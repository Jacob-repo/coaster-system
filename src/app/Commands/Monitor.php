<?php namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use React\EventLoop\Factory;
use Clue\React\Redis\Factory as RedisFactory;

class Monitor extends BaseCommand
{
    protected $group = 'Coaster';
    protected $name = 'coaster:monitor';
    protected $description = 'Monitoruje kolejki górskie w czasie rzeczywistym.';

    public function run(array $params)
    {
        CLI::write("Uruchomiono monitor kolejek górskich (odświeżanie co 5 sek.)", 'blue');
        CLI::write("Czas startu: " . date('Y-m-d H:i:s') . "\n", 'blue');

        $loop = Factory::create();
        $redisFactory = new RedisFactory($loop);
        $redis = $redisFactory->createLazyClient('redis://redis');

        $loop->addPeriodicTimer(5, function () use ($redis) {
            $redis->keys('coaster:*')->then(function ($keys) use ($redis) {
                foreach ($keys as $key) {
                    $redis->get($key)->then(function ($json) use ($key) {
                        $coaster = json_decode($json, true);
                        $this->evaluateCoaster($key, $coaster);
                    });
                }
            });
        });

        $loop->run();
    }

    private function evaluateCoaster($key, $coaster)
    {
        $name = $key;
        $wagons = $coaster['wagons'] ?? [];
        $requiredP = 1 + count($wagons) * 2;
        $currentP = $coaster['liczba_personelu'];
        $status = [];

        if ($currentP < $requiredP) {
            $status[] = "Brakuje " . ($requiredP - $currentP) . " pracowników";
        } elseif ($currentP > $requiredP) {
            $status[] = "Nadmiar " . ($currentP - $requiredP) . " pracowników";
        }

        $avgSpeed = $this->avgSpeed($wagons);
        $capacity = $this->calculateMaxClients($wagons, $coaster['dl_trasy'], $coaster['godziny_od'], $coaster['godziny_do'], $avgSpeed);
        $requiredClients = $coaster['liczba_klientow'];

        if ($capacity < $requiredClients) {
            $status[] = "Brakuje przepustowości dla " . ($requiredClients - $capacity) . " klientów";
        } elseif ($capacity > 2 * $requiredClients) {
            $status[] = "Zbyt duża przepustowość (nadmiar dla " . ($capacity - 2 * $requiredClients) . " klientów)";
        }

        if (!empty($status)) {
            $now = date('Y-m-d H:i:s');
            $message = "[$now] $name - Problem: " . implode(', ', $status);
            log_message('warning', $message);
            file_put_contents(WRITEPATH . 'logs/monitor.log', $message . "\n", FILE_APPEND);
            CLI::write($message, 'yellow');
        } else {
            CLI::write("[$name]", 'green');
            CLI::write("- Godziny działania: {$coaster['godziny_od']} - {$coaster['godziny_do']}");
            CLI::write("- Liczba wagonów: " . count($wagons));
            CLI::write("- Dostępny personel: $currentP/$requiredP");
            CLI::write("- Klienci dziennie: {$coaster['liczba_klientow']}");
            CLI::write("- Przepustowość maksymalna: $capacity");
            CLI::write("- Status: OK\n");
        }
        
    }

    private function avgSpeed(array $wagons): float
    {
        if (empty($wagons)) return 0;
        return array_sum(array_column($wagons, 'predkosc_wagonu')) / count($wagons);
    }

    private function calculateMaxClients(array $wagons, int $dlTrasy, string $od, string $do, float $avgSpeed): int
    {
        if ($avgSpeed <= 0) return 0;
        $start = \DateTime::createFromFormat('H:i', $od);
        $end = \DateTime::createFromFormat('H:i', $do);
        if (!$start || !$end) return 0;

        $minutesAvailable = ($end->getTimestamp() - $start->getTimestamp()) / 60;
        $rideMinutes = ($dlTrasy / $avgSpeed) / 60 + 5;

        $capacity = 0;
        foreach ($wagons as $w) {
            $ridesPerDay = floor($minutesAvailable / $rideMinutes);
            $capacity += $ridesPerDay * ($w['ilosc_miejsc'] ?? 0);
        }

        return (int) $capacity;
    }
}
