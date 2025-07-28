<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $routes = [
            ['GET', '/api/coasters', 'Pobierz wszystkie kolejki'],
            ['GET', '/api/coasters/{id}', 'Pobierz kolejkę po ID'],
            ['POST', '/api/coasters', 'Dodaj nową kolejkę'],
            ['PUT', '/api/coasters/{id}', 'Edytuj kolejkę'],
            ['DELETE', '/api/coasters/{id}', 'Usuń kolejkę'],
            ['GET', '/api/wagons', 'Pobierz wszystkie wagony'],
            ['POST', '/api/wagons', 'Dodaj wagon'],
            ['DELETE', '/api/wagons/{id}', 'Usuń wagon'],
            ['GET', '/api/cache/flush', 'Wyczyść cache Redis'],
        ];

        return view('welcome_message', ['routes' => $routes]);
    }
}
