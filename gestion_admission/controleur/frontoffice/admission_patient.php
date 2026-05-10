<?php
/**
 * admission_patient.php
 * Controleur frontoffice : recupere les admissions du patient connecte
 *
 * Placement : gestion_admission/controleur/frontoffice/admission_patient.php
 */

require_once __DIR__ . '/../../../inc_session.php';
checkSession([1]);

require_once __DIR__ . '/../../modele/config.php';

$user     = getCurrentUser();
$idPatient = (int) $user['id'];

$db = config::getConnexion();

// Admission active : la plus recente dont la date_arrive_relle est <= maintenant
// On considere "active" l'admission la plus recente du patient
$sqlActive = "SELECT
                a.id_admission,
                a.date_arrive_relle,
                a.mode_entree,
                a.id_ticket,
                a.id_salle,
                s.numero   AS salle_numero,
                s.statut   AS salle_statut,
                t.statut   AS ticket_statut
              FROM admission a
              LEFT JOIN salle      s ON a.id_salle  = s.id_salle
              LEFT JOIN ticket_num t ON a.id_ticket = t.id_ticket
              WHERE a.id_patient = :id_patient
              ORDER BY a.date_arrive_relle DESC
              LIMIT 1";

$qActive = $db->prepare($sqlActive);
$qActive->execute([':id_patient' => $idPatient]);
$admissionActive = $qActive->fetch(PDO::FETCH_ASSOC);

// Historique : toutes les admissions du patient
$sqlHistorique = "SELECT
                    a.id_admission,
                    a.date_arrive_relle,
                    a.mode_entree,
                    a.id_salle,
                    s.numero AS salle_numero
                  FROM admission a
                  LEFT JOIN salle s ON a.id_salle = s.id_salle
                  WHERE a.id_patient = :id_patient
                  ORDER BY a.date_arrive_relle DESC";

$qHist = $db->prepare($sqlHistorique);
$qHist->execute([':id_patient' => $idPatient]);
$historique = $qHist->fetchAll(PDO::FETCH_ASSOC);

// Stats pour le suivi de recuperation :
// nombre total d'admissions, modes d'entree, salle actuelle
$totalAdmissions = count($historique);

$stats = [
    'total'     => $totalAdmissions,
    'urgence'   => 0,
    'normal'    => 0,
    'transfert' => 0,
    'autre'     => 0,
];

foreach ($historique as $h) {
    $mode = strtolower(trim($h['mode_entree']));
    if (isset($stats[$mode])) {
        $stats[$mode]++;
    } else {
        $stats['autre']++;
    }
}
?>