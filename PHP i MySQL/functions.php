<?php
declare(strict_types=1);

// Bezpieczne wypisanie tekstu na stronie.
function h($wartosc): string
{
    return htmlspecialchars((string) $wartosc, ENT_QUOTES, 'UTF-8');
}

// Zwraca datę urodzenia (Y-m-d) z numeru PESEL albo null, gdy numer jest błędny.
// Miesiąc: 1900-1999 bez zmian, 2000-2099 +20, 2100-2199 +40, 1800-1899 +80.
function dataUrodzeniaZPesel(string $pesel): ?string
{
    if (!preg_match('/^[0-9]{11}$/', $pesel)) {
        return null;
    }

    $rok   = (int) substr($pesel, 0, 2);
    $mies  = (int) substr($pesel, 2, 2);
    $dzien = (int) substr($pesel, 4, 2);

    if ($mies > 80) {
        $rok += 1800;
        $mies -= 80;
    } elseif ($mies > 60) {
        $rok += 2200;
        $mies -= 60;
    } elseif ($mies > 40) {
        $rok += 2100;
        $mies -= 40;
    } elseif ($mies > 20) {
        $rok += 2000;
        $mies -= 20;
    } else {
        $rok += 1900;
    }

    if (!checkdate($mies, $dzien, $rok)) {
        return null;
    }

    return sprintf('%04d-%02d-%02d', $rok, $mies, $dzien);
}

function peselPoprawny(string $pesel): bool
{
    if (dataUrodzeniaZPesel($pesel) === null) {
        return false;
    }

    $wagi = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
    $suma = 0;
    for ($i = 0; $i < 10; $i++) {
        $suma += (int) $pesel[$i] * $wagi[$i];
    }

    $cyfraKontrolna = (10 - ($suma % 10)) % 10;

    return $cyfraKontrolna === (int) $pesel[10];
}


function pobierzDaneFormularza(array $src): array
{
    return [
        'imie'     => trim((string) ($src['imie'] ?? '')),
        'nazwisko' => trim((string) ($src['nazwisko'] ?? '')),
        'pesel'    => trim((string) ($src['pesel'] ?? '')),
        'adres_ul' => trim((string) ($src['adres_ul'] ?? '')),
        'adres_nr' => trim((string) ($src['adres_nr'] ?? '')),
        'miasto'   => trim((string) ($src['miasto'] ?? '')),
    ];
}

function walidujDane(array $d, mysqli $conn, ?int $idPomin = null): array
{
    $bledy = [];

    // imię i nazwisko - tylko litery (plus spacja i myślnik)
    if (!preg_match('/^[\p{L}][\p{L} \-]*$/u', $d['imie'])) {
        $bledy['imie'] = 'Imię może zawierać wyłącznie litery (bez cyfr i znaków specjalnych).';
    } elseif (mb_strlen($d['imie']) > 30) {
        $bledy['imie'] = 'Imię może mieć maksymalnie 30 znaków.';
    }

    if (!preg_match('/^[\p{L}][\p{L} \-]*$/u', $d['nazwisko'])) {
        $bledy['nazwisko'] = 'Nazwisko może zawierać wyłącznie litery (bez cyfr i znaków specjalnych).';
    } elseif (mb_strlen($d['nazwisko']) > 40) {
        $bledy['nazwisko'] = 'Nazwisko może mieć maksymalnie 40 znaków.';
    }

    // PESEL - 11 cyfr i poprawna suma kontrolna
    if (!preg_match('/^[0-9]{11}$/', $d['pesel'])) {
        $bledy['pesel'] = 'PESEL musi składać się z dokładnie 11 cyfr.';
    } elseif (!peselPoprawny($d['pesel'])) {
        $bledy['pesel'] = 'PESEL ma niepoprawną sumę kontrolną lub niepoprawną datę urodzenia.';
    } else {
        // PESEL musi być unikalny
        $stmt = $conn->prepare('SELECT id_ucz FROM uczniowie WHERE pesel = ? LIMIT 1');
        $stmt->bind_param('s', $d['pesel']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && ($idPomin === null || (int) $row['id_ucz'] !== $idPomin)) {
            $bledy['pesel'] = 'Uczeń o podanym numerze PESEL już istnieje.';
        }
    }

    // ulica
    if (!preg_match('/^[\p{L}0-9][\p{L}0-9 .\-]*$/u', $d['adres_ul'])) {
        $bledy['adres_ul'] = 'Ulica może zawierać litery, cyfry, spacje, kropki i myślniki.';
    } elseif (mb_strlen($d['adres_ul']) > 40) {
        $bledy['adres_ul'] = 'Nazwa ulicy może mieć maksymalnie 40 znaków.';
    }

    // numer domu, np. 21a
    if (!preg_match('/^[0-9]{1,4}[A-Za-z]?$/', $d['adres_nr'])) {
        $bledy['adres_nr'] = 'Numer domu to cyfry z opcjonalną literą (np. 21a).';
    }

    // miejscowość
    if (!preg_match('/^[\p{L}][\p{L} \-]*$/u', $d['miasto'])) {
        $bledy['miasto'] = 'Nazwa miejscowości może zawierać wyłącznie litery.';
    } elseif (mb_strlen($d['miasto']) > 30) {
        $bledy['miasto'] = 'Nazwa miejscowości może mieć maksymalnie 30 znaków.';
    }

    return $bledy;
}

// Zapisuje wpis do historii zmian.
function zapiszZmiane(mysqli $conn, string $imie, string $nazwisko, string $operacja): void
{
    $stmt = $conn->prepare(
        'INSERT INTO zmiany_uczniowie (imie, nazwisko, operacja, data)
         VALUES (?, ?, ?, NOW())'
    );
    $stmt->bind_param('sss', $imie, $nazwisko, $operacja);
    $stmt->execute();
    $stmt->close();
}

// Zwraca ostatni wpis z historii zmian.
function ostatniaZmiana(mysqli $conn): ?array
{
    $res = $conn->query(
        'SELECT imie, nazwisko, operacja, data
           FROM zmiany_uczniowie
       ORDER BY id_zmiany DESC
          LIMIT 1'
    );

    if (!$res) {
        return null;
    }

    return $res->fetch_assoc() ?: null;
}
