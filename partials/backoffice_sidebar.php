<?php
$role = (int)($user['id_role'] ?? $_SESSION['id_role'] ?? 0);
$current = basename($_SERVER['PHP_SELF']);
$base = '/Esprit-PW-2A23-2526-JumeauNumerique';
?>

<aside>
  <a href="<?= $base ?>/bord.php" class="nav-item <?= $current === 'bord.php' ? 'active' : '' ?>">
    Tableau de bord
  </a>

  <?php if ($role === 2 || $role === 4): ?>
    <a href="<?= $base ?>/gestion_ressource/vue/backoffice/ressource.php" class="nav-item <?= $current === 'ressource.php' ? 'active' : '' ?>">
      <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
      Ressources
    </a>

    <a href="<?= $base ?>/gestion_admission/vue/backoffice/admission.php" class="nav-item <?= $current === 'admission.php' ? 'active' : '' ?>">
      Admissions
    </a>

    <a href="<?= $base ?>/gestion_paiement/vue/backoffice/facture.php" class="nav-item <?= $current === 'facture.php' ? 'active' : '' ?>">
      Les factures
    </a>

    <a href="<?= $base ?>/gestion_user/vue/backoffice/supadmin.php" class="nav-item <?= $current === 'supadmin.php' ? 'active' : '' ?>">
      Les médecins
    </a>
  <?php endif; ?>

  <?php if ($role === 3 || $role === 4): ?>
    <a href="<?= $base ?>/gestion_rdv/vue/backoffice/rdv_page.php" class="nav-item <?= $current === 'rdv_page.php' ? 'active' : '' ?>">
      Les rendez-vous
    </a>

    <a href="<?= $base ?>/gestion_dossier/vue/backoffice/dossier.php" class="nav-item <?= $current === 'dossier.php' ? 'active' : '' ?>">
      Dossiers médicaux
    </a>
  <?php endif; ?>

  <a href="<?= $base ?>/gestion_user/vue/backoffice/profil.php" class="nav-item <?= $current === 'profil.php' ? 'active' : '' ?>">
    Paramètres
  </a>

  <a href="<?= $base ?>/gestion_user/controleur/frontoffice/logout.php" class="nav-item">
    Déconnexion
  </a>
</aside>