<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dokumentacja API - System Kolejek Górskich</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 2rem;
        }
        h1 {
            color: #dd4814;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 2rem;
        }
        th, td {
            padding: 1rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #eee;
        }
        code, pre {
            background: #f1f1f1;
            padding: 4px 8px;
            border-radius: 3px;
        }
    </style>
</head>
<body>

    <h1>Dokumentacja API - System Kolejek Górskich</h1>
    <p>API zarządzania kolejkami górskimi i wagonami (CodeIgniter 4 + Redis)</p>

    <table>
        <thead>
            <tr>
                <th>Metoda</th>
                <th>Endpoint</th>
                <th>Opis</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>GET</td><td><code>/api/coasters</code></td><td>Lista wszystkich kolejek</td></tr>
            <tr><td>GET</td><td><code>/api/coasters/{id}/status</code></td><td>Status techniczny, klientów, personelu</td></tr>
            <tr><td>POST</td><td><code>/api/coasters</code></td><td>Rejestracja nowej kolejki</td></tr>
            <tr><td>PUT</td><td><code>/api/coasters/{id}</code></td><td>Aktualizacja kolejki</td></tr>
            <tr><td>POST</td><td><code>/api/coasters/{id}/wagons</code></td><td>Dodanie wagonu</td></tr>
            <tr><td>DELETE</td><td><code>/api/coasters/{id}/wagons/{wagonId}</code></td><td>Usunięcie wagonu</td></tr>
        </tbody>
    </table>

    <h2>Przykładowe dane wejściowe:</h2>

    <h3>POST /api/coasters</h3>
    <pre>{
  "liczba_personelu": 16,
  "liczba_klientow": 60000,
  "dl_trasy": 1800,
  "godziny_od": "08:00",
  "godziny_do": "16:00"
}</pre>

    <h3>POST /api/coasters/{id}/wagons</h3>
    <pre>{
  "ilosc_miejsc": 32,
  "predkosc_wagonu": 1.2
}</pre>

    <p>Aby uruchomić monitorowanie w CLI:</p>
    <pre>php spark coaster:monitor</pre>

    <p>Dane są przechowywane w Redis z prefiksem <code>dev:</code> lub <code>prod:</code> zależnie od środowiska. Logi zależne od trybu (development/production).</p>

</body>
</html>
