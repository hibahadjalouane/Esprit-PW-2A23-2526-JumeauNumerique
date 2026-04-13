<?php
require_once "../../config.php";
require_once "../../Model/User.php";

$pdo = Config::getConnexion();
$model = new User($pdo);

$users = $model->getAll();
$roles = $model->getRoles();

/* 🔎 SEARCH */
$search = $_GET['search'] ?? "";

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT * FROM user 
        WHERE id_user LIKE ?
    ");

    $stmt->execute(["%$search%"]);
    $users = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Users</title>
    <link rel="stylesheet" href="../../assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>🏥 JumeauNum</h2>

    <a href="#"><i class="fa fa-home"></i> Dashboard</a>
    <a href="#"><i class="fa fa-user"></i> Utilisateurs</a>
    <a href="#"><i class="fa fa-calendar"></i> Rendez-vous</a>
    <a href="#"><i class="fa fa-file"></i> Factures</a>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <h1>Gestion des Utilisateurs</h1>
    </div>

    <!-- SEARCH -->
    <form method="GET" class="search-box">
        <input type="text" name="search" placeholder="Rechercher utilisateur...">
        <button>Rechercher</button>
    </form>

    <!-- CARDS -->
    <div class="cards">
        <div class="card green">👤 <?= count($users) ?> Utilisateurs</div>
    </div>

    <div class="content">

        <!-- TABLE -->
        <div class="table-box">

            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>CIN</th>
                    <th>Service</th>
                    <th>Statut</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>

                <?php foreach ($users as $u): ?>
                <tr>

                    <form method="POST" action="../../Controller/update.php"
      onsubmit="return validateUserForm(this)">

                        <td>
    <?= htmlspecialchars($u['id_user']) ?>
    <input type="hidden" name="id_user"
           value="<?= htmlspecialchars($u['id_user']) ?>">
</td>

<td>
    <input name="nom"
           value="<?= htmlspecialchars($u['nom']) ?>"
           oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ\s]/g,'')"
           required>
</td>

<td>
    <input name="prenom"
           value="<?= htmlspecialchars($u['prenom']) ?>"
           oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ\s]/g,'')"
           required>
</td>

<td>
    <input type="email"
           name="email"
           value="<?= htmlspecialchars($u['email']) ?>"
           required>
</td>

<td>
    <input name="cin"
           value="<?= htmlspecialchars($u['cin']) ?>"
           oninput="this.value=this.value.replace(/[^0-9]/g,'')"
           required>
</td>

<td>
    <input name="service"
           value="<?= htmlspecialchars($u['service']) ?>"
           required>
</td>

                        <td>
                            <select name="statut_cmpt">
                                <option value="actif" <?= $u['statut_cmpt']=='actif'?'selected':'' ?>>Actif</option>
                                <option value="bloque" <?= $u['statut_cmpt']=='bloque'?'selected':'' ?>>Bloqué</option>
                            </select>
                        </td>

                        <td>
                            <select name="id_role">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id_role'] ?>"
                                        <?= $r['id_role'] == $u['id_role'] ? 'selected' : '' ?>>
                                        <?= $r['nom_role'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <!-- SAVE -->
                            <button class="btn-save">💾</button>

                            <!-- DELETE -->
                            <a class="btn-del"
                               href="../../Controller/delete.php?id=<?= $u['id_user'] ?>">
                                ❌
                            </a>

                            <!-- EDIT ICON (même fonction update) -->
                            <a class="btn-edit" href="edit.php?id=<?= $u['id_user'] ?>">
    ✏️
</a>
                        </td>

                    </form>

                </tr>
                <?php endforeach; ?>

            </table>

        </div>

        <!-- RIGHT PANEL (ADD USER) -->
        <div class="right-panel">

            <h3>Ajouter utilisateur</h3>

            <form method="POST" action="../../Controller/add.php"
      onsubmit="return validateUserForm(this)">
                <input type="number"
       name="id_user"
       placeholder="ID utilisateur"
       min="1"
       step="1"
       required
       oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                <input name="nom" placeholder="Nom"
       oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ\s]/g,'')">

<input name="prenom" placeholder="Prénom"
       oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿ\s]/g,'')">
                <input name="email" placeholder="Email">
                <input name="mot_de_passe" type="password" placeholder="Mot de passe">
                <input name="cin" placeholder="CIN"
       oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                <input name="service" placeholder="Service">

                <select name="statut_cmpt">
                    <option value="actif">Actif</option>
                    <option value="bloque">Bloqué</option>
                </select>

                <select name="id_role">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id_role'] ?>">
                            <?= $r['nom_role'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button>+ Ajouter</button>

            </form>

        </div>

    </div>
</div>
<script>
function validateUserForm(form) {
    let nom = form.nom.value.trim();
    let prenom = form.prenom.value.trim();
    let email = form.email.value.trim();
    let cin = form.cin.value.trim();
    let service = form.service.value.trim();
    let statut = form.statut_cmpt.value;
    let role = form.id_role.value;

    // Validation du nom
    if (!/^[A-Za-zÀ-ÿ\s]+$/.test(nom)) {
        alert("Nom invalide (lettres uniquement).");
        return false;
    }

    // Validation du prénom
    if (!/^[A-Za-zÀ-ÿ\s]+$/.test(prenom)) {
        alert("Prénom invalide (lettres uniquement).");
        return false;
    }

    // Validation de l'email
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert("Adresse email invalide.");
        return false;
    }

    // Validation du CIN
    if (!/^[0-9]{6,20}$/.test(cin)) {
        alert("CIN invalide (chiffres uniquement).");
        return false;
    }

    // Validation du service
    if (service === "") {
        alert("Le service est obligatoire.");
        return false;
    }

    // Validation du statut
    if (statut === "") {
        alert("Le statut du compte est obligatoire.");
        return false;
    }

    // Validation du rôle
    if (role === "") {
        alert("Le rôle est obligatoire.");
        return false;
    }
    if (form.id_user) {
        let id = form.id_user.value.trim();

        if (!/^[0-9]+$/.test(id) || parseInt(id) <= 0) {
            alert("L'ID utilisateur doit être un nombre entier positif.");
            return false;
        }
    }

    return true;
}
</script>
</body>
</html>
</body>
</html>