<?php
// Connexion à la base de données
try {
    $db = new PDO("mysql:host=localhost;dbname=etrs403;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données");
}

// On choisit par défaut d'afficher 50 valeurs sur les graphiques
$choixGraphique = "50";

// Si l'utilisateur choisit un autre nombre de valeurs, on le récupère dans l'URL
if (isset($_GET["nb"])) {
    $choixGraphique = $_GET["nb"];
}

// On vérifie que le choix est valide
if ($choixGraphique != "20" && $choixGraphique != "50" && $choixGraphique != "100" && $choixGraphique != "all") {
    $choixGraphique = "50";
}

// Récupération de la dernière mesure enregistrée
$derniereMesure = $db->query("
    SELECT *
    FROM mesures_capteur
    ORDER BY id_mesures DESC
    LIMIT 1
")->fetch();

// Récupération des statistiques générales
$stats = $db->query("
    SELECT 
        COUNT(*) AS nb_mesures,
        MIN(temperature) AS temp_min,
        MAX(temperature) AS temp_max,
        ROUND(AVG(temperature), 1) AS temp_moy,
        MIN(humidite) AS hum_min,
        MAX(humidite) AS hum_max,
        ROUND(AVG(humidite), 1) AS hum_moy
    FROM mesures_capteur
")->fetch();

// Récupération des mesures utilisées pour les graphiques
if ($choixGraphique == "all") {
    // Si l'utilisateur choisit "toutes les valeurs", on récupère toutes les mesures
    $mesuresGraphique = $db->query("
        SELECT *
        FROM mesures_capteur
        ORDER BY id_mesures ASC
    ")->fetchAll();
} else {
    // Sinon on récupère seulement les dernières valeurs choisies
    $limite = (int)$choixGraphique;

    $mesuresGraphique = $db->query("
        SELECT *
        FROM mesures_capteur
        ORDER BY id_mesures DESC
        LIMIT $limite
    ")->fetchAll();

    // On remet les valeurs dans l'ordre chronologique pour le graphique
    $mesuresGraphique = array_reverse($mesuresGraphique);
}

// Récupération des 5 dernières mesures pour le petit tableau
$dernieresMesures = $db->query("
    SELECT *
    FROM mesures_capteur
    ORDER BY id_mesures DESC
    LIMIT 5
")->fetchAll();

// Message par défaut pour le confort intérieur
$messageConfort = "Aucune mesure disponible pour calculer le confort intérieur.";
$classeConfort = "normal";

// Si une mesure existe, on peut calculer le confort intérieur
if ($derniereMesure) {
    $temperatureActuelle = (float)$derniereMesure["temperature"];
    $humiditeActuelle = (float)$derniereMesure["humidite"];

    // On considère que le confort est bon entre 19 et 24°C et entre 40 et 60% d'humidité
    if ($temperatureActuelle >= 19 && $temperatureActuelle <= 24 && $humiditeActuelle >= 40 && $humiditeActuelle <= 60) {
        $messageConfort = "Les conditions intérieures sont confortables.";
        $classeConfort = "normal";
    } elseif ($temperatureActuelle < 19) {
        $messageConfort = "La température intérieure est un peu basse.";
        $classeConfort = "froid";
    } elseif ($temperatureActuelle > 24) {
        $messageConfort = "La température intérieure est élevée.";
        $classeConfort = "chaud";
    } elseif ($humiditeActuelle < 40) {
        $messageConfort = "L’air intérieur est plutôt sec.";
        $classeConfort = "sec";
    } elseif ($humiditeActuelle > 60) {
        $messageConfort = "L’air intérieur est plutôt humide.";
        $classeConfort = "humide";
    }
}

// Récupération du mois actuel
$moisActuel = date("n");

// Tableau pour afficher le nom du mois en français
$nomsMois = [
    1 => "Janvier",
    2 => "Février",
    3 => "Mars",
    4 => "Avril",
    5 => "Mai",
    6 => "Juin",
    7 => "Juillet",
    8 => "Août",
    9 => "Septembre",
    10 => "Octobre",
    11 => "Novembre",
    12 => "Décembre"
];

// Récupération de la normale de saison du mois actuel
$requeteNormale = $db->prepare("
    SELECT normale
    FROM normales_saison
    WHERE mois = ?
");
$requeteNormale->execute([$moisActuel]);
$resultatNormale = $requeteNormale->fetch();

$normale = null;

// Si une normale existe pour le mois, on la stocke
if ($resultatNormale) {
    $normale = $resultatNormale["normale"];
}

// Calcul de la température moyenne mesurée pendant le mois actuel
$requeteMoyenne = $db->prepare("
    SELECT ROUND(AVG(temperature), 1) AS moyenne
    FROM mesures_capteur
    WHERE MONTH(date_mesure) = ?
");
$requeteMoyenne->execute([$moisActuel]);
$resultatMoyenne = $requeteMoyenne->fetch();

$moyenneMois = $resultatMoyenne["moyenne"];

// Variables pour la comparaison avec les normales de saison
$ecart = null;
$messageComparaison = "";
$classeComparaison = "";

// Si on a une normale et une moyenne, on peut comparer les deux
if ($normale != null && $moyenneMois != null) {
    $ecart = round($moyenneMois - $normale, 1);

    if ($ecart > 1) {
        $messageComparaison = "La température intérieure moyenne est supérieure à la normale extérieure du mois.";
        $classeComparaison = "chaud";
    } elseif ($ecart < -1) {
        $messageComparaison = "La température intérieure moyenne est inférieure à la normale extérieure du mois.";
        $classeComparaison = "froid";
    } else {
        $messageComparaison = "La température intérieure moyenne est proche de la normale extérieure du mois.";
        $classeComparaison = "normal";
    }
}

// Préparation des tableaux qui seront envoyés au fichier JavaScript
$heures = [];
$temperatures = [];
$humidites = [];

// On remplit les tableaux avec les données de la base
foreach ($mesuresGraphique as $mesure) {
    $heures[] = $mesure["date_mesure"] . " " . $mesure["heure_mesure"];
    $temperatures[] = $mesure["temperature"];
    $humidites[] = $mesure["humidite"];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Station météo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <div>
        <h1>Station météo</h1>
        <p>Suivi de la température et de l’humidité</p>
    </div>

    <nav>
        <a href="affichage.php" class="active">Accueil</a>
        <a href="historique.php">Historique</a>
    </nav>
</header>

<main>

    <section class="bloc titre">
        <div>
            <h2>Tableau de bord</h2>
            <p>Affichage des mesures récupérées par le capteur DHT22.</p>
        </div>

        <button onclick="location.reload()">Actualiser</button>
    </section>

    <!-- Affichage de la dernière mesure -->
    <?php if ($derniereMesure) { ?>
        <section class="cartes">
            <div class="carte">
                <p>Température actuelle</p>
                <h2><?php echo $derniereMesure["temperature"]; ?> °C</h2>
            </div>

            <div class="carte">
                <p>Humidité actuelle</p>
                <h2><?php echo $derniereMesure["humidite"]; ?> %</h2>
            </div>

            <div class="carte">
                <p>Dernier relevé</p>
                <h2><?php echo $derniereMesure["heure_mesure"]; ?></h2>
                <span><?php echo $derniereMesure["date_mesure"]; ?></span>
            </div>
        </section>
    <?php } else { ?>
        <section class="bloc">
            <h2>Aucune mesure disponible</h2>
            <p>Les mesures apparaîtront ici quand le capteur enverra des données.</p>
        </section>
    <?php } ?>

    <!-- Bloc de confort intérieur -->
    <section class="bloc">
        <h2>Indication de confort intérieur</h2>
        <p class="info">
            Cette indication est basée sur la température et l’humidité actuelles mesurées en intérieur.
        </p>

        <div class="confort <?php echo $classeConfort; ?>">
            <h3><?php echo $messageConfort; ?></h3>
        </div>
    </section>

    <!-- Bloc des statistiques générales -->
    <section class="bloc">
        <h2>Résumé des mesures</h2>

        <div class="stats">
            <div>
                <p>Nombre de mesures</p>
                <h3><?php echo $stats["nb_mesures"]; ?></h3>
            </div>

            <div>
                <p>Température min.</p>
                <h3><?php echo $stats["temp_min"]; ?> °C</h3>
            </div>

            <div>
                <p>Température max.</p>
                <h3><?php echo $stats["temp_max"]; ?> °C</h3>
            </div>

            <div>
                <p>Température moy.</p>
                <h3><?php echo $stats["temp_moy"]; ?> °C</h3>
            </div>

            <div>
                <p>Humidité min.</p>
                <h3><?php echo $stats["hum_min"]; ?> %</h3>
            </div>

            <div>
                <p>Humidité max.</p>
                <h3><?php echo $stats["hum_max"]; ?> %</h3>
            </div>

            <div>
                <p>Humidité moy.</p>
                <h3><?php echo $stats["hum_moy"]; ?> %</h3>
            </div>
        </div>
    </section>

    <!-- Comparaison avec les normales de saison -->
    <section class="bloc">
        <h2>Comparaison indicative avec les normales de saison</h2>
        <p class="info">
            Les mesures ont été prises en intérieur. Cette comparaison sert donc seulement de repère
            avec la température moyenne extérieure du mois.
        </p>

        <?php if ($normale != null && $moyenneMois != null) { ?>
            <div class="comparaison">
                <div>
                    <p>Mois actuel</p>
                    <h3><?php echo $nomsMois[$moisActuel]; ?></h3>
                </div>

                <div>
                    <p>Normale extérieure</p>
                    <h3><?php echo $normale; ?> °C</h3>
                </div>

                <div>
                    <p>Moyenne mesurée</p>
                    <h3><?php echo $moyenneMois; ?> °C</h3>
                </div>

                <div class="<?php echo $classeComparaison; ?>">
                    <p>Écart</p>
                    <h3>
                        <?php
                        if ($ecart > 0) {
                            echo "+";
                        }
                        echo $ecart;
                        ?> °C
                    </h3>
                </div>
            </div>

            <p class="message <?php echo $classeComparaison; ?>">
                <?php echo $messageComparaison; ?>
            </p>
        <?php } else { ?>
            <p>Aucune comparaison possible pour le moment.</p>
        <?php } ?>
    </section>

    <!-- Choix du nombre de valeurs affichées dans les graphiques -->
    <section class="bloc">
        <div class="haut-bloc">
            <div>
                <h2>Graphiques</h2>
                <p class="info">Choisissez le nombre de dernières valeurs à afficher.</p>
            </div>

            <form method="get" class="choix-graphique">
                <select name="nb" onchange="this.form.submit()">
                    <option value="20" <?php if ($choixGraphique == "20") echo "selected"; ?>>20 dernières valeurs</option>
                    <option value="50" <?php if ($choixGraphique == "50") echo "selected"; ?>>50 dernières valeurs</option>
                    <option value="100" <?php if ($choixGraphique == "100") echo "selected"; ?>>100 dernières valeurs</option>
                    <option value="all" <?php if ($choixGraphique == "all") echo "selected"; ?>>Toutes les valeurs</option>
                </select>
            </form>
        </div>
    </section>

    <!-- Graphique de température -->
    <section class="bloc">
        <h2>Évolution de la température</h2>

        <div class="graphique">
            <canvas id="graphTemp"></canvas>
        </div>
    </section>

    <!-- Graphique d'humidité -->
    <section class="bloc">
        <h2>Évolution de l’humidité</h2>

        <div class="graphique">
            <canvas id="graphHum"></canvas>
        </div>
    </section>

    <!-- Petit tableau des dernières mesures -->
    <section class="bloc">
        <div class="haut-bloc">
            <h2>Dernières mesures</h2>
            <a href="historique.php" class="bouton">Voir tout l’historique</a>
        </div>

        <table>
            <tr>
                <th>Température</th>
                <th>Humidité</th>
                <th>Date</th>
                <th>Heure</th>
            </tr>

            <?php foreach ($dernieresMesures as $mesure) { ?>
                <tr>
                    <td><?php echo $mesure["temperature"]; ?> °C</td>
                    <td><?php echo $mesure["humidite"]; ?> %</td>
                    <td><?php echo $mesure["date_mesure"]; ?></td>
                    <td><?php echo $mesure["heure_mesure"]; ?></td>
                </tr>
            <?php } ?>
        </table>
    </section>

</main>

<!-- On envoie les données PHP vers JavaScript pour créer les graphiques -->
<script>
    let donneesStation = {
        heures: <?php echo json_encode($heures); ?>,
        temperatures: <?php echo json_encode($temperatures); ?>,
        humidites: <?php echo json_encode($humidites); ?>
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="graphique.js"></script>

</body>
</html>