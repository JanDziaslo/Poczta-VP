<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// stan sesji: user 1 - tylko odczyt, user 2 - odczyt i zapis
if (!isset($_SESSION['user']))                     { $_SESSION['user'] = 1; }
if (!isset($_SESSION['miasta']) || !is_array($_SESSION['miasta'])) { $_SESSION['miasta'] = []; }
if (!array_key_exists('login_time', $_SESSION))    { $_SESSION['login_time'] = null; }

$akcja = $_POST['akcja'] ?? '';

// połącz / rozłącz - przełączenie konta bazy
if ($akcja === 'polacz') {
    $_SESSION['user']       = 2;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    header('Location: index.php');
    exit;
}
if ($akcja === 'rozlacz') {
    $_SESSION['user']       = 1;
    $_SESSION['login_time'] = null;
    header('Location: index.php');
    exit;
}

$jestUser2 = ($_SESSION['user'] === 2);

// połączenie z bazą na koncie zależnym od trybu
$conn = $jestUser2 ? polacz(DB_USER2, DB_PASS2) : polacz(DB_USER1, DB_PASS1);

// wybór miejscowości z lewej kolumny
if (isset($_GET['szukaj'])) {
    $wybrane = $_GET['miasta'] ?? [];
    $_SESSION['miasta'] = is_array($wybrane)
        ? array_values(array_unique(array_map('strval', $wybrane)))
        : [];
}
$wybraneMiasta = $_SESSION['miasta'];

// operacje zapisu - tylko user 2
$bledy     = [];
$formularz = [];
$edytujId  = isset($_GET['edytuj']) ? (int) $_GET['edytuj'] : 0;

if ($jestUser2 && $akcja === 'dodaj') {
    $formularz = pobierzDaneFormularza($_POST);
    $bledy     = walidujDane($formularz, $conn);
    if (!$bledy) {
        $stmt = $conn->prepare(
            'INSERT INTO uczniowie (nazwisko, imie, pesel, adres_ul, adres_nr, miasto)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssssss',
            $formularz['nazwisko'], $formularz['imie'], $formularz['pesel'],
            $formularz['adres_ul'], $formularz['adres_nr'], $formularz['miasto']
        );
        $stmt->execute();
        $stmt->close();

        zapiszZmiane($conn, $formularz['imie'], $formularz['nazwisko'], 'dodanie');
        header('Location: index.php');
        exit;
    }
}

if ($jestUser2 && $akcja === 'zapisz') {
    $edytujId  = (int) ($_POST['id_ucz'] ?? 0);
    $formularz = pobierzDaneFormularza($_POST);
    $bledy     = walidujDane($formularz, $conn, $edytujId);
    if (!$bledy) {
        $stmt = $conn->prepare(
            'UPDATE uczniowie
                SET nazwisko = ?, imie = ?, pesel = ?, adres_ul = ?, adres_nr = ?, miasto = ?
              WHERE id_ucz = ?'
        );
        $stmt->bind_param(
            'ssssssi',
            $formularz['nazwisko'], $formularz['imie'], $formularz['pesel'],
            $formularz['adres_ul'], $formularz['adres_nr'], $formularz['miasto'], $edytujId
        );
        $stmt->execute();
        $stmt->close();

        zapiszZmiane($conn, $formularz['imie'], $formularz['nazwisko'], 'edycja');
        header('Location: index.php');
        exit;
    }
}

if ($jestUser2 && $akcja === 'usun') {
    $ids = array_values(array_filter(array_map('intval', (array) ($_POST['usun'] ?? []))));
    if ($ids) {
        $sel = $conn->prepare('SELECT imie, nazwisko FROM uczniowie WHERE id_ucz = ?');
        $del = $conn->prepare('DELETE FROM uczniowie WHERE id_ucz = ?');
        foreach ($ids as $id) {
            $sel->bind_param('i', $id);
            $sel->execute();
            $row = $sel->get_result()->fetch_assoc();
            if ($row) {
                $del->bind_param('i', $id);
                $del->execute();
                zapiszZmiane($conn, $row['imie'], $row['nazwisko'], 'usunięcie');
            }
        }
        $sel->close();
        $del->close();

        header('Location: index.php');
        exit;
    }
    $bledy['usun'] = 'Nie zaznaczono żadnego rekordu do usunięcia.';
}

// miejscowości do listy wyboru
$miasta = [];
$res = $conn->query('SELECT DISTINCT miasto FROM uczniowie ORDER BY miasto');
while ($r = $res->fetch_assoc()) {
    $miasta[] = $r['miasto'];
}

// uczniowie z wybranych miejscowości, sortowani wg nazwiska
$uczniowie = [];
if ($wybraneMiasta) {
    $znaki = implode(',', array_fill(0, count($wybraneMiasta), '?'));
    $sql   = "SELECT id_ucz, nazwisko, imie, pesel, adres_ul, adres_nr, miasto
                FROM uczniowie
               WHERE miasto IN ($znaki)
            ORDER BY nazwisko, imie";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('s', count($wybraneMiasta)), ...$wybraneMiasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $uczniowie[] = $r;
    }
    $stmt->close();
}

