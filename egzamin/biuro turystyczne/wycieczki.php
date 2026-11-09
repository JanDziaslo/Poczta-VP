<?php
$polaczenie = mysqli_connect("localhost","root", "", "biuro" );

$zapytanie1 = "SELECT id, dataWyjazdu, cel, cena from wycieczki where dostepna = 1";
$wynik = mysqli_query($polaczenie,$zapytanie1);

$zapytanie2 = "SELECT nazwapliku, podpis from zdjecia ORDER BY podpis DESC;";
$wynik2 = mysqli_query($polaczenie,$zapytanie2);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wycieczki po Europie</title>
    <link rel="stylesheet" href="styl4.css">
</head>
<body>

    <header>
        <h1>BIURO TURYSTYCZNE</h1>
    </header>

    <div id="dane">
        <h3>Wycieczki, na które są wolne miejsca</h3>
        <ul>
            <?php
            while ($wiersz = mysqli_fetch_array($wynik)) {
                echo '<li>'. $wiersz['id'] . '. dnia ' .  $wiersz['dataWyjazdu']. ' jedziemy do '. $wiersz['cel'] . ' cena: '.  $wiersz['cena'] . '</li>';
            }
            ?>
        </ul>
    </div>

    <div id="lewy">
        <h2>Bestselery</h2>
        <table>
            <tr>
                <td>Wenecja</td>
                <td>kwiecień</td>
            </tr>
            <tr>
                <td>Londyn</td>
                <td>lipiec</td>
            </tr>
            <tr>
                <td>Rzym</td>
                <td>wrzesień</td>
            </tr>
        </table>
    </div>

    <div id="srodek">
        <h2>Nasze zdjęcia</h2>
        <?php
            while ($wiersz2 = mysqli_fetch_array($wynik2)) {
                echo '<img src="'. $wiersz2['nazwapliku']. '" alt="' . $wiersz2['podpis'] . '">' ;
            }
        ?>
    </div>

    <div id="prawy">
        <h2>Skontaktuj się</h2>
        <a href="mailto:turysta@wycieczki.pl">napisz do nas</a>
        <p>telefon: 111222333</p>
    </div>

    <footer>
        <p>Stronę wykonał: Bartosz Nikitiuk</p>
    </footer>

</body>
</html>
<?php
mysqli_close($polaczenie);
?>