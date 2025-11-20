<?php

// CONNEXION À LA BASE DE DONNÉES

try {
    $dbh = new PDO(
        "mysql:host=localhost;dbname=airbnb.sql;charset=utf8",
        "root",
        ""
    );
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die( $e->getMessage());
}


// PAGINATION

$page  = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;


// TRI (colonnes qui existent vraiment dans la table listings)

$triages = ["name", "neighbourhood_group_cleansed", "price", "host_name"];
$tri = (isset($_GET["tri"]) && in_array($_GET["tri"], $triages)) ? $_GET["tri"] : "name";


// AJOUT D'UN RESULTAT

if (!empty($_POST)) {

    $name   = $_POST["name"];
    $ville  = $_POST["ville"];
    $prix   = $_POST["prix"];
    $image  = $_POST["image"];
    $host   = $_POST["host"];

    $sql = $dbh->prepare("
        INSERT INTO listings (name, neighbourhood_group_cleansed, price, picture_url, host_name)
        VALUES (?, ?, ?, ?, ?)
    ");
    $sql->execute([$name, $ville, $prix, $image, $host]);

    // Redirection pour éviter renvoi du formulaire
    header("Location: config.php?ajout=ok&tri=$tri&page=$page");
    exit();
}


// REQUÊTE LISTE + PAGINATION

$total = $dbh->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $dbh->prepare("
    SELECT id, name, picture_url, host_name, price, neighbourhood_group_cleansed
    FROM listings
    ORDER BY $tri
    LIMIT :lim OFFSET :off
");
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);
$stmt->bindValue(":off", $offset, PDO::PARAM_INT);
$stmt->execute();
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Airbnb </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
            background-color: #f7f7f7;
        }
        h1 {
            color: #FF5A5F;
            text-align: center;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #FF5A5F;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .listing {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .listing img {
            width: 100%;
            max-width: 300px;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .listing strong {
            font-size: 18px;
            color: #333;
        }
        .pagination {
            margin: 20px 0;
            text-align: center;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 5px;
            background: #FF5A5F;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .pagination a:hover {
            background: #E04E53;
        }
        .pagination strong {
            padding: 8px 12px;
            margin: 0 5px;
            background: red;
            color: white;
            border-radius: 4px;
        }
        form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #FF5A5F;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 16px;
        }
        button:hover {
            background: #E04E53;
        }
        .sort-form {
            margin-bottom: 20px;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 30px 0;
        }
    </style>
</head>
<body>

<h1>AIRBNB</h1>

<?php if (isset($_GET["ajout"])): ?>
    <div class="success">Résultat ajouté avec succès !</div>
<?php endif; ?>

<hr>

<!--   SECTION : LISTE DES LOGEMENTS   -->
<h2> Liste des logements</h2>

<!-- FORMULAIRE DE TRI -->
<form method="GET" class="sort-form">
    <label for="tri">Trier par :</label>
    <select name="tri" id="tri" onchange="this.form.submit()">
        <option value="name" <?= $tri=="name" ? "selected" : "" ?>>Nom</option>
        <option value="neighbourhood_group_cleansed" <?= $tri=="neighbourhood_group_cleansed" ? "selected" : "" ?>>Ville</option>
        <option value="price" <?= $tri=="price" ? "selected" : "" ?>>Prix</option>
        <option value="host_name" <?= $tri=="host_name" ? "selected" : "" ?>>Propriétaire</option>
    </select>
    <!-- Garder la page courante lors du tri -->
    <input type="hidden" name="page" value="<?= $page ?>">
</form>

<!-- AFFICHAGE DES LOGEMENTS -->
<?php if (count($listings) > 0): ?>
    <?php foreach ($listings as $l): ?>
        <div class="listing">
            <?php if (!empty($l['picture_url'])): ?>
                <img src="<?= htmlspecialchars($l['picture_url']) ?>" alt="<?= htmlspecialchars($l['name']) ?>">
            <?php endif; ?>
            <strong><?= htmlspecialchars($l['name']) ?></strong><br>
             Ville : <?= htmlspecialchars($l['neighbourhood_group_cleansed']) ?><br>
             Prix : <?= htmlspecialchars($l['price']) ?> € / nuit 
            (<?= htmlspecialchars($l['price'] * 3) ?> € pour 3 nuits)<br>
            Propriétaire : <?= htmlspecialchars($l['host_name']) ?><br>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Aucun logement trouvé.</p>
<?php endif; ?>

<!-- PAGINATION -->
<div class="pagination">
    <?php
    // Lien précédent
    if ($page > 1) {
        echo "<a href='?page=" . ($page - 1) . "&tri=$tri'>← Précédent</a> ";
    }

    // Numéros de pages
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo "<strong>$i</strong> ";
        } else {
            echo "<a href='?page=$i&tri=$tri'>$i</a> ";
        }
    }

    // Lien suivant
    if ($page < $totalPages) {
        echo "<a href='?page=" . ($page + 1) . "&tri=$tri'>Suivant →</a>";
    }
    ?>
</div>

<hr>

<!--  SECTION : AJOUTER UN RESULTAT-->
<h2>Ajouter une logement </h2>

<form method="POST">

    <label for="name">Nom du logement :</label>
    <input type="text" name="name" id="name" placeholder="Ex: Studio cosy à Paris" required>

    <label for="ville">Ville / Pays :</label>
    <input type="text" name="ville" id="ville" placeholder="Ex: Paris, France" required>

    <label for="prix">Prix par nuit (€) :</label>
    <input type="number" name="prix" id="prix" placeholder="Ex: 80" required>
    <label for="image">URL de l'image :</label>
    <input type="text" name="image" id="image" placeholder="https://exemple.com/image.jpg" required>

    <label for="host">Nom du propriétaire :</label>
    <input type="text" name="host" id="host" placeholder="Ex: Alice" required>


    <button type="submit">Ajouter un logement</button>
</form>





</body>
</html>
