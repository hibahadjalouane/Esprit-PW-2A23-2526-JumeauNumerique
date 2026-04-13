<?php

require_once "../../config.php";

$pdo = Config::getConnexion();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID utilisateur manquant.");
}

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM user WHERE id_user = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur introuvable.");
}

$roles = $pdo->query("SELECT * FROM role")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modifier Utilisateur</title>
    <link rel="stylesheet" href="../../assets/style.css">

    <style>
        .form-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
        }

        .form-box input,
        .form-box select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .form-box label {
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
            display: block;
        }

        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>

<body>

<div class="main">

<h2>✏️ Modifier utilisateur</h2>

<div class="form-box">

<form method="POST" action="../../Controller/update.php"
      onsubmit="return validateEditForm(this)">
    

    <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">

    <!-- ID visible mais non modifiable -->
    <label>ID Utilisateur</label>
    <input type="text" value="<?= $user['id_user'] ?>" disabled>

    <label>Nom</label>
    <input name="nom" placeholder="Nom"
        value="<?= htmlspecialchars($user['nom'] ?? '') ?>">

    <label>Prénom</label>
    <input name="prenom" placeholder="Prénom"
        value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">

    <label>Email</label>
    <input name="email" placeholder="Email"
        value="<?= htmlspecialchars($user['email'] ?? '') ?>">

    <label>CIN</label>
    <input name="cin" placeholder="CIN"
        value="<?= htmlspecialchars($user['cin'] ?? '') ?>">

    <label>Service</label>
    <input name="service" placeholder="Service"
        value="<?= htmlspecialchars($user['service'] ?? '') ?>">

    <!-- STATUT -->
    <label>Statut compte</label>
    <select name="statut_cmpt">
        <option value="">-- choisir --</option>
        <option value="actif" <?= ($user['statut_cmpt'] ?? '')=='actif'?'selected':'' ?>>Actif</option>
        <option value="bloque" <?= ($user['statut_cmpt'] ?? '')=='bloque'?'selected':'' ?>>Bloqué</option>
    </select>

    <!-- ROLE -->
    <label>Rôle</label>
    <select name="id_role">
        <option value="">-- choisir rôle --</option>
        <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id_role'] ?>"
                <?= ($r['id_role']==$user['id_role'])?'selected':'' ?>>
                <?= $r['nom_role'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="btn" type="submit">💾 Enregistrer</button>

</form>

</div>

</div>
<script>
function validateEditForm(form) {

    let nom = form.nom.value.trim();
    let prenom = form.prenom.value.trim();
    let email = form.email.value.trim();
    let cin = form.cin.value.trim();
    let service = form.service.value.trim();
    let statut = form.statut_cmpt.value.trim();
    let role = form.id_role.value.trim();

    // NOM
    if (nom === "" || !/^[A-Za-zÀ-ÿ\s]+$/.test(nom)) {
        alert("Nom invalide (lettres uniquement)");
        return false;
    }

    // PRÉNOM
    if (prenom === "" || !/^[A-Za-zÀ-ÿ\s]+$/.test(prenom)) {
        alert("Prénom invalide (lettres uniquement)");
        return false;
    }

    // EMAIL
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        alert("Email invalide");
        return false;
    }

    // CIN
    if (cin === "" || !/^[0-9]+$/.test(cin)) {
        alert("CIN doit contenir uniquement des chiffres");
        return false;
    }

    // SERVICE
    if (service === "") {
        alert("Service obligatoire");
        return false;
    }

    // STATUT
    if (statut === "") {
        alert("Statut obligatoire");
        return false;
    }

    // ROLE
    if (role === "") {
        alert("Rôle obligatoire");
        return false;
    }

    return true;
}
</script>
</body>
</html>