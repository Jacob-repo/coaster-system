<?php

namespace App\Controllers;

use App\Models\CoasterModel;
use App\Services\RedisService;
use CodeIgniter\RESTful\ResourceController;

class CoasterController extends ResourceController
{
    protected RedisService $redis;
    protected $model;

    public function __construct()
    {
        $this->redis = new RedisService();
        $this->model = new CoasterModel();
    }

    public function index()
    {
        $keys = $this->redis->getKeysWithPrefix('coaster:');

        $coasters = array_map(function ($key) {
            return $this->redis->get(str_replace($this->redis->getPrefix(), '', $key));
        }, $keys);

        return $this->respond($coasters);
    }


    public function create()
    {
        $data = $this->request->getJSON(true);

        if (!$this->model->validateInput($data)) {
            return $this->failValidationError('Nieprawidłowe dane wejściowe.');
        }

        $id = $this->model->generateId();
        $data['id'] = $id;
        $data['wagons'] = [];

        $this->redis->set("coaster:$id", $data);

        return $this->respondCreated($data);
    }

    public function addWagon($coasterId)
    {
        $data = $this->request->getJSON(true);

        if (!$this->redis->exists("coaster:$coasterId")) {
            return $this->failNotFound("Kolejka o ID $coasterId nie istnieje.");
        }

        $coaster = $this->redis->get("coaster:$coasterId");

        if (!isset($data['ilosc_miejsc'], $data['predkosc_wagonu'])) {
            return $this->failValidationError('Brak wymaganych danych: ilosc_miejsc, predkosc_wagonu');
        }

        $updated = $this->model->addWagonToCoaster($coaster, $data);
        $this->redis->set("coaster:$coasterId", $updated);

        return $this->respondCreated($updated);
    }

    public function deleteWagon(string $coasterId, string $wagonId)
    {
        if (!$this->redis->exists("coaster:$coasterId")) {
            return $this->failNotFound("Kolejka o ID $coasterId nie istnieje.");
        }
    
        $coaster = $this->redis->get("coaster:$coasterId");
    
        $wagons = $coaster['wagons'] ?? [];
        $updatedWagons = array_filter($wagons, fn($wagon) => $wagon['id'] !== $wagonId);
    
        if (count($updatedWagons) === count($wagons)) {
            return $this->failNotFound("Wagon o ID $wagonId nie istnieje.");
        }
    
        $coaster['wagons'] = array_values($updatedWagons);
        $this->redis->set("coaster:$coasterId", $coaster);
    
        return $this->respondDeleted(["message" => "Wagon usunięty."]);
    }

    public function update($coasterId = null)
    {
        $data = $this->request->getJSON(true);

        if (!$this->redis->exists("coaster:$coasterId")) {
            return $this->failNotFound("Kolejka o ID $coasterId nie istnieje.");
        }

        $coaster = $this->redis->get("coaster:$coasterId");

        unset($data['dl_trasy']);

        $updated = array_merge($coaster, $data);

        $this->redis->set("coaster:$coasterId", $updated);

        return $this->respondUpdated($updated);
    }

    public function getStatus($coasterId)
    {
        if (!$this->redis->exists("coaster:$coasterId")) {
            return $this->failNotFound("Kolejka o ID $coasterId nie istnieje.");
        }
    
        $coaster = $this->redis->get("coaster:$coasterId");
    
        $wagons = $coaster['wagons'] ?? [];

        $avgSpeed = $this->avgSpeed($wagons);
        if ($avgSpeed <= 0) {
            return $this->failServerError("Nieprawidłowa prędkość wagonu.");
        }
    
        $requiredPersonnel = 1 + count($coaster['wagons']) * 2;
        $coaster['avg_speed'] = $avgSpeed;
    
        $coaster['personnel'] = [
            'required' => $requiredPersonnel,
            'current'  => $coaster['liczba_personelu'],
            'status'   => $this->evaluatePersonnelStatus($coaster['liczba_personelu'], $requiredPersonnel),
        ];
    
        $maxClients = $this->calculateMaxClients($coaster['wagons'], $coaster['dl_trasy'], $coaster['godziny_od'], $coaster['godziny_do'], $avgSpeed);
        $coaster['capacity'] = [
            'required_clients' => $coaster['liczba_klientow'],
            'max_capacity'     => $maxClients,
            'status'           => $this->evaluateCapacityStatus($maxClients, $coaster['liczba_klientow']),
        ];
    
        return $this->respond($coaster);
    }
    
    private function avgSpeed(array $wagons): float
    {
        if (empty($wagons)) return 0;
    
        $sum = 0;
        foreach ($wagons as $w) {
            $sum += $w['predkosc_wagonu'] ?? 0;
        }
    
        return count($wagons) > 0 ? $sum / count($wagons) : 0;
    }

    private function evaluatePersonnelStatus(int $current, int $required): string
    {
        if ($current < $required) {
            return "Brakuje " . ($required - $current) . " pracowników";
        } elseif ($current > $required) {
            return "Nadmiar " . ($current - $required) . " pracowników";
        }

        return "OK";
    }

    private function evaluateCapacityStatus(int $maxCapacity, int $requiredClients): string
    {
        if ($maxCapacity < $requiredClients) {
            return "Brakuje przepustowości dla " . ($requiredClients - $maxCapacity) . " klientów";
        } elseif ($maxCapacity > 2 * $requiredClients) {
            return "Zbyt duża przepustowość (nadmiar dla " . ($maxCapacity - 2 * $requiredClients) . " klientów)";
        }

        return "OK";
    }

    private function calculateMaxClients(array $wagons, int $dlTrasy, string $od, string $do, float $avgSpeed): int
    {
        if ($avgSpeed <= 0) return 0;

        $start = \DateTime::createFromFormat('H:i', $od);
        $end = \DateTime::createFromFormat('H:i', $do);
        if (!$start || !$end) return 0;

        $minutesAvailable = ($end->getTimestamp() - $start->getTimestamp()) / 60;

        $rideMinutes = ($dlTrasy / $avgSpeed) / 60 + 5;

        if ($rideMinutes <= 0) return 0;

        $capacity = 0;
        foreach ($wagons as $w) {
            $ridesPerDay = floor($minutesAvailable / $rideMinutes);
            $capacity += $ridesPerDay * ($w['ilosc_miejsc'] ?? 0);
        }

        return (int) $capacity;
    }




    
}
