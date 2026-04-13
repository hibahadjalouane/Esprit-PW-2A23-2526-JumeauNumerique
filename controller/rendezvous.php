<?php
require '../config.php';

class RdvC
{
    // ── ADD ──────────────────────────────────────────────────
    function addRdv($rdv)
    {
        $sql = "INSERT INTO rendez_vous
                VALUES (:id, :dateDemande, :dateRdv, :statut, :typeConsultation, :idPatient, :idCreneau, :idMedecin)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id'              => $rdv->getIdRdv(),
                'dateDemande'     => $rdv->getDateDemande(),
                'dateRdv'         => $rdv->getDateRdv(),
                'statut'          => $rdv->getStatut(),
                'typeConsultation'=> $rdv->getTypeConsultation(),
                'idPatient'       => $rdv->getIdPatient(),
                'idCreneau'       => $rdv->getIdCreneau(),
                'idMedecin'       => $rdv->getIdMedecin(),
            ]);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

    // ── UPDATE ───────────────────────────────────────────────
    function updateRdv($rdv, $id)
    {
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                'UPDATE rendez_vous SET
                    date_demande       = :dateDemande,
                    date_rdv           = :dateRdv,
                    statut             = :statut,
                    type_consultation  = :typeConsultation,
                    id_patient         = :idPatient,
                    id_creneau         = :idCreneau,
                    id_medecin         = :idMedecin
                WHERE id_rdv = :id'
            );
            $query->execute([
                'id'              => $id,
                'dateDemande'     => $rdv->getDateDemande(),
                'dateRdv'         => $rdv->getDateRdv(),
                'statut'          => $rdv->getStatut(),
                'typeConsultation'=> $rdv->getTypeConsultation(),
                'idPatient'       => $rdv->getIdPatient(),
                'idCreneau'       => $rdv->getIdCreneau(),
                'idMedecin'       => $rdv->getIdMedecin(),
            ]);
            echo $query->rowCount() . " records UPDATED successfully <br>";
        } catch (PDOException $e) {
            $e->getMessage();
        }
    }

    // ── DELETE ───────────────────────────────────────────────
    function deleteRdv($ide)
    {
        $sql = "DELETE FROM rendez_vous WHERE id_rdv = :id";
        $db  = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $ide);
        try {
            $req->execute();
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    // ── SELECT ALL ───────────────────────────────────────────
    function getAllRdv()
    {
        $sql = "SELECT * FROM rendez_vous";
        $db  = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll();
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
?>