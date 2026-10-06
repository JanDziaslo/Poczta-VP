<?php
$polaczenie = mysqli_connect('localhost', 'root', '' , 'form');

if  (!$polaczenie) {
    die('Błąd połączenia: ' . mysqli_connect_error());
}

mysqli_set_charset($polaczenie, 'utf8mb4');


?>