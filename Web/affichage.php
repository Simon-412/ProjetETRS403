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

    // dernière mesure
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
        echo "<span class='heure'>" . $row['heure_mesure'] . "</span> ";
        echo "<span class='temp'>" . $row['temperature'] . "</span> ";
        echo "<span class='hum'>" . $row['humidite'] . "</span>";
        echo "</div>";
    }


    $graphData = $db->query("SELECT * FROM mesures_capteur ORDER BY id_mesures DESC LIMIT 500");

} catch (PDOException $e) {
    echo "Erreur";
}
?>

<br>

<button onclick="location.reload()">Actualiser</button>

<form action="supprime.php" method="post" onsubmit="return confirm('Supprimer toutes les données');">
    <button type="submit">Supprimer</button>
</form>

<h2>Graphique</h2>

<canvas id="graph" width="400" height="200"></canvas>

<!-- données cachées -->
<div id="graphData" style="display:none;">
<?php
foreach ($graphData as $row) {
    echo "<span class='g_heure'>" . $row['heure_mesure'] . "</span>";
    echo "<span class='g_temp'>" . $row['temperature'] . "</span>";
    echo "<span class='g_hum'>" . $row['humidite'] . "</span>";
}
?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="graphique.js"></script>

<script>
window.onload = function() {
    afficherGraphique();
}
</script>

</body>
</html>