// urodziny dzisiaj na podstawie PESEL - tylko user 2
$urodziny = [];
if ($jestUser2) {
    $dzisiaj = date('m-d');
    $res = $conn->query('SELECT imie, nazwisko, pesel FROM uczniowie ORDER BY nazwisko');
    while ($r = $res->fetch_assoc()) {
        $dataUrodzenia = dataUrodzeniaZPesel($r['pesel']);
        if ($dataUrodzenia !== null && date('m-d', strtotime($dataUrodzenia)) === $dzisiaj) {
            $urodziny[] = $r;
        }
    }
}

// ostatnia zmiana - tylko user 2
$ostatnia = $jestUser2 ? ostatniaZmiana($conn) : null;

// edytowany rekord - tylko user 2
$edytowany = null;
if ($jestUser2 && $edytujId > 0) {
    $stmt = $conn->prepare('SELECT * FROM uczniowie WHERE id_ucz = ?');
    $stmt->bind_param('i', $edytujId);
    $stmt->execute();
    $edytowany = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
}
if ($edytowany === null) {
    $edytujId = 0;
}

// wartość pola formularza (najpierw to, co wpisał użytkownik)
$wartosc = function (string $pole) use ($formularz, $edytowany): string {
    if (array_key_exists($pole, $formularz)) {
        return (string) $formularz[$pole];
    }
    if ($edytowany !== null && isset($edytowany[$pole])) {
        return (string) $edytowany[$pole];
    }
    return '';
};

// klasa is-invalid dla pola z błędem
$niepoprawne = function (string $pole) use ($bledy): string {
    return isset($bledy[$pole]) ? 'is-invalid' : '';
};

$tytulFormularza = $edytowany ? 'Edycja ucznia' : 'Dodaj nowego ucznia';
?>
<!DOCTYPE html>
<html lang="pl" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uczniowie - <?= $jestUser2 ? 'user 2' : 'user 1' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar bg-body-tertiary border-bottom">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Uczniowie</span>

        <div class="d-flex align-items-center gap-3 flex-wrap">
            <?php if ($jestUser2): ?>
                <span class="badge text-bg-primary">uczen2 – odczyt i edycja</span>
                <span class="badge text-bg-success">Zalogowano: <?= h($_SESSION['login_time']) ?></span>
            <?php else: ?>
                <span class="badge text-bg-secondary">uczen1 – tylko przeglądanie</span>
            <?php endif; ?>

            <form method="post" action="index.php" class="m-0">
                <?php if ($jestUser2): ?>
                    <input type="hidden" name="akcja" value="rozlacz">
                    <button type="submit" class="btn btn-danger btn-sm">Rozłącz</button>
                <?php else: ?>
                    <input type="hidden" name="akcja" value="polacz">
                    <button type="submit" class="btn btn-success btn-sm">Połącz</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</nav>

