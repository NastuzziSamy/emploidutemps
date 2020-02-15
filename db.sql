-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Client :  localhost:3306
-- Généré le :  Mer 06 Septembre 2017 à 21:32
-- Version du serveur :  5.6.30-1+b1
-- Version de PHP :  7.0.19-1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `emploidutemps`
--

-- --------------------------------------------------------

--
-- Structure de la table `debug`
--

CREATE TABLE `debug` (
  `id` int(11) NOT NULL,
  `login` char(16) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `creator` char(16) COLLATE utf8_bin NOT NULL,
  `creator_asso` char(16) COLLATE utf8_bin DEFAULT NULL,
  `type` varchar(16) COLLATE utf8_bin NOT NULL,
  `date` date NOT NULL,
  `begin` char(5) COLLATE utf8_bin NOT NULL,
  `end` char(5) COLLATE utf8_bin NOT NULL,
  `subject` char(32) COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin,
  `location` char(32) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `events_followed`
--

CREATE TABLE `events_followed` (
  `id` int(11) NOT NULL,
  `idEvent` int(11) NOT NULL,
  `login` char(8) COLLATE utf8_bin NOT NULL,
  `color` char(8) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `exchanges`
--

CREATE TABLE `exchanges` (
  `id` int(11) NOT NULL,
  `idUV` int(11) NOT NULL,
  `idUV2` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `exchanges_canceled`
--

CREATE TABLE `exchanges_canceled` (
  `id` int(11) NOT NULL,
  `idExchange` int(11) NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `login2` char(16) COLLATE utf8_bin NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `available` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `exchanges_received`
--

CREATE TABLE `exchanges_received` (
  `id` int(11) NOT NULL,
  `idExchange` int(11) NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `exchanged` tinyint(1) NOT NULL DEFAULT '0',
  `idSent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `exchanges_sent`
--

CREATE TABLE `exchanges_sent` (
  `id` int(11) NOT NULL,
  `idExchange` int(11) NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` text COLLATE utf8_bin,
  `available` tinyint(1) NOT NULL DEFAULT '1',
  `exchanged` tinyint(1) NOT NULL DEFAULT '0',
  `idReceived` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `exchanges_set`
--

CREATE TABLE `exchanges_set` (
  `id` int(11) NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `login2` char(16) COLLATE utf8_bin NOT NULL,
  `idUV` int(11) NOT NULL,
  `idUV2` int(11) NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waiting` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `students`
--

CREATE TABLE `students` (
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `surname` text COLLATE utf8_bin,
  `firstname` text COLLATE utf8_bin,
  `email` text COLLATE utf8_bin,
  `semester` char(4) COLLATE utf8_bin NOT NULL,
  `nbrUV` int(11) NOT NULL,
  `uvs` char(64) COLLATE utf8_bin NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `mode` char(16) COLLATE utf8_bin DEFAULT NULL
  `prevoir` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `students_groups`
--

CREATE TABLE `students_groups` (
  `id` int(11) NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `name` char(32) COLLATE utf8_bin NOT NULL,
  `asso` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `students_groups_elements`
--

CREATE TABLE `students_groups_elements` (
  `id` int(11) NOT NULL,
  `idSubGroup` int(11) NOT NULL,
  `element` char(16) COLLATE utf8_bin NOT NULL,
  `info` char(16) COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `students_groups_subs`
--

CREATE TABLE `students_groups_subs` (
  `id` int(11) NOT NULL,
  `idGroup` int(11) NOT NULL,
  `name` char(32) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `uvs`
--

CREATE TABLE `uvs` (
  `id` int(11) NOT NULL,
  `uv` char(5) COLLATE utf8_bin NOT NULL,
  `type` char(1) COLLATE utf8_bin NOT NULL,
  `groupe` mediumint(9) NOT NULL,
  `day` smallint(6) NOT NULL,
  `begin` char(5) COLLATE utf8_bin NOT NULL,
  `end` char(5) COLLATE utf8_bin NOT NULL,
  `room` char(6) COLLATE utf8_bin DEFAULT NULL,
  `frequency` tinyint(4) NOT NULL,
  `week` char(1) COLLATE utf8_bin DEFAULT NULL,
  `nbrEtu` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `uvs_colors`
--

CREATE TABLE `uvs_colors` (
  `uv` char(5) COLLATE utf8_bin NOT NULL,
  `color` char(8) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `uvs_days`
--

CREATE TABLE `uvs_days` (
  `id` int(11) NOT NULL,
  `begin` date NOT NULL,
  `end` date NOT NULL,
  `day` int(11) DEFAULT NULL,
  `week` char(1) COLLATE utf8_bin DEFAULT NULL,
  `number` char(1) COLLATE utf8_bin DEFAULT NULL,
  `C` tinyint(1) NOT NULL DEFAULT '1',
  `D` tinyint(1) DEFAULT '1',
  `T` tinyint(1) NOT NULL DEFAULT '1',
  `subject` text COLLATE utf8_bin,
  `description` text COLLATE utf8_bin,
  `location` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `uvs_followed`
--

CREATE TABLE `uvs_followed` (
  `id` int(11) NOT NULL,
  `idUV` char(8) COLLATE utf8_bin NOT NULL,
  `login` char(16) COLLATE utf8_bin NOT NULL,
  `color` char(8) COLLATE utf8_bin DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `exchanged` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure de la table `uvs_rooms`
--

CREATE TABLE `uvs_rooms` (
  `id` int(11) NOT NULL,
  `room` char(8) COLLATE utf8_bin NOT NULL,
  `type` char(1) COLLATE utf8_bin NOT NULL,
  `day` tinyint(4) NOT NULL,
  `begin` char(5) COLLATE utf8_bin NOT NULL,
  `end` char(5) COLLATE utf8_bin NOT NULL,
  `gap` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `debug`
--
ALTER TABLE `debug`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `events_followed`
--
ALTER TABLE `events_followed`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exchanges`
--
ALTER TABLE `exchanges`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exchanges_canceled`
--
ALTER TABLE `exchanges_canceled`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exchanges_received`
--
ALTER TABLE `exchanges_received`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exchanges_sent`
--
ALTER TABLE `exchanges_sent`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `exchanges_set`
--
ALTER TABLE `exchanges_set`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`login`);

--
-- Index pour la table `students_groups`
--
ALTER TABLE `students_groups`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `students_groups_elements`
--
ALTER TABLE `students_groups_elements`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `students_groups_subs`
--
ALTER TABLE `students_groups_subs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `uvs`
--
ALTER TABLE `uvs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `uvs_colors`
--
ALTER TABLE `uvs_colors`
  ADD PRIMARY KEY (`uv`);

--
-- Index pour la table `uvs_days`
--
ALTER TABLE `uvs_days`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `uvs_followed`
--
ALTER TABLE `uvs_followed`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `uvs_rooms`
--
ALTER TABLE `uvs_rooms`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `debug`
--
ALTER TABLE `debug`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;
--
-- AUTO_INCREMENT pour la table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT pour la table `events_followed`
--
ALTER TABLE `events_followed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
--
-- AUTO_INCREMENT pour la table `exchanges`
--
ALTER TABLE `exchanges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT pour la table `exchanges_canceled`
--
ALTER TABLE `exchanges_canceled`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT pour la table `exchanges_received`
--
ALTER TABLE `exchanges_received`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=199;
--
-- AUTO_INCREMENT pour la table `exchanges_sent`
--
ALTER TABLE `exchanges_sent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT pour la table `exchanges_set`
--
ALTER TABLE `exchanges_set`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `students_groups`
--
ALTER TABLE `students_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
--
-- AUTO_INCREMENT pour la table `students_groups_elements`
--
ALTER TABLE `students_groups_elements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;
--
-- AUTO_INCREMENT pour la table `students_groups_subs`
--
ALTER TABLE `students_groups_subs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT pour la table `uvs`
--
ALTER TABLE `uvs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1174;
--
-- AUTO_INCREMENT pour la table `uvs_days`
--
ALTER TABLE `uvs_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;
--
-- AUTO_INCREMENT pour la table `uvs_followed`
--
ALTER TABLE `uvs_followed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29896;
--
-- AUTO_INCREMENT pour la table `uvs_rooms`
--
ALTER TABLE `uvs_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=858;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
