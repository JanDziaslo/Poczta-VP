-- Baza szkola, tabela uczniowie.
-- Zmiany względem pierwotnej wersji:
--   id_ucz -> INT AUTO_INCREMENT,
--   pesel -> CHAR(11),
--   pozostałe pola -> VARCHAR o odpowiedniej długości.
-- Poprawione dane: PESEL jako tekst z poprawnymi sumami kontrolnymi,
-- literówka "Gnieno" -> "Gniezno". Dodano 3 nowych uczniów.

CREATE DATABASE IF NOT EXISTS szkola
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE szkola;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS zmiany_uczniowie;
DROP TABLE IF EXISTS uczniowie;

CREATE TABLE uczniowie (
    id_ucz   INT         NOT NULL AUTO_INCREMENT,
    nazwisko VARCHAR(40) NOT NULL,
    imie     VARCHAR(30) NOT NULL,
    pesel    CHAR(11)    NOT NULL,
    adres_ul VARCHAR(40) NOT NULL,
    adres_nr VARCHAR(6)  NOT NULL,
    miasto   VARCHAR(30) NOT NULL,
    PRIMARY KEY (id_ucz),
    UNIQUE KEY uq_uczniowie_pesel (pesel)
) ENGINE=InnoDB;

-- historia zmian - potrzebna do informacji "ostatnia zmiana"
CREATE TABLE zmiany_uczniowie (
    id_zmiany INT         NOT NULL AUTO_INCREMENT,
    imie      VARCHAR(30) NOT NULL,
    nazwisko  VARCHAR(40) NOT NULL,
    operacja  VARCHAR(12) NOT NULL,
    data      DATETIME    NOT NULL,
    PRIMARY KEY (id_zmiany)
) ENGINE=InnoDB;

-- uczniowie (id_ucz nadawany automatycznie: 1..5)
INSERT INTO uczniowie (nazwisko, imie, pesel, adres_ul, adres_nr, miasto) VALUES
('Abacki',  'Jan',     '95091202017', 'Nocna',     '21a', 'Gniezno'),
('Babacki', 'Tomasz',  '96100102012', 'Gwiezdna',  '2',   'Gniezno'),
('Cabacki', 'Jerzy',   '95110902050', 'Mierna',    '13b', 'Kutno'),
('Dabacki', 'Tobiasz', '94010398341', 'Bierna',    '3',   'Miastko'),
('Ebacki',  'Adrian',  '95010198931', 'Marna',     '456', 'Mielno');

-- 3 nowi uczniowie. W PESEL osób urodzonych po 1999 r. do miesiąca dodaje się 20:
--   2000-02-29 -> 00222901239, 2010-06-15 -> 10261504566, 2003-10-16 -> 03301607893.
INSERT INTO uczniowie (nazwisko, imie, pesel, adres_ul, adres_nr, miasto) VALUES
('Fabacki', 'Filip',    '00222901239', 'Fabryczna', '8',   'Miastko'),
('Gabacka', 'Gabriela', '10261504566', 'Leśna',     '12c', 'Gniezno'),
('Habacka', 'Helena',   '03301607893', 'Szkolna',   '5',   'Kutno');
