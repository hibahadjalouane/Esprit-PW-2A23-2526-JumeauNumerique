<?php
// confirmation_inscription.php
require_once '../../config.php'; // Ajustez le chemin vers votre fichier de config DB

// 1. Récupération des données passées dans le lien de l'email
$email  = $_GET['email'] ?? '';
$nom    = $_GET['nom']   ?? '';
$prenom = $_GET['prenom'] ?? '';
$role   = (int)($_GET['role']  ?? 3); // 2 pour Admin, 3 pour Médecin

$roleLabel = ($role === 2) ? "Administrateur" : "Médecin";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Finalisation de l'inscription</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 50px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { color: #2c3e50; border-bottom: 2px solid #27ae60; padding-bottom: 10px; }
        .info { background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #27ae60; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #219150; }
    </style>
</head>
<body>

<div class="card">
    <h2>Finaliser le profil : <?php echo $roleLabel; ?></h2>
    
    <div class="info">
        <strong>Candidat :</strong> <?php echo htmlspecialchars($prenom . " " . $nom); ?><br>
        <strong>Email :</strong> <?php echo htmlspecialchars($email); ?>
    </div>

    <form action="user_crud.php?action=signup" method="POST">
        <!-- Champs cachés pour transmettre les infos à user_crud.php -->
        <input type="hidden" name="nom" value="<?php echo htmlspecialchars($nom); ?>">
        <input type="hidden" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="role" value="<?php echo $role; ?>">

        <div class="form-group">
            <label>Numéro CIN :</label>
            <input type="text" name="cin" required placeholder="8 chiffres" maxlength="8" pattern="[0-9]{8}">
        </div>

        <?php if ($role === 3): // Si c'est un médecin, on demande le service ?>
        <div class="form-group">
            <label>Service médical :</label>
            <select name="service" required>
                <option value="Cardiologie">Cardiologie</option>
                <option value="Radiologie">Radiologie</option>
                <option value="Urgences">Urgences</option>
                <option value="Généraliste">Généraliste</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Définir le mot de passe :</label>
            <input type="password" name="mot_de_passe" required placeholder="Mot de passe provisoire">
        </div>

        <button type="submit">Enregistrer dans la base de données</button>
    </form>
</div>

</body>
</html>