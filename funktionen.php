<?php
// funktionen.php – alle Funktionen der Geräteverwaltung
// Wird von verwaltung.php mit require eingebunden.

// Feste Spalten jeder Geräte-CSV. Die Zusatzfelder der Gruppe kommen dahinter.
// Eine Konstante ist – anders als eine Variable – auch innerhalb von Funktionen sichtbar.
const FESTE_FELDER = ["id", "bezeichnung", "bestand", "standort"];

// Kopfzeile der Gruppendatei (immer genau sechs Spalten)
const GRUPPEN_KOPF = ["id", "name", "datei", "extra1", "extra2", "extra3"];

// Name der Gruppendatei im Datenordner
const GRUPPEN_DATEI = "geraetegruppen.csv";


// ============================================================
// Allgemeine Hilfsfunktionen
// ============================================================

// Fragt einen Text ab und gibt ihn ohne Leerzeichen/Zeilenumbruch zurück
function eingabe($frage) {
    echo $frage;
    $text = fgets(STDIN);
    if ($text === false) {
        exit("\nEingabe beendet.\n");
    }
    return trim($text);
}

// Ja/Nein-Abfrage: true bei "j", false bei "n"
function bestaetigen($frage) {
    while (true) {
        $antwort = strtolower(eingabe($frage . " (j/n): "));
        if ($antwort === "j") {
            return true;
        }
        if ($antwort === "n") {
            return false;
        }
        echo "Bitte j oder n eingeben.\n";
    }
}

function zeigeUeberschrift($text) {
    echo "\n=== $text ===\n";
}

// Prüft, ob ein Text eine nichtnegative ganze Zahl ist (0, 1, 2, ...)
function istGueltigeZahl($text) {
    return $text !== "" && ctype_digit($text);
}

// Fragt eine ID ab. Rückgabe: ID als Integer oder -1 bei ungültiger Eingabe
function eingabeId($frage) {
    $text = eingabe($frage);
    if (istGueltigeZahl($text) && (int) $text > 0) {
        return (int) $text;
    }
    echo "Ungültige ID. Bitte eine positive ganze Zahl eingeben.\n";
    return -1;
}

// Sucht die Array-Position eines Eintrags über seine ID (nicht über die Bildschirmposition!)
function findeIndexNachId($liste, $id) {
    foreach ($liste as $index => $eintrag) {
        if ($eintrag["id"] === $id) {
            return $index;
        }
    }
    return -1;
}

// Größte vorhandene ID plus eins, bei leerer Liste 1 (nicht count() + 1!)
function naechsteId($liste) {
    $groesste = 0;
    foreach ($liste as $eintrag) {
        if ($eintrag["id"] > $groesste) {
            $groesste = $eintrag["id"];
        }
    }
    return $groesste + 1;
}

// Summe aller Bestände (nicht die Anzahl der Datensätze)
function berechneBestand($geraete) {
    $summe = 0;
    foreach ($geraete as $geraet) {
        $summe += $geraet["bestand"];
    }
    return $summe;
}

// Prüft Zeichen für Zeichen, ob der Text nur erlaubte Zeichen enthält
function nurErlaubteZeichen($text, $erlaubt) {
    if ($text === "") {
        return false;
    }
    for ($i = 0; $i < strlen($text); $i++) {
        if (strpos($erlaubt, $text[$i]) === false) {
            return false;
        }
    }
    return true;
}

// Dateikennung: nur a-z, 0-9 und _, "geraetegruppen" ist reserviert
function istGueltigeKennung($kennung) {
    return nurErlaubteZeichen($kennung, "abcdefghijklmnopqrstuvwxyz0123456789_")
        && $kennung !== "geraetegruppen";
}

// Zusatzfeldname: nur a-z und _, die festen Felder sind reserviert
function istGueltigerFeldname($feld) {
    return nurErlaubteZeichen($feld, "abcdefghijklmnopqrstuvwxyz_")
        && in_array($feld, FESTE_FELDER, true) === false;
}


// ============================================================
// Gruppenverwaltung
// ============================================================

// Prüft, ob ein Gruppenname schon vergeben ist (ohne Groß-/Kleinschreibung).
// $ausserId: diese Gruppe wird ignoriert (beim Umbenennen die eigene Gruppe)
function nameVergeben($gruppen, $name, $ausserId) {
    foreach ($gruppen as $gruppe) {
        if ($gruppe["id"] !== $ausserId
            && strtolower(trim($gruppe["name"])) === strtolower(trim($name))) {
            return true;
        }
    }
    return false;
}

function dateiVergeben($gruppen, $datei) {
    foreach ($gruppen as $gruppe) {
        if ($gruppe["datei"] === $datei) {
            return true;
        }
    }
    return false;
}

// Liste aller CSV-Dateinamen der Gruppen, z. B. ["notebook.csv", "beamer.csv"]
function dateienDerGruppen($gruppen) {
    $dateien = [];
    foreach ($gruppen as $gruppe) {
        $dateien[] = $gruppe["datei"];
    }
    return $dateien;
}

function felderAlsText($felder) {
    if (count($felder) === 0) {
        return "keine";
    }
    return implode(", ", $felder);
}

function zeigeGruppen($gruppen) {
    if (count($gruppen) === 0) {
        echo "Es sind noch keine Gruppen vorhanden.\n";
        return;
    }
    foreach ($gruppen as $gruppe) {
        echo "ID " . $gruppe["id"] . ": " . $gruppe["name"] . "\n";
        echo "   Datei:          " . $gruppe["datei"] . "\n";
        echo "   Zusatzfelder:   " . felderAlsText($gruppe["felder"]) . "\n";
        echo "   Datensätze:     " . count($gruppe["geraete"]) . "\n";
        echo "   Gesamtbestand:  " . berechneBestand($gruppe["geraete"]) . "\n";
    }
}

// Fragt bis zu drei Zusatzfeldnamen ab. Leere Eingabe beendet die Abfrage.
function felderEingeben() {
    $felder = [];
    echo "Bis zu 3 Zusatzfelder (nur a-z und _). Leere Eingabe beendet.\n";
    while (count($felder) < 3) {
        $feld = eingabe("Zusatzfeld " . (count($felder) + 1) . ": ");
        if ($feld === "") {
            break;
        }
        if (in_array($feld, FESTE_FELDER, true)) {
            echo "'$feld' ist reserviert (id, bezeichnung, bestand, standort).\n";
            continue;
        }
        if (istGueltigerFeldname($feld) === false) {
            echo "Ungültig: nur kleine Buchstaben a-z und _ erlaubt.\n";
            continue;
        }
        if (in_array($feld, $felder, true)) {
            echo "Das Feld '$feld' gibt es in dieser Gruppe schon.\n";
            continue;
        }
        $felder[] = $feld;
    }
    return $felder;
}

// G2: neue Gruppe erfassen. Rückgabe: aktualisierte Gruppenliste
function gruppeAnlegen($gruppen) {
    zeigeUeberschrift("Gruppe hinzufügen");

    while (true) {
        $name = eingabe("Gruppenname (leer = Abbrechen): ");
        if ($name === "") {
            echo "Abgebrochen.\n";
            return $gruppen;
        }
        if (nameVergeben($gruppen, $name, 0)) {
            echo "Eine Gruppe '$name' gibt es bereits.\n";
            continue;
        }
        break;
    }

    while (true) {
        $kennung = eingabe("Dateikennung, z. B. notebook (leer = Abbrechen): ");
        if ($kennung === "") {
            echo "Abgebrochen.\n";
            return $gruppen;
        }
        if (istGueltigeKennung($kennung) === false) {
            echo "Ungültig: nur a-z, 0-9 und _ erlaubt, 'geraetegruppen' ist reserviert.\n";
            continue;
        }
        if (dateiVergeben($gruppen, $kennung . ".csv")) {
            echo "Die Dateikennung '$kennung' wird schon verwendet.\n";
            continue;
        }
        break;
    }

    $felder = felderEingeben();

    $gruppen[] = [
        "id" => naechsteId($gruppen),
        "name" => $name,
        "datei" => $kennung . ".csv",   // Ordner und .csv ergänzt das Programm selbst
        "felder" => $felder,
        "geraete" => []
    ];
    echo "Gruppe '$name' angelegt.\n";
    return $gruppen;
}

// G3: Namen ändern, Zusatzfelder nur bei leerer Gruppe. Dateikennung bleibt immer gleich.
function gruppeBearbeiten($gruppen, $id) {
    $index = findeIndexNachId($gruppen, $id);
    if ($index === -1) {
        echo "Keine Gruppe mit ID $id vorhanden.\n";
        return $gruppen;
    }
    $gruppe = $gruppen[$index];

    echo "Aktueller Name: " . $gruppe["name"] . "\n";
    $name = eingabe("Neuer Name (leer = unverändert): ");
    if ($name !== "") {
        if (nameVergeben($gruppen, $name, $id)) {
            echo "Der Name '$name' ist schon vergeben. Name bleibt unverändert.\n";
        } else {
            $gruppe["name"] = $name;
            echo "Name geändert. Die Datei " . $gruppe["datei"] . " bleibt zugeordnet.\n";
        }
    }

    echo "Zusatzfelder: " . felderAlsText($gruppe["felder"]) . "\n";
    if (count($gruppe["geraete"]) > 0) {
        echo "Zusatzfelder können nur geändert werden, solange die Gruppe keine Geräte enthält.\n";
        echo "(Sonst müssten alle vorhandenen Datensätze und die CSV-Kopfzeile angepasst werden.)\n";
    } elseif (bestaetigen("Zusatzfelder neu festlegen?")) {
        $gruppe["felder"] = felderEingeben();
        echo "Neue Zusatzfelder: " . felderAlsText($gruppe["felder"]) . "\n";
    }

    $gruppen[$index] = $gruppe;
    return $gruppen;
}

// G4: nur leere Gruppen löschen. Die CSV-Datei wird erst beim Speichern entfernt.
function gruppeLoeschen($gruppen, $id) {
    $index = findeIndexNachId($gruppen, $id);
    if ($index === -1) {
        echo "Keine Gruppe mit ID $id vorhanden.\n";
        return $gruppen;
    }
    $gruppe = $gruppen[$index];

    // Auch ein Datensatz mit Bestand 0 zählt – deshalb count() und nicht berechneBestand()
    $anzahl = count($gruppe["geraete"]);
    if ($anzahl > 0) {
        echo "Die Gruppe enthält noch $anzahl Datensätze und kann nicht gelöscht werden.\n";
        return $gruppen;
    }

    if (bestaetigen("Gruppe '" . $gruppe["name"] . "' wirklich löschen?") === false) {
        echo "Nicht gelöscht.\n";
        return $gruppen;
    }

    unset($gruppen[$index]);
    $gruppen = array_values($gruppen);   // Positionen neu durchnummerieren (IDs bleiben gleich)
    echo "Gruppe gelöscht. Die Datei " . $gruppe["datei"] . " wird beim nächsten Speichern entfernt.\n";
    return $gruppen;
}


// ============================================================
// Geräteverwaltung
// ============================================================

// Alle Spalten einer Geräte-CSV: feste Felder + Zusatzfelder der Gruppe
function geraeteKopf($gruppe) {
    $kopf = FESTE_FELDER;
    foreach ($gruppe["felder"] as $feld) {
        $kopf[] = $feld;
    }
    return $kopf;
}

function zeigeGeraet($gruppe, $geraet) {
    echo "ID " . $geraet["id"] . ": " . $geraet["bezeichnung"] . "\n";
    echo "   bestand: " . $geraet["bestand"] . "\n";
    echo "   standort: " . $geraet["standort"] . "\n";
    // Die Feldnamen kommen aus der Gruppendefinition, nicht aus dem Programmcode
    foreach ($gruppe["felder"] as $feld) {
        echo "   $feld: " . $geraet[$feld] . "\n";
    }
}

// D2: alle Geräte einer Gruppe anzeigen
function zeigeGeraete($gruppe) {
    zeigeUeberschrift("Geräte der Gruppe " . $gruppe["name"]);
    if (count($gruppe["geraete"]) === 0) {
        echo "Diese Gruppe enthält noch keine Geräte.\n";
        return;
    }
    foreach ($gruppe["geraete"] as $geraet) {
        zeigeGeraet($gruppe, $geraet);
    }
    echo "Datensätze: " . count($gruppe["geraete"])
        . ", Gesamtbestand: " . berechneBestand($gruppe["geraete"]) . "\n";
}

// Fragt so lange, bis ein nicht leerer Text eingegeben wird
function eingabePflicht($frage) {
    while (true) {
        $text = eingabe($frage);
        if ($text !== "") {
            return $text;
        }
        echo "Dieses Feld darf nicht leer sein.\n";
    }
}

// Fragt einen Bestand ab. Rückgabe: Integer, oder "" wenn $leerErlaubt und nichts eingegeben wurde.
// Wichtig: "0" ist ein gültiger Bestand und keine leere Eingabe!
function eingabeBestand($frage, $leerErlaubt) {
    while (true) {
        $text = eingabe($frage);
        if ($text === "" && $leerErlaubt) {
            return "";
        }
        if (istGueltigeZahl($text)) {
            return (int) $text;
        }
        echo "Bitte eine nichtnegative ganze Zahl eingeben.\n";
    }
}

// D3: neues Gerät erfassen. Rückgabe: aktualisierte Gruppe
function geraetAnlegen($gruppe) {
    zeigeUeberschrift("Gerät hinzufügen: " . $gruppe["name"]);

    $geraet = ["id" => naechsteId($gruppe["geraete"])];
    $geraet["bezeichnung"] = eingabePflicht("Bezeichnung: ");
    $geraet["bestand"] = eingabeBestand("Bestand: ", false);
    $geraet["standort"] = eingabePflicht("Standort: ");

    // Nur die Zusatzfelder genau dieser Gruppe abfragen
    foreach ($gruppe["felder"] as $feld) {
        $geraet[$feld] = eingabe("$feld (darf leer sein): ");
    }

    $gruppe["geraete"][] = $geraet;
    echo "Gerät mit ID " . $geraet["id"] . " angelegt.\n";
    return $gruppe;
}

// D4: Gerät ändern. Leere Eingabe behält den alten Wert, die ID bleibt gleich.
function geraetBearbeiten($gruppe, $id) {
    $index = findeIndexNachId($gruppe["geraete"], $id);
    if ($index === -1) {
        echo "Kein Gerät mit ID $id in dieser Gruppe.\n";
        return $gruppe;
    }
    $geraet = $gruppe["geraete"][$index];

    zeigeGeraet($gruppe, $geraet);
    echo "Leere Eingabe = alten Wert behalten. Zusatzfeld mit - leeren.\n";

    $text = eingabe("Bezeichnung [" . $geraet["bezeichnung"] . "]: ");
    if ($text !== "") {
        $geraet["bezeichnung"] = $text;
    }

    $bestand = eingabeBestand("Bestand [" . $geraet["bestand"] . "]: ", true);
    if ($bestand !== "") {
        $geraet["bestand"] = $bestand;
    }

    $text = eingabe("Standort [" . $geraet["standort"] . "]: ");
    if ($text !== "") {
        $geraet["standort"] = $text;
    }

    foreach ($gruppe["felder"] as $feld) {
        $text = eingabe("$feld [" . $geraet[$feld] . "]: ");
        if ($text === "-") {
            $geraet[$feld] = "";
        } elseif ($text !== "") {
            $geraet[$feld] = $text;
        }
    }

    $gruppe["geraete"][$index] = $geraet;   // geänderte Kopie an die gefundene Position zurückschreiben
    echo "Gerät mit ID $id geändert.\n";
    return $gruppe;
}

// D5: Gerät nach Bestätigung löschen. Die IDs der anderen Geräte bleiben unverändert.
function geraetLoeschen($gruppe, $id) {
    $index = findeIndexNachId($gruppe["geraete"], $id);
    if ($index === -1) {
        echo "Kein Gerät mit ID $id in dieser Gruppe.\n";
        return $gruppe;
    }

    zeigeGeraet($gruppe, $gruppe["geraete"][$index]);
    if (bestaetigen("Dieses Gerät wirklich löschen?") === false) {
        echo "Nicht gelöscht.\n";
        return $gruppe;
    }

    unset($gruppe["geraete"][$index]);
    $gruppe["geraete"] = array_values($gruppe["geraete"]);
    echo "Gerät gelöscht. Neuer Gesamtbestand: " . berechneBestand($gruppe["geraete"]) . "\n";
    return $gruppe;
}


// ============================================================
// CSV laden
// ============================================================

function ladeFehler($datei, $zeilennummer, $text) {
    echo "Ladefehler in $datei, Zeile $zeilennummer: $text\n";
}

// Prüft eine Zeile der Gruppendatei. Rückgabe: Gruppen-Array oder false
// $gruppen = die bisher geladenen Gruppen (für die Prüfung auf Doppelte)
function pruefeGruppenZeile($zeile, $gruppen, $nr) {
    if (count($zeile) !== 6) {
        ladeFehler(GRUPPEN_DATEI, $nr, "6 Spalten erwartet, " . count($zeile) . " gefunden");
        return false;
    }

    $idText = trim($zeile[0]);
    $name = trim($zeile[1]);
    $datei = trim($zeile[2]);

    // Erst prüfen, dann umwandeln
    if (istGueltigeZahl($idText) === false || (int) $idText === 0) {
        ladeFehler(GRUPPEN_DATEI, $nr, "ungültige ID '$idText'");
        return false;
    }
    $id = (int) $idText;
    if (findeIndexNachId($gruppen, $id) !== -1) {
        ladeFehler(GRUPPEN_DATEI, $nr, "doppelte ID $id");
        return false;
    }

    if ($name === "") {
        ladeFehler(GRUPPEN_DATEI, $nr, "leerer Gruppenname");
        return false;
    }
    if (nameVergeben($gruppen, $name, 0)) {
        ladeFehler(GRUPPEN_DATEI, $nr, "doppelter Gruppenname '$name'");
        return false;
    }

    // Dateiname muss <kennung>.csv sein und die Regeln für Kennungen erfüllen
    $kennung = substr($datei, 0, -4);
    if (substr($datei, -4) !== ".csv" || istGueltigeKennung($kennung) === false) {
        ladeFehler(GRUPPEN_DATEI, $nr, "ungültiger Dateiname '$datei'");
        return false;
    }
    if (dateiVergeben($gruppen, $datei)) {
        ladeFehler(GRUPPEN_DATEI, $nr, "doppelter Dateiname '$datei'");
        return false;
    }

    // Spalten extra1 bis extra3: leere Zellen sind keine Feldnamen
    $felder = [];
    for ($i = 3; $i < 6; $i++) {
        $feld = trim($zeile[$i]);
        if ($feld === "") {
            continue;
        }
        if (istGueltigerFeldname($feld) === false) {
            ladeFehler(GRUPPEN_DATEI, $nr, "ungültiges Zusatzfeld '$feld'");
            return false;
        }
        if (in_array($feld, $felder, true)) {
            ladeFehler(GRUPPEN_DATEI, $nr, "doppeltes Zusatzfeld '$feld'");
            return false;
        }
        $felder[] = $feld;
    }

    return [
        "id" => $id,
        "name" => $name,
        "datei" => $datei,
        "felder" => $felder,
        "geraete" => []
    ];
}

// Lädt die Geräte-CSV einer Gruppe. Rückgabe: Geräteliste oder false
function ladeGeraete($ordner, $gruppe) {
    $datei = $gruppe["datei"];
    $pfad = $ordner . "/" . $datei;

    // Fehlende Gerätedatei ist ein Fehler und nicht stillschweigend ein leerer Bestand
    if (file_exists($pfad) === false) {
        echo "Ladefehler: Die Datei $datei der Gruppe '" . $gruppe["name"] . "' fehlt.\n";
        return false;
    }
    $handle = fopen($pfad, "r");
    if ($handle === false) {
        echo "Ladefehler: $datei konnte nicht geöffnet werden.\n";
        return false;
    }

    $kopf = geraeteKopf($gruppe);
    if (fgetcsv($handle, 0, ";", '"', "") !== $kopf) {
        fclose($handle);
        ladeFehler($datei, 1, "Kopfzeile muss lauten: " . implode(";", $kopf));
        return false;
    }

    $geraete = [];
    $nr = 1;
    while (($zeile = fgetcsv($handle, 0, ";", '"', "")) !== false) {
        $nr++;
        if ($zeile === [null]) {   // leere Zeile überspringen
            continue;
        }
        if (count($zeile) !== count($kopf)) {
            fclose($handle);
            ladeFehler($datei, $nr, count($kopf) . " Spalten erwartet, " . count($zeile) . " gefunden");
            return false;
        }

        $idText = trim($zeile[0]);
        $bestandText = trim($zeile[2]);
        if (istGueltigeZahl($idText) === false || (int) $idText === 0) {
            fclose($handle);
            ladeFehler($datei, $nr, "ungültige ID '$idText'");
            return false;
        }
        if (findeIndexNachId($geraete, (int) $idText) !== -1) {
            fclose($handle);
            ladeFehler($datei, $nr, "doppelte ID $idText");
            return false;
        }
        if (istGueltigeZahl($bestandText) === false) {
            fclose($handle);
            ladeFehler($datei, $nr, "ungültiger Bestand '$bestandText'");
            return false;
        }
        if (trim($zeile[1]) === "" || trim($zeile[3]) === "") {
            fclose($handle);
            ladeFehler($datei, $nr, "Bezeichnung und Standort dürfen nicht leer sein");
            return false;
        }

        // Spaltenname aus der Kopfzeile => Wert aus der Zeile
        $geraet = [];
        foreach ($kopf as $spalte => $feld) {
            $geraet[$feld] = $zeile[$spalte];
        }
        $geraet["id"] = (int) $idText;
        $geraet["bestand"] = (int) $bestandText;
        $geraete[] = $geraet;
    }

    fclose($handle);
    return $geraete;
}

// Lädt alle Gruppen und Geräte in temporäre Arrays.
// Rückgabe: Gruppenliste (auch leer möglich) oder false bei einem Fehler.
// Deshalb beim Aufruf mit === false prüfen!
function ladeAlles($ordner) {
    $pfad = $ordner . "/" . GRUPPEN_DATEI;

    if (file_exists($pfad) === false) {
        echo "Keine " . GRUPPEN_DATEI . " gefunden – Start mit leerer Verwaltung.\n";
        return [];
    }
    $handle = fopen($pfad, "r");
    if ($handle === false) {
        echo "Ladefehler: " . GRUPPEN_DATEI . " konnte nicht geöffnet werden.\n";
        return false;
    }

    if (fgetcsv($handle, 0, ";", '"', "") !== GRUPPEN_KOPF) {
        fclose($handle);
        ladeFehler(GRUPPEN_DATEI, 1, "Kopfzeile muss lauten: " . implode(";", GRUPPEN_KOPF));
        return false;
    }

    $gruppen = [];
    $nr = 1;
    while (($zeile = fgetcsv($handle, 0, ";", '"', "")) !== false) {
        $nr++;
        if ($zeile === [null]) {
            continue;
        }
        $gruppe = pruefeGruppenZeile($zeile, $gruppen, $nr);
        if ($gruppe === false) {
            fclose($handle);
            return false;
        }
        $gruppen[] = $gruppe;
    }
    fclose($handle);

    // Zu jeder Gruppe die eigene Geräte-CSV laden
    foreach ($gruppen as $index => $gruppe) {
        $geraete = ladeGeraete($ordner, $gruppe);
        if ($geraete === false) {
            return false;
        }
        $gruppen[$index]["geraete"] = $geraete;
    }

    return $gruppen;
}


// ============================================================
// CSV speichern
// ============================================================

// Schreibt alle Zeilen in eine CSV-Datei. Rückgabe: true nur wenn alles geklappt hat
function schreibeCsv($pfad, $zeilen) {
    $handle = fopen($pfad, "w");
    if ($handle === false) {
        return false;
    }
    $ok = true;
    foreach ($zeilen as $zeile) {
        if (fputcsv($handle, $zeile, ";", '"', "") === false) {
            $ok = false;
        }
    }
    if (fclose($handle) === false) {
        $ok = false;
    }
    return $ok;
}

// Schreibt Gruppendatei und alle Gerätedateien.
// $alteDateien: Dateien vom letzten Laden/Speichern. Dateien gelöschter Gruppen werden entfernt.
// Rückgabe: true nur bei vollständigem Erfolg
function speichereAlles($ordner, $gruppen, $alteDateien) {
    if (is_dir($ordner) === false) {
        echo "Fehler: Der Ordner $ordner existiert nicht.\n";
        return false;
    }
    $alles_ok = true;

    // 1. Gruppendatei: immer genau drei Zusatzfeld-Spalten, unbenutzte bleiben leer
    $zeilen = [GRUPPEN_KOPF];
    foreach ($gruppen as $gruppe) {
        $zeile = [$gruppe["id"], $gruppe["name"], $gruppe["datei"]];
        for ($i = 0; $i < 3; $i++) {
            if ($i < count($gruppe["felder"])) {
                $zeile[] = $gruppe["felder"][$i];
            } else {
                $zeile[] = "";
            }
        }
        $zeilen[] = $zeile;
    }
    if (schreibeCsv($ordner . "/" . GRUPPEN_DATEI, $zeilen) === false) {
        echo "Fehler beim Schreiben von " . GRUPPEN_DATEI . "\n";
        $alles_ok = false;
    }

    // 2. Eine Gerätedatei pro Gruppe (auch leere Gruppen bekommen die Kopfzeile)
    foreach ($gruppen as $gruppe) {
        $kopf = geraeteKopf($gruppe);
        $zeilen = [$kopf];
        foreach ($gruppe["geraete"] as $geraet) {
            $zeile = [];
            foreach ($kopf as $feld) {
                $zeile[] = $geraet[$feld];
            }
            $zeilen[] = $zeile;
        }
        if (schreibeCsv($ordner . "/" . $gruppe["datei"], $zeilen) === false) {
            echo "Fehler beim Schreiben von " . $gruppe["datei"] . "\n";
            $alles_ok = false;
        }
    }

    // 3. Nur die Dateien gelöschter Gruppen entfernen – niemals alle CSV-Dateien im Ordner.
    //    Bei einem vorherigen Fehler lieber nichts löschen.
    if ($alles_ok) {
        $aktuelle = dateienDerGruppen($gruppen);
        foreach ($alteDateien as $datei) {
            $pfad = $ordner . "/" . $datei;
            if (in_array($datei, $aktuelle, true) === false && file_exists($pfad)) {
                if (unlink($pfad)) {
                    echo "Datei $datei der gelöschten Gruppe entfernt.\n";
                } else {
                    echo "Fehler: $datei konnte nicht gelöscht werden.\n";
                    $alles_ok = false;
                }
            }
        }
    }

    return $alles_ok;
}
