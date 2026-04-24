<?php
// Connexion à la base de données
try {
    $db = new PDO("mysql:host=localhost;dbname=etrs403;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données");
}

// Variable utilisée pour le filtre par date
$dateFiltre = "";

// Si une date est choisie, on la récupère
if (isset($_GET["date"])) {
    $dateFiltre = $_GET["date"];
}

// Si une date est sélectionnée, on affiche seulement les mesures de cette date
if ($dateFiltre != "") {
    $requete = $db->prepare("
        SELECT *
        FROM mesures_capteur
        WHERE date_mesure = ?
        ORDER BY id_mesures DESC
    ");
    $requete->execute([$dateFiltre]);
    $mesures = $requete->fetchAll();
} else {
    // Sinon on affiche toutes les mesures
    $mesures = $db->query("
        SELECT *
        FROM mesures_capteur
        ORDER BY id_mesures DESC
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <div>
        <h1>Historique</h1>
        <p>Liste des mesures enregistrées</p>
    </div>

    <nav>
        <a href="affichage.php">Accueil</a>
        <a href="historique.php" class="active">Historique</a>
    </nav>
</header>

<main>

    <section class="bloc titre">
        <div>
            <h2>Historique des mesures</h2>
            <p>Cette page affiche les mesures stockées dans la base de données.</p>
        </div>

        <a href="affichage.php" class="bouton">Retour à l’accueil</a>
    </section>

    <!-- Formulaire permettant de filtrer les mesures par date -->
    <section class="bloc">
        <h2>Filtrer les mesures</h2>

        <form method="get" class="filtre">
            <label for="date">Choisir une date :</label>

            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($dateFiltre); ?>">

            <button type="submit">Filtrer</button>

            <a href="historique.php" class="bouton gris">Tout afficher</a>
        </form>
    </section>

    <!-- Tableau qui affiche les mesures -->
    <section class="bloc">
        <div class="haut-bloc">
            <h2>Mesures enregistrées</h2>

            <?php if ($dateFiltre != "") { ?>
                <p><?php echo count($mesures); ?> mesure(s) pour le <?php echo htmlspecialchars($dateFiltre); ?></p>
            <?php } else { ?>
                <p><?php echo count($mesures); ?> mesure(s)</p>
            <?php } ?>
        </div>

        <?php if (count($mesures) > 0) { ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Température</th>
                    <th>Humidité</th>
                    <th>Date</th>
                    <th>Heure</th>
                </tr>

                <?php foreach ($mesures as $mesure) { ?>
                    <tr>
                        <td><?php echo $mesure["id_mesures"]; ?></td>
                        <td><?php echo $mesure["temperature"]; ?> °C</td>
                        <td><?php echo $mesure["humidite"]; ?> %</td>
                        <td><?php echo $mesure["date_mesure"]; ?></td>
                        <td><?php echo $mesure["heure_mesure"]; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p>Aucune mesure trouvée pour cette date.</p>
        <?php } ?>
    </section>

    <!-- Zone de suppression des données -->
    <section class="bloc danger-zone">
        <div>
            <h2>Suppression des données</h2>
            <p>Ce bouton permet de supprimer toutes les mesures enregistrées.</p>
        </div>

        <form action="supprime.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer toutes les mesures ?');">
            <button type="submit" class="danger">Supprimer toutes les mesures</button>
        </form>
    </section>

</main>

</body>
</html>