<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Station météo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Station météo</h1>

<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=ETRS403;charset=utf8", "root", "");

   
    $last = $db->query("SELECT * FROM mesures_capteur ORDER BY id_mesures DESC LIMIT 1")->fetch();

    if ($last) {
        echo "<div class='box'>";
        echo "<h2>Dernière mesure</h2>";
        echo "<p>Température : " . $last['temperature'] . " °C</p>";
        echo "<p>Humidité : " . $last['humidite'] . " %</p>";
        echo "<p>" . $last['date_mesure'] . " " . $last['heure_mesure'] . "</p>";
        echo "</div>";
    }

  
    echo "<h2>Historique</h2>";

    $data = $db->query("SELECT * FROM mesures_capteur ORDER BY id_mesures DESC LIMIT 10");

    foreach ($data as $row) {
        echo "<div class='ligne'>";
        echo $row['heure_mesure'] . " - " . $row['temperature'] . " °C - " . $row['humidite'] . " %";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "Erreur";
}
?>

<br>

<button onclick="location.reload()">Actualiser</button>

<form action="delete.php" method="post">
    <button type="submit">Vider la base</button>
</form>

</body>
</html>