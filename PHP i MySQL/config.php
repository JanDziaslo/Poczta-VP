<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'szkola';

const DB_USER1 = 'uczen1';
const DB_PASS1 = 'uczen1pass';

const DB_USER2 = 'uczen2';
const DB_PASS2 = 'uczen2pass';

// Otwiera połączenie mysqli dla podanego użytkownika.
function polacz(string $user, string $haslo): mysqli
{
    try {
        $conn = new mysqli(DB_HOST, $user, $haslo, DB_NAME);
    } catch (mysqli_sql_exception $e) {
        die('Błąd połączenia z bazą danych (' . htmlspecialchars($user) . '): '
            . htmlspecialchars($e->getMessage()));
    }

    if ($conn->connect_error) {
        die('Błąd połączenia z bazą danych (' . htmlspecialchars($user) . '): '
            . htmlspecialchars($conn->connect_error));
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
