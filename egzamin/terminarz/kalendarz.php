<?php
$polaczenie = mysqli_connect('192.168.50.179', 'root', '', 'terminarz');
$zapytanie1 = "SELECT DISTINCT wpis from zadania where dataZadania = '2020-07-01' or dataZadania = '2020-07-07' or wpis <> ' ';";
$wynik = mysqli_query($polaczenie, $zapytanie1);

$zapytanie2 = "SELECT dataZadania, wpis from zadania where miesiac='lipiec';";
$wynik2 = mysqli_query($polaczenie,$zapytanie2);

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zadania na lipiec</title>
    <link rel="stylesheet" href="styl6.css">
</head>
<body>
    <div id="baner1">
        <img src="logo1.png" alt="lipiec">
    </div>

    <div id="baner2">
        <h1>TERMINARZ</h1>
        <p>Najbliższe zadania:
        <?php
        while ($wiersz = mysqli_fetch_array($wynik)) {
            echo " " . $wiersz['wpis'] . ';';
        }

        ?>
        </p>
    </div>

    <main>
        <?php
            while ($wiersz2 = mysqli_fetch_array($wynik2)) {
                echo '<div class="blok">';
                echo "<h6>". $wiersz2['dataZadania'] . "</h6>";
                echo "<p>". $wiersz2['wpis'] . "</p>";
                echo "</div>";
            }
        ?>
    </main>

    <footer>
        <a href="sierpien.html">Terminarz na sierpień</a>
        <p>Stonę wykonał: Bartosz Nikitiuk</p>
    </footer>
</body>
</html>
<?php
mysqli_close($polaczenie);
?>