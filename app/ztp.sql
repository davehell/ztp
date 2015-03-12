-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

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
  `je_aktivni` tinyint(4) NOT NULL DEFAULT '1',
  `je_tester` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `jmeno` (`jmeno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `lide` (`id`, `jmeno`, `prostredi`, `je_aktivni`, `je_tester`) VALUES
(1, 'dhe',  'FF ESR 24.7.0',  1,  0),
(2, 'mma',  'FF ESR 31.4',  1,  1),
(3, 'dku',  'Internet Explorer 11.0.9600',  1,  1),
(4, 'jbo',  '', 1,  0),
(5, 'jsv',  '', 1,  0),
(6, 'tmo',  '', 1,  0),
(7, 'jhb',  '', 1,  0),
(8, 'vma',  '', 1,  0),
(9, 'mkr',  '', 1,  0),
(10,  'lbu',  '', 1,  0),
(11,  'pso',  'FF 35.0',  1,  1),
(12,  'mha',  'FF ESR 31.5.0',  1,  1),
(13,  'jpe',  'FF ESR 31.3.0',  1,  1),
(14,  'mpi',  'FF 36.0',  1,  1),
(15,  'bpi',  '', 1,  1),
(16,  'mvo',  '', 1,  0),
(17,  'kpe',  '', 1,  0),
(18,  'hba',  '', 1,  1);

DROP TABLE IF EXISTS `sekce`;
CREATE TABLE `sekce` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `sekce` (`id`, `nazev`) VALUES
(1,	'monitorování'),
(2,	'Energis'),
(3,	'procesy'),
(4,	'hlavní stránka'),
(5,	'parametrizace');

DROP TABLE IF EXISTS `typy_tagu`;
CREATE TABLE `typy_tagu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

INSERT INTO `typy_tagu` (`id`, `nazev`) VALUES
(1,	'energis'),
(2,	'rwe');

DROP TABLE IF EXISTS `typy_zmen`;
CREATE TABLE `typy_zmen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazev` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `zkratka` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `zmeny`;
CREATE TABLE `zmeny` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verze_id` int(11) NOT NULL,
  `typy_zmen_id` int(11) NOT NULL,
  `sekce_id` int(11) NOT NULL DEFAULT '2',
  `poradi` int(11) NOT NULL DEFAULT '0' COMMENT 'pořadí v rámci protokolu',
  `commit` varchar(50) COLLATE utf8_czech_ci DEFAULT NULL COMMENT 'hash při importu z gitu, null při ručním zadání',
  `je_ok` tinyint(4) DEFAULT NULL COMMENT '1 - funguje správně, 0 - existuje neopravená chyba, null - zatím netestováno',
  `autor_id` int(11) DEFAULT NULL,
  `tester_id` int(11) DEFAULT NULL COMMENT 'null - tester ještě není přiřazený',
  `je_verejna` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 - objeví se výsledném změnovém protokolu, 0 - neveřejná změna která se musí otestovat',
  `text` text COLLATE utf8_czech_ci NOT NULL COMMENT 'popis změny, který se objeví ve změnovém i testovacím protokolu',
  `detail` text CHARACTER SET utf16 COLLATE utf16_czech_ci NOT NULL COMMENT 'podrobné informace určené pro testery',
  `task` varchar(50) COLLATE utf8_czech_ci NOT NULL COMMENT 'číslo tasku',
  `vysledek_testu` varchar(100) COLLATE utf8_czech_ci NOT NULL COMMENT 'výsledek testování',
  `uloha` varchar(1000) COLLATE utf8_czech_ci NOT NULL COMMENT 'kterých úloh se změna týká',
  PRIMARY KEY (`id`),
  UNIQUE KEY `commit` (`commit`),
  KEY `typy_zmen_id` (`typy_zmen_id`),
  KEY `sekce_id` (`sekce_id`),
  KEY `autor_id` (`autor_id`),
  KEY `tester_id` (`tester_id`),
  KEY `verze_id` (`verze_id`),
  CONSTRAINT `zmeny_ibfk_2` FOREIGN KEY (`typy_zmen_id`) REFERENCES `typy_zmen` (`id`),
  CONSTRAINT `zmeny_ibfk_3` FOREIGN KEY (`sekce_id`) REFERENCES `sekce` (`id`),
  CONSTRAINT `zmeny_ibfk_4` FOREIGN KEY (`autor_id`) REFERENCES `lide` (`id`),
  CONSTRAINT `zmeny_ibfk_5` FOREIGN KEY (`tester_id`) REFERENCES `lide` (`id`),
  CONSTRAINT `zmeny_ibfk_6` FOREIGN KEY (`verze_id`) REFERENCES `verze` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `zmeny_tagy`;
CREATE TABLE `zmeny_tagy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zmeny_id` int(11) NOT NULL,
  `typy_tagu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `typy_tagu_id` (`typy_tagu_id`),
  KEY `zmeny_id` (`zmeny_id`),
  CONSTRAINT `zmeny_tagy_ibfk_2` FOREIGN KEY (`typy_tagu_id`) REFERENCES `typy_tagu` (`id`),
  CONSTRAINT `zmeny_tagy_ibfk_3` FOREIGN KEY (`zmeny_id`) REFERENCES `zmeny` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


-- 2015-03-10 10:50:02
