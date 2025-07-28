# System Kolejek Górskich – API

Aplikacja REST API do zarządzania kolejkami górskimi i przypisanymi do nich wagonami, z obsługą personelu i klientów. Dane są przechowywane w Redis. System posiada dwa tryby działania: development i production.

## Wymagania

- PHP 8.1+
- Docker + Docker Compose
- Redis
- CodeIgniter 4.6

## Instalacja

1. Sklonuj repozytorium:

git clone https://github.com/Jacob-repo/coaster-system.git
cd coaster-system


2. Skopiuj plik `.env.dev` lub `.env.prod` jako `.env`.

3. Uruchom kontenery:

docker-compose up -d --build


4. Wejdź do kontenera aplikacji:

docker-compose exec app bash


5. (Opcjonalnie) Uruchom monitor CLI:

php spark coaster:monitor


## Endpointy API

| Metoda | Endpoint | Opis |
|--------|----------|------|
| GET | /api/coasters | Lista wszystkich kolejek |
| POST | /api/coasters | Rejestracja nowej kolejki |
| GET | /api/coasters/{id} | Szczegóły kolejki i status |
| PUT | /api/coasters/{id} | Aktualizacja danych kolejki |
| DELETE | /api/coasters/{id}/wagons/{wagon_id} | Usunięcie wagonu |
| POST | /api/coasters/{id}/wagons | Dodanie wagonu |

## Logika

- Każda kolejka wymaga 1 pracownika + 2 na każdy wagon
- Klienci są obsługiwani na podstawie mocy wagonów, czasu trasy i godzin działania
- Wagon po kursie ma 5 minut przerwy
- System informuje o brakach lub nadmiarach personelu i wagonów
- Monitor CLI wykrywa problemy i loguje je

## Środowiska

- `development`: prefix `dev:`, logi `debug/info/warning/error`
- `production`: prefix `prod:`, logi `warning/error`

## Przykładowe dane

Tworzenie kolejki:

`{
  "liczba_personelu": 16,
  "liczba_klientow": 60000,
  "dl_trasy": 1800,
  "godziny_od": "08:00",
  "godziny_do": "16:00"
}

Dodanie wagonu:

{
  "ilosc_miejsc": 32,
  "predkosc_wagonu": 1.2
}

Po uruchomieniu systemu dokumentacja API jest widoczna na stronie głównej (/) w formie HTML.

Licencja
MIT, 2025
