<?php

namespace App\Models;

class CoasterModel
{
    public function validateInput(array $data): bool
    {
        return isset($data['liczba_personelu'], $data['liczba_klientow'], $data['dl_trasy'], $data['godziny_od'], $data['godziny_do']);
    }

    public function generateId(): string
    {
        return uniqid('coaster_', true);
    }

    public function addWagonToCoaster(array $coaster, array $wagon): array
    {
        $wagon['id'] = uniqid('wagon_', true);
        $wagon['ilosc_miejsc'] = (int) $wagon['ilosc_miejsc'];
        $wagon['predkosc_wagonu'] = (float) $wagon['predkosc_wagonu'];
    
        $coaster['wagons'][] = $wagon;
    
        return $coaster;
    }
    
    public function removeWagonFromCoaster(array $coaster, string $wagonId): ?array
    {
        $found = false;
        $coaster['wagons'] = array_filter($coaster['wagons'], function ($wagon) use ($wagonId, &$found) {
            if ($wagon['id'] === $wagonId) {
                $found = true;
                return false;
            }
            return true;
        });

        return $found ? $coaster : null;
    }

    

}
