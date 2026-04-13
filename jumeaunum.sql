-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 10 avr. 2026 à 01:41
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `jumeaunum`
--

-- --------------------------------------------------------

--
-- Structure de la table `admission`
--

CREATE TABLE `admission` (
  `id_admission` varchar(20) NOT NULL,
  `date_arrive_relle` date NOT NULL,
  `mode_entree` varchar(20) NOT NULL,
  `id_ticket` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `consultation`
--

CREATE TABLE `consultation` (
  `id_consultation` varchar(20) NOT NULL,
  `date_consultation` date NOT NULL,
  `motif` varchar(20) NOT NULL,
  `diagnostic` varchar(20) NOT NULL,
  `notes` varchar(20) NOT NULL,
  `id_dossier` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `creneau`
--

CREATE TABLE `creneau` (
  `id_creneau` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_medecin` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `dossier_medical`
--

CREATE TABLE `dossier_medical` (
  `id_dossier` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `date_creation` date NOT NULL,
  `id_patient` varchar(20) NOT NULL,
  `id_medecin` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `facture`
--

CREATE TABLE `facture` (
  `id_facture` varchar(20) NOT NULL,
  `montant` double NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_facture` date NOT NULL,
  `id_patient` varchar(20) NOT NULL,
  `id_rdv` varchar(20) NOT NULL,
  `id_type_paiement` varchar(20) NOT NULL,
  `id_ligneOrd` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ligne_ordonnance`
--

CREATE TABLE `ligne_ordonnance` (
  `id_ligne` varchar(20) NOT NULL,
  `date_ordonnance` varchar(20) NOT NULL,
  `id_type_paiement` varchar(20) NOT NULL,
  `quantité` int(11) NOT NULL,
  `details` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ordonnance`
--

CREATE TABLE `ordonnance` (
  `id_ordonnance` varchar(20) NOT NULL,
  `date_ordonnance` date NOT NULL,
  `instructions` varchar(20) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_consultation` varchar(20) NOT NULL,
  `id_patient` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panne`
--

CREATE TABLE `panne` (
  `id_Panne` varchar(20) NOT NULL,
  `date_de_panne` date NOT NULL,
  `date_de_reparation` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `id_ressource` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id_rdv` varchar(20) NOT NULL,
  `date_demande` date NOT NULL,
  `date_rdv` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `type_consultation` varchar(20) NOT NULL,
  `id_patient` varchar(20) NOT NULL,
  `id_creneau` varchar(20) NOT NULL,
  `id_medecin` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ressources`
--

CREATE TABLE `ressources` (
  `id_ressource` varchar(20) NOT NULL,
  `Nom` varchar(20) NOT NULL,
  `Type` varchar(20) NOT NULL,
  `Dernier_Maintenence` date NOT NULL,
  `Statut` varchar(10) NOT NULL,
  `Localisation` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role`
--

CREATE TABLE `role` (
  `id_role` varchar(20) NOT NULL,
  `nom_role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salle`
--

CREATE TABLE `salle` (
  `id_salle` varchar(20) NOT NULL,
  `numero` int(11) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_admission` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ticket_num`
--

CREATE TABLE `ticket_num` (
  `id_ticket` varchar(20) NOT NULL,
  `date_creation` date NOT NULL,
  `date_expiration` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_facture` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `type_paiement`
--

CREATE TABLE `type_paiement` (
  `id_type` varchar(20) NOT NULL,
  `nom-type` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `id_facture` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id_user` varchar(20) NOT NULL,
  `Nom` varchar(20) NOT NULL,
  `Prenom` varchar(20) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Mot de Passe` varchar(10) NOT NULL,
  `Statut_Cmpt` varchar(10) NOT NULL,
  `CIN` int(10) NOT NULL,
  `Service` varchar(10) NOT NULL,
  `id_role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`id_admission`),
  ADD KEY `id_ticket` (`id_ticket`);

--
-- Index pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id_consultation`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_dossier_2` (`id_dossier`),
  ADD KEY `id_dossier_3` (`id_dossier`);

--
-- Index pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`id_creneau`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD PRIMARY KEY (`id_dossier`),
  ADD KEY `id_patient` (`id_patient`,`id_medecin`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id_facture`),
  ADD KEY `id_patient` (`id_patient`,`id_rdv`,`id_type_paiement`,`id_ligneOrd`),
  ADD KEY `id_rdv` (`id_rdv`),
  ADD KEY `id_type_paiement` (`id_type_paiement`),
  ADD KEY `id_ligneOrd` (`id_ligneOrd`);

--
-- Index pour la table `ligne_ordonnance`
--
ALTER TABLE `ligne_ordonnance`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `id_type_paiement` (`id_type_paiement`);

--
-- Index pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD PRIMARY KEY (`id_ordonnance`),
  ADD KEY `id_consultation` (`id_consultation`,`id_patient`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Index pour la table `panne`
--
ALTER TABLE `panne`
  ADD PRIMARY KEY (`id_Panne`),
  ADD KEY `id_ressource` (`id_ressource`);

--
-- Index pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id_rdv`),
  ADD KEY `id_patient` (`id_patient`,`id_creneau`,`id_medecin`),
  ADD KEY `id_creneau` (`id_creneau`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Index pour la table `ressources`
--
ALTER TABLE `ressources`
  ADD PRIMARY KEY (`id_ressource`);

--
-- Index pour la table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id_role`);

--
-- Index pour la table `salle`
--
ALTER TABLE `salle`
  ADD PRIMARY KEY (`id_salle`),
  ADD KEY `id_admission` (`id_admission`);

--
-- Index pour la table `ticket_num`
--
ALTER TABLE `ticket_num`
  ADD PRIMARY KEY (`id_ticket`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Index pour la table `type_paiement`
--
ALTER TABLE `type_paiement`
  ADD PRIMARY KEY (`id_type`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `id_role` (`id_role`);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `admission_ibfk_1` FOREIGN KEY (`id_ticket`) REFERENCES `ticket_num` (`id_ticket`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `consultation_ibfk_1` FOREIGN KEY (`id_dossier`) REFERENCES `dossier_medical` (`id_dossier`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD CONSTRAINT `dossier_medical_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dossier_medical_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `facture_ibfk_1` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `facture_ibfk_2` FOREIGN KEY (`id_type_paiement`) REFERENCES `type_paiement` (`id_type`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `facture_ibfk_3` FOREIGN KEY (`id_ligneOrd`) REFERENCES `ligne_ordonnance` (`id_ligne`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD CONSTRAINT `ordonnance_ibfk_1` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ordonnance_ibfk_2` FOREIGN KEY (`id_consultation`) REFERENCES `consultation` (`id_consultation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `panne`
--
ALTER TABLE `panne`
  ADD CONSTRAINT `panne_ibfk_1` FOREIGN KEY (`id_ressource`) REFERENCES `ressources` (`id_ressource`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `rendez_vous_ibfk_1` FOREIGN KEY (`id_creneau`) REFERENCES `creneau` (`id_creneau`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rendez_vous_ibfk_2` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `rendez_vous_ibfk_3` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `salle`
--
ALTER TABLE `salle`
  ADD CONSTRAINT `salle_ibfk_1` FOREIGN KEY (`id_admission`) REFERENCES `admission` (`id_admission`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `ticket_num`
--
ALTER TABLE `ticket_num`
  ADD CONSTRAINT `ticket_num_ibfk_1` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id_facture`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `type_paiement`
--
ALTER TABLE `type_paiement`
  ADD CONSTRAINT `type_paiement_ibfk_1` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id_facture`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
