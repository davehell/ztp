-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

USE `ztp`;

DROP TABLE IF EXISTS `chyby`;
CREATE TABLE `chyby` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zmeny_id` int(11) NOT NULL,
  `je_ok` tinyint(4) DEFAULT '0' COMMENT '1 - opraveno, 0 - neopraveno, null - čeká na otestování',
  `text` varchar(1000) COLLATE utf8_czech_ci NOT NULL COMMENT 'popis chyby',
  PRIMARY KEY (`id`),
  KEY `zmeny_id` (`zmeny_id`),
  CONSTRAINT `chyby_ibfk_2` FOREIGN KEY (`zmeny_id`) REFERENCES `zmeny` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `lide`;
CREATE TABLE `lide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jmeno` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `prostredi` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'popis testovacího prostředí (prohlížeč)',
  `je_zadano_prostredi` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'příznak, jestli tester vyplnil své prostředí',
  `je_aktivni` tinyint(4) NOT NULL DEFAULT '1',
  `je_tester` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `jmeno` (`jmeno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

TRUNCATE `lide`;
INSERT INTO `lide` (`id`, `jmeno`, `prostredi`, `je_zadano_prostredi`, `je_aktivni`, `je_tester`) VALUES
(1,	'dhe',	'',	0,	1,	0),
(2,	'mma',	'',	1,	1,	1),
(3,	'dku',	'',	0,	1,	1),
(4,	'jbo',	'',	0,	1,	0),
(5,	'jsv',	'',	0,	1,	0),
(6,	'tmo',	'',	0,	1,	0),
(7,	'jhb',	'',	0,	1,	0),
(8,	'vma',	'',	0,	1,	0),
(9,	'mkr',	'',	0,	1,	0),
(10,	'lbu',	'',	0,	1,	0),
(11,	'pso',	'',	0,	1,	1),
(12,	'mha',	'',	0,	1,	1),
(13,	'jpe',	'',	0,	1,	1),
(14,	'mpi',	'',	0,	1,	1),
(15,	'bpi',	'',	0,	1,	1),
(16,	'mvo',	'',	0,	1,	0),
(17,	'kpe',	'',	0,	1,	0),
(18,	'hba',	'',	0,	1,	1),
(19,  'vzu',  '', 0,  1,  0),
(20,  'vne',  '', 0,  1,  0),
(21,  'pvr',  '', 0,  1,  0);

DROP TABLE IF EXISTS `tagy`;
CREATE TABLE `tagy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podnik` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `nazev` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

TRUNCATE `tagy`;
INSERT INTO `tagy` (`id`, `podnik`, `nazev`) VALUES
(2,	'25',	'RWE'),
(3, 'myenergisplus', 'myEnergis Plus');

DROP TABLE IF EXISTS `typy_zmen`;
CREATE TABLE `typy_zmen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `zkratka` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

TRUNCATE `typy_zmen`;
INSERT INTO `typy_zmen` (`id`, `nazev`, `zkratka`) VALUES
(1,	'Přidáno',	'add'),
(2,	'Změněno',	'mod'),
(3,	'Opraveno',	'fix');

DROP TABLE IF EXISTS `verze`;
CREATE TABLE `verze` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `datum` date DEFAULT NULL,
  `pozn_verejna` text COLLATE utf8_czech_ci NOT NULL,
  `pozn_skryta` text COLLATE utf8_czech_ci NOT NULL,
  `je_zamcena` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nazev` (`nazev`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `zmeny`;
CREATE TABLE `zmeny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verze_id` int(11) NOT NULL,
  `poradi` int(11) NOT NULL DEFAULT '0' COMMENT 'pořadí v rámci protokolu',
  `autor_id` int(11) DEFAULT NULL,
  `je_verejna` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 - objeví se výsledném změnovém protokolu, 0 - neveřejná změna která se musí otestovat',
  `task` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'číslo tasku',
  `uloha` varchar(1000) COLLATE utf8_czech_ci NOT NULL COMMENT 'kterých úloh se změna týká',
  `typy_zmen_id` int(11) NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL COMMENT 'popis změny, který se objeví ve změnovém i testovacím protokolu',
  `detail` text COLLATE utf8_czech_ci NOT NULL COMMENT 'podrobné informace určené pro testery',
  `tester_id` int(11) DEFAULT NULL COMMENT 'null - tester ještě není přiřazený',
  `je_ok` tinyint(4) DEFAULT NULL COMMENT '1 - funguje správně, 0 - existuje neopravená chyba, null - zatím netestováno',
  `vysledek_testu` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'výsledek testování',
  PRIMARY KEY (`id`),
  KEY `typy_zmen_id` (`typy_zmen_id`),
  KEY `autor_id` (`autor_id`),
  KEY `tester_id` (`tester_id`),
  KEY `verze_id` (`verze_id`),
  CONSTRAINT `zmeny_ibfk_2` FOREIGN KEY (`typy_zmen_id`) REFERENCES `typy_zmen` (`id`),
  CONSTRAINT `zmeny_ibfk_4` FOREIGN KEY (`autor_id`) REFERENCES `lide` (`id`),
  CONSTRAINT `zmeny_ibfk_5` FOREIGN KEY (`tester_id`) REFERENCES `lide` (`id`),
  CONSTRAINT `zmeny_ibfk_6` FOREIGN KEY (`verze_id`) REFERENCES `verze` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `zmeny_tagy`;
CREATE TABLE `zmeny_tagy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zmeny_id` int(11) NOT NULL,
  `tagy_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `typy_tagu_id` (`tagy_id`),
  KEY `zmeny_id` (`zmeny_id`),
  CONSTRAINT `zmeny_tagy_ibfk_3` FOREIGN KEY (`zmeny_id`) REFERENCES `zmeny` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `zmeny_tagy_ibfk_4` FOREIGN KEY (`tagy_id`) REFERENCES `tagy` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2015-04-03 12:36:26
