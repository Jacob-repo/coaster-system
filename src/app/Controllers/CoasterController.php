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
    
}