<main class="container-fluid py-4">
    <div class="row g-4">

        <div class="col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-semibold">Miejscowości</div>
                <div class="card-body d-flex flex-column">
                    <form method="get" action="index.php" class="d-flex flex-column h-100">
                        <select name="miasta[]" multiple size="12" class="form-select lista">
                            <?php foreach ($miasta as $m): ?>
                                <option value="<?= h($m) ?>" <?= in_array($m, $wybraneMiasta, true) ? 'selected' : '' ?>>
                                    <?= h($m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="szukaj" value="1" class="btn btn-primary w-100 mt-3">Szukaj</button>
                        <p class="form-text mb-0 mt-2">
                            Zaznacz jedną lub kilka miejscowości (Ctrl / Shift), a następnie kliknij „Szukaj”.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">Uczniowie</div>
                <div class="card-body">

                    <?php if (!empty($bledy)): ?>
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($bledy as $b): ?>
                                    <li><?= h($b) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($jestUser2): ?>
                    <form method="post" action="index.php" id="form-tabela">
                    <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <?php if ($jestUser2): ?><th class="text-center">Wybór</th><?php endif; ?>
                                        <th>Lp.</th>
                                        <th>Imię</th>
                                        <th>Nazwisko</th>
                                        <th>Adres</th>
                                        <?php if ($jestUser2): ?>
                                            <th>PESEL</th>
                                            <th class="text-center">Edycja</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!$uczniowie): ?>
                                    <tr>
                                        <td colspan="<?= $jestUser2 ? 7 : 4 ?>" class="text-center fst-italic text-body-secondary py-4">
                                            Brak danych – zaznacz miejscowości i kliknij „Szukaj”.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($uczniowie as $i => $u): ?>
                                        <tr>
                                            <?php if ($jestUser2): ?>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="usun[]" value="<?= (int) $u['id_ucz'] ?>">
                                                </td>
                                            <?php endif; ?>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= h($u['imie']) ?></td>
                                            <td><?= h($u['nazwisko']) ?></td>
                                            <td><?= h($u['adres_ul'] . ' ' . $u['adres_nr'] . ', ' . $u['miasto']) ?></td>
                                            <?php if ($jestUser2): ?>
                                                <td class="font-monospace"><?= h($u['pesel']) ?></td>
                                                <td class="text-center">
                                                    <a class="btn btn-warning btn-sm"
                                                       href="index.php?edytuj=<?= (int) $u['id_ucz'] ?>">Edytuj</a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <?php if ($jestUser2): ?>
                                <button type="submit" name="akcja" value="usun" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Usunąć zaznaczone rekordy?');">
                                    Usuń zaznaczone
                                </button>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <span class="text-body-secondary">
                                Liczba uczniów: <span class="badge text-bg-info"><?= count($uczniowie) ?></span>
                            </span>
                        </div>

                    <?php if ($jestUser2): ?>
                    </form>
                    <?php endif; ?>

                    <?php if ($jestUser2): ?>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="card border-warning h-100">
                                    <div class="card-header fw-semibold">
                                        Urodziny dzisiaj (<?= h(date('d.m.Y')) ?>)
                                    </div>
                                    <?php if ($urodziny): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($urodziny as $u): ?>
                                                <li class="list-group-item"><?= h($u['imie'] . ' ' . $u['nazwisko']) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="card-body fst-italic text-body-secondary">
                                            Brak uczniów z urodzinami w dniu dzisiejszym.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-info h-100">
                                    <div class="card-header fw-semibold">Ostatnia zmiana w tabeli</div>
                                    <div class="card-body">
                                        <?php if ($ostatnia): ?>
                                            <p class="mb-0">
                                                <?= h($ostatnia['imie'] . ' ' . $ostatnia['nazwisko']) ?>
                                                <span class="badge text-bg-secondary"><?= h($ostatnia['operacja']) ?></span><br>
                                                <small class="text-body-secondary">data: <?= h($ostatnia['data']) ?></small>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-0 fst-italic text-body-secondary">
                                                Nie wykonano jeszcze żadnej zmiany.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($jestUser2): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header fw-semibold"><?= h($tytulFormularza) ?></div>
                    <div class="card-body">
                        <form method="post" action="index.php" class="row g-3">
                            <input type="hidden" name="akcja" value="<?= $edytowany ? 'zapisz' : 'dodaj' ?>">
                            <?php if ($edytowany): ?>
                                <input type="hidden" name="id_ucz" value="<?= (int) $edytowany['id_ucz'] ?>">
                            <?php endif; ?>

                            <div class="col-md-6">
                                <label class="form-label" for="imie">Imię</label>
                                <input class="form-control <?= $niepoprawne('imie') ?>" type="text"
                                       id="imie" name="imie" value="<?= h($wartosc('imie')) ?>" maxlength="30">
                                <div class="invalid-feedback"><?= h($bledy['imie'] ?? '') ?></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="nazwisko">Nazwisko</label>
                                <input class="form-control <?= $niepoprawne('nazwisko') ?>" type="text"
                                       id="nazwisko" name="nazwisko" value="<?= h($wartosc('nazwisko')) ?>" maxlength="40">
                                <div class="invalid-feedback"><?= h($bledy['nazwisko'] ?? '') ?></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="pesel">PESEL</label>
                                <input class="form-control <?= $niepoprawne('pesel') ?>" type="text"
                                       id="pesel" name="pesel" value="<?= h($wartosc('pesel')) ?>"
                                       maxlength="11" inputmode="numeric">
                                <div class="invalid-feedback"><?= h($bledy['pesel'] ?? '') ?></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="miasto">Miejscowość</label>
                                <input class="form-control <?= $niepoprawne('miasto') ?>" type="text"
                                       id="miasto" name="miasto" value="<?= h($wartosc('miasto')) ?>" maxlength="30">
                                <div class="invalid-feedback"><?= h($bledy['miasto'] ?? '') ?></div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label" for="adres_ul">Ulica</label>
                                <input class="form-control <?= $niepoprawne('adres_ul') ?>" type="text"
                                       id="adres_ul" name="adres_ul" value="<?= h($wartosc('adres_ul')) ?>" maxlength="40">
                                <div class="invalid-feedback"><?= h($bledy['adres_ul'] ?? '') ?></div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="adres_nr">Nr domu</label>
                                <input class="form-control <?= $niepoprawne('adres_nr') ?>" type="text"
                                       id="adres_nr" name="adres_nr" value="<?= h($wartosc('adres_nr')) ?>" maxlength="6">
                                <div class="invalid-feedback"><?= h($bledy['adres_nr'] ?? '') ?></div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <?= $edytowany ? 'Zapisz zmiany' : 'Dodaj ucznia' ?>
                                </button>
                                <?php if ($edytowany): ?>
                                    <a class="btn btn-outline-secondary" href="index.php">Anuluj</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>
