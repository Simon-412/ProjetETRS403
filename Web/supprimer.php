<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=ETRS403;charset=utf8", "root", "");

    $db->exec("DELETE FROM mesures_capteur");

    header("Location: affichage.php");
} catch (PDOException $e) {
    echo "Erreur";
}
?>