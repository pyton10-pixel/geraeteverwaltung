-- Geräteverwaltung: relationales Datenmodell (MariaDB / MySQL)
-- Zusatzfelder sind je Gruppe verschieden. Deshalb stehen sie nicht als Spalten
-- in "geraete", sondern als Zeilen in "gruppen_felder" und "geraete_werte".

CREATE DATABASE IF NOT EXISTS geraeteverwaltung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE geraeteverwaltung;

-- Gerätegruppen, z. B. Notebooks, Monitore
-- "datei" stammt aus der CSV-Version (historisches Feld), in der Datenbank nur zur Nachverfolgung
CREATE TABLE gruppen (
    gruppe_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    datei VARCHAR(100) NOT NULL UNIQUE
);

-- Standorte kommen in den Daten sehr oft doppelt vor -> eigene Tabelle
CREATE TABLE standorte (
    standort_id INT AUTO_INCREMENT PRIMARY KEY,
    bezeichnung VARCHAR(150) NOT NULL UNIQUE
);

-- Geräte mit den festen Feldern
-- RESTRICT: eine Gruppe mit Geräten kann nicht gelöscht werden (gleiche Regel wie im Programm)
CREATE TABLE geraete (
    geraet_id INT AUTO_INCREMENT PRIMARY KEY,
    gruppe_id INT NOT NULL,
    standort_id INT NOT NULL,
    bezeichnung VARCHAR(150) NOT NULL,
    bestand INT NOT NULL DEFAULT 0 CHECK (bestand >= 0),
    FOREIGN KEY (gruppe_id) REFERENCES gruppen(gruppe_id) ON DELETE RESTRICT,
    FOREIGN KEY (standort_id) REFERENCES standorte(standort_id) ON DELETE RESTRICT
);

-- Welche Zusatzfelder hat eine Gruppe (max. 3), in welcher Reihenfolge
CREATE TABLE gruppen_felder (
    feld_id INT AUTO_INCREMENT PRIMARY KEY,
    gruppe_id INT NOT NULL,
    feldname VARCHAR(50) NOT NULL,
    sortierung TINYINT NOT NULL,
    FOREIGN KEY (gruppe_id) REFERENCES gruppen(gruppe_id) ON DELETE RESTRICT,
    UNIQUE (gruppe_id, feldname),     -- kein Feld doppelt in einer Gruppe
    UNIQUE (gruppe_id, sortierung)    -- keine Position doppelt
);

-- Wert eines Zusatzfeldes für ein Gerät. Leerer Wert = keine Zeile.
-- CASCADE: wird ein Gerät gelöscht, verschwinden auch seine Werte.
-- RESTRICT: ein Feld mit Werten kann nicht gelöscht werden (wie im Programm: nur bei leerer Gruppe).
-- Hinweis: Dass feld_id zur Gruppe des Geräts gehört, prüft das Programm, nicht die Datenbank.
CREATE TABLE geraete_werte (
    wert_id INT AUTO_INCREMENT PRIMARY KEY,
    geraet_id INT NOT NULL,
    feld_id INT NOT NULL,
    wert VARCHAR(255) NOT NULL,
    FOREIGN KEY (geraet_id) REFERENCES geraete(geraet_id) ON DELETE CASCADE,
    FOREIGN KEY (feld_id) REFERENCES gruppen_felder(feld_id) ON DELETE RESTRICT,
    UNIQUE (geraet_id, feld_id)       -- pro Gerät nur ein Wert je Feld
);
