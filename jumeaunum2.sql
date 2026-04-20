-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 09:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jumeaunum`
--

-- --------------------------------------------------------

--
-- Table structure for table `admission`
--

CREATE TABLE `admission` (
  `id_admission` int(8) NOT NULL,
  `date_arrive_relle` date NOT NULL,
  `mode_entree` varchar(20) NOT NULL,
  `id_ticket` int(8) DEFAULT NULL,
  `id_salle` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consultation`
--

CREATE TABLE `consultation` (
  `id_consultation` int(8) NOT NULL,
  `date_consultation` date NOT NULL,
  `motif` varchar(20) NOT NULL,
  `diagnostic` varchar(20) NOT NULL,
  `notes` varchar(20) NOT NULL,
  `id_dossier` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creneau`
--

CREATE TABLE `creneau` (
  `id_creneau` int(8) NOT NULL,
  `date` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_medecin` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dossier_medical`
--

CREATE TABLE `dossier_medical` (
  `id_dossier` int(8) NOT NULL,
  `description` varchar(20) NOT NULL,
  `date_creation` date NOT NULL,
  `id_patient` int(8) NOT NULL,
  `id_medecin` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facture`
--

CREATE TABLE `facture` (
  `id_facture` int(8) NOT NULL,
  `montant` float NOT NULL,
  `statut` varchar(20) NOT NULL,
  `date_facture` date NOT NULL,
  `id_rdv` int(8) DEFAULT NULL,
  `id_type_paiement` int(8) NOT NULL,
  `id_ligneOrd` int(8) DEFAULT NULL,
  `id_patient` int(8) NOT NULL,
  `ressource_assignee` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ligne_ordonnance`
--

CREATE TABLE `ligne_ordonnance` (
  `id_ligne` int(8) NOT NULL,
  `date_ordonnance` varchar(20) NOT NULL,
  `quantité` int(11) NOT NULL,
  `details` varchar(10) NOT NULL,
  `id_type_paiement` int(8) NOT NULL,
  `id_ordonnance` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ordonnance`
--

CREATE TABLE `ordonnance` (
  `id_ordonnance` int(8) NOT NULL,
  `date_ordonnance` date NOT NULL,
  `instructions` varchar(20) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_consultation` int(8) NOT NULL,
  `id_patient` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panne`
--

CREATE TABLE `panne` (
  `id_Panne` int(8) NOT NULL,
  `date_de_panne` date NOT NULL,
  `date_de_reparation` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `id_ressource` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rendez_vous`
--

CREATE TABLE `rendez_vous` (
  `id_rdv` int(8) NOT NULL,
  `date_demande` date NOT NULL,
  `date_rdv` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `type_consultation` varchar(20) NOT NULL,
  `id_patient` int(8) NOT NULL,
  `id_creneau` int(8) NOT NULL,
  `id_medecin` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ressources`
--

CREATE TABLE `ressources` (
  `id_ressource` int(8) NOT NULL,
  `Nom` varchar(20) NOT NULL,
  `Type` varchar(20) NOT NULL,
  `Dernier_Maintenence` date NOT NULL,
  `Statut` varchar(10) NOT NULL,
  `Localisation` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id_role` int(8) NOT NULL,
  `nom_role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id_role`, `nom_role`) VALUES
(1, 'patient'),
(2, 'admin'),
(3, 'medecin'),
(4, 'superadmin');

-- --------------------------------------------------------

--
-- Table structure for table `salle`
--

CREATE TABLE `salle` (
  `id_salle` int(8) NOT NULL,
  `numero` int(11) NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_medecin` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_num`
--

CREATE TABLE `ticket_num` (
  `id_ticket` int(8) NOT NULL,
  `date_creation` date NOT NULL,
  `date_expiration` date NOT NULL,
  `statut` varchar(20) NOT NULL,
  `id_facture` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `type_paiement`
--

CREATE TABLE `type_paiement` (
  `id_type` int(8) NOT NULL,
  `nom_type` varchar(20) NOT NULL,
  `description` varchar(20) NOT NULL,
  `montant` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `type_paiement`
--

INSERT INTO `type_paiement` (`id_type`, `nom_type`, `description`, `montant`) VALUES
(44, 'JKH', 'KJ', 55);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(8) NOT NULL,
  `Nom` varchar(20) NOT NULL,
  `Prenom` varchar(20) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `mot_de_passe` varchar(10) NOT NULL,
  `Statut_Cmpt` varchar(10) NOT NULL,
  `CIN` int(8) NOT NULL,
  `Service` varchar(10) NOT NULL,
  `id_role` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `Nom`, `Prenom`, `Email`, `mot_de_passe`, `Statut_Cmpt`, `CIN`, `Service`, `id_role`) VALUES
(1, 'Dr. fatma ', 'zahra', 'fatmazahra@jumnum.tn', '889', 'online', 346372, 'generalist', 3),
(776, 'ali', 'ben ahmed', 'ahmed@gmail.com', '098', 'actif', 346372, 'AH', 1),
(2691284, 'sd', 'see', 'jzhef@jhf.jdf', '$2y$10$R7y', 'actif', 12345670, 'cardiologi', 1),
(57210804, 'hamed', 'ben ali', 'jqhsd@jsdjs.ijjzd', '$2y$10$hCV', 'actif', 12345678, '', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admission`
--
ALTER TABLE `admission`
  ADD PRIMARY KEY (`id_admission`),
  ADD KEY `FK_TICKET` (`id_ticket`),
  ADD KEY `admission_ibfk_1` (`id_salle`);

--
-- Indexes for table `consultation`
--
ALTER TABLE `consultation`
  ADD PRIMARY KEY (`id_consultation`),
  ADD KEY `id_dossier` (`id_dossier`),
  ADD KEY `id_dossier_2` (`id_dossier`),
  ADD KEY `id_dossier_3` (`id_dossier`);

--
-- Indexes for table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`id_creneau`),
  ADD KEY `FK_MEDECIN4` (`id_medecin`);

--
-- Indexes for table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD PRIMARY KEY (`id_dossier`),
  ADD KEY `id_patient` (`id_patient`,`id_medecin`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Indexes for table `facture`
--
ALTER TABLE `facture`
  ADD PRIMARY KEY (`id_facture`),
  ADD KEY `id_rdv` (`id_rdv`),
  ADD KEY `id_type_paiement` (`id_type_paiement`),
  ADD KEY `id_ligneOrd` (`id_ligneOrd`),
  ADD KEY `FK_PATIENTT` (`id_patient`);

--
-- Indexes for table `ligne_ordonnance`
--
ALTER TABLE `ligne_ordonnance`
  ADD PRIMARY KEY (`id_ligne`),
  ADD KEY `FK_ORD` (`id_ordonnance`),
  ADD KEY `FK_TYPEE` (`id_type_paiement`);

--
-- Indexes for table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD PRIMARY KEY (`id_ordonnance`),
  ADD KEY `id_consultation` (`id_consultation`,`id_patient`),
  ADD KEY `id_patient` (`id_patient`);

--
-- Indexes for table `panne`
--
ALTER TABLE `panne`
  ADD PRIMARY KEY (`id_Panne`),
  ADD KEY `id_ressource` (`id_ressource`);

--
-- Indexes for table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD PRIMARY KEY (`id_rdv`),
  ADD KEY `id_patient` (`id_patient`,`id_creneau`,`id_medecin`),
  ADD KEY `id_creneau` (`id_creneau`),
  ADD KEY `id_medecin` (`id_medecin`);

--
-- Indexes for table `ressources`
--
ALTER TABLE `ressources`
  ADD PRIMARY KEY (`id_ressource`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id_role`);

--
-- Indexes for table `salle`
--
ALTER TABLE `salle`
  ADD PRIMARY KEY (`id_salle`),
  ADD KEY `FK_MEDECIN3` (`id_medecin`);

--
-- Indexes for table `ticket_num`
--
ALTER TABLE `ticket_num`
  ADD PRIMARY KEY (`id_ticket`),
  ADD KEY `id_facture` (`id_facture`);

--
-- Indexes for table `type_paiement`
--
ALTER TABLE `type_paiement`
  ADD PRIMARY KEY (`id_type`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD KEY `id_role` (`id_role`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission`
--
ALTER TABLE `admission`
  ADD CONSTRAINT `FK_TICKET` FOREIGN KEY (`id_ticket`) REFERENCES `ticket_num` (`id_ticket`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `admission_ibfk_1` FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `consultation`
--
ALTER TABLE `consultation`
  ADD CONSTRAINT `FK_DOSSIER` FOREIGN KEY (`id_dossier`) REFERENCES `dossier_medical` (`id_dossier`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `creneau`
--
ALTER TABLE `creneau`
  ADD CONSTRAINT `FK_MEDECIN4` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dossier_medical`
--
ALTER TABLE `dossier_medical`
  ADD CONSTRAINT `FK_MEDECIN` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_PATIENT` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `facture`
--
ALTER TABLE `facture`
  ADD CONSTRAINT `FK_LIGNE` FOREIGN KEY (`id_ligneOrd`) REFERENCES `ligne_ordonnance` (`id_ligne`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_PATIENTT` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_RDV` FOREIGN KEY (`id_rdv`) REFERENCES `rendez_vous` (`id_rdv`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_TYPE` FOREIGN KEY (`id_type_paiement`) REFERENCES `type_paiement` (`id_type`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ligne_ordonnance`
--
ALTER TABLE `ligne_ordonnance`
  ADD CONSTRAINT `FK_ORD` FOREIGN KEY (`id_ordonnance`) REFERENCES `ordonnance` (`id_ordonnance`),
  ADD CONSTRAINT `FK_TYPEE` FOREIGN KEY (`id_type_paiement`) REFERENCES `type_paiement` (`id_type`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ordonnance`
--
ALTER TABLE `ordonnance`
  ADD CONSTRAINT `FK_CONSULTATION` FOREIGN KEY (`id_consultation`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_PATIENT3` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `panne`
--
ALTER TABLE `panne`
  ADD CONSTRAINT `ID_RES1` FOREIGN KEY (`id_ressource`) REFERENCES `ressources` (`id_ressource`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rendez_vous`
--
ALTER TABLE `rendez_vous`
  ADD CONSTRAINT `FK_CRENEAU` FOREIGN KEY (`id_creneau`) REFERENCES `creneau` (`id_creneau`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_MEDECIN2` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_PATIENT4` FOREIGN KEY (`id_patient`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `salle`
--
ALTER TABLE `salle`
  ADD CONSTRAINT `FK_MEDECIN3` FOREIGN KEY (`id_medecin`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ticket_num`
--
ALTER TABLE `ticket_num`
  ADD CONSTRAINT `FK_FACTURE` FOREIGN KEY (`id_facture`) REFERENCES `facture` (`id_facture`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
