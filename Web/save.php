<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $temperature = isset($_POST['t']) ? floatval($_POST['t']) : null;
    $humidite = isset($_POST['h']) ? floatval($_POST['h']) : null;

    if ($temperature === null || $humidite === null) {
        echo "missing";
        exit;
    }

    $date = date('Y-m-d');
    $heure = date('H:i:s');

    try {
        $db = new PDO("mysql:host=localhost;dbname=ETRS403;charset=utf8", "root", "");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $req = $db->prepare("INSERT INTO mesures_capteur 
            (temperature, humidite, date_mesure, heure_mesure) 
            VALUES (?, ?, ?, ?)");

        $req->execute([$temperature, $humidite, $date, $heure]);

        echo "ok";

    } catch (PDOException $e) {
        echo "error";
    }

} else {
    echo "invalid";
}
?>