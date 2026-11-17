<?php
// json.php – Speichern und Laden im JSON-Format, CSV-Import und gemeinsame Datenprüfung
// Wird von verwaltung.php nach funktionen.php mit require eingebunden.

// Version des Dateiformats. Ändert sich der Aufbau der JSON-Datei, wird sie erhöht.
const SCHEMA_VERSION = 1;


// ============================================================
// Gemeinsame Datenprüfung
// Wird vor dem Speichern, nach dem Laden und nach dem CSV-Import verwendet.
// Alle Prüfungen geben einen Fehlertext zurück oder "", wenn alles passt.
// ============================================================

function istNichtLeererText($wert) {
    return is_string($wert) && trim($wert) !== "";
}

function istPositiveId($wert) {
    return is_int($wert) && $wert > 0;
}

// Zusatzwert: ein Text oder eine Liste von Texten (z. B. mehrere Seriennummern)
function istGueltigerZusatzwert($wert) {
    if (is_string($wert)) {
        return true;
    }
    if (is_array($wert) === false || array_is_list($wert) === false) {
        return false;
    }
    foreach ($wert as $teil) {
        if (is_string($teil) === false) {
            return false;
        }
    }
    return true;
}

// Prüft, ob ein Eintrag genau die erwarteten Schlüssel hat – keiner fehlt, keiner zu viel
function pruefeSchluessel($eintrag, $erwartet, $wo) {
    if (is_array($eintrag) === false) {
        return "$wo ist kein Objekt";
    }
    foreach ($erwartet as $schluessel) {
        if (array_key_exists($schluessel, $eintrag) === false) {
            return "$wo: Feld '$schluessel' fehlt";
        }
    }
    foreach (array_keys($eintrag) as $schluessel) {
        if (in_array($schluessel, $erwartet, true) === false) {
            return "$wo: unbekanntes Feld '$schluessel'";
        }
    }
    return "";
}

// Prüft einen Gerätedatensatz. $felder = Zusatzfelder der Gruppe
function pruefeGeraet($geraet, $felder, $wo) {
    $fehler = pruefeSchluessel($geraet, array_merge(FESTE_FELDER, $felder), $wo);
    if ($fehler !== "") {
        return $fehler;
    }
    if (istPositiveId($geraet["id"]) === false) {
        return "$wo: id muss eine positive ganze Zahl sein";
    }
    if (istNichtLeererText($geraet["bezeichnung"]) === false) {
        return "$wo: bezeichnung muss ein nicht leerer Text sein";
    }
    // is_int: auch "3" (Text) oder 3.5 werden abgelehnt
    if (is_int($geraet["bestand"]) === false || $geraet["bestand"] < 0) {
        return "$wo: bestand muss eine nichtnegative ganze Zahl sein";
    }
    if (istNichtLeererText($geraet["standort"]) === false) {
        return "$wo: standort muss ein nicht leerer Text sein";
    }
    foreach ($felder as $feld) {
        if (istGueltigerZusatzwert($geraet[$feld]) === false) {
            return "$wo: $feld muss Text oder eine Liste von Texten sein";
        }
    }
    return "";
}

// Prüft die Zusatzfeld-Definition einer Gruppe (höchstens 3, gültig, eindeutig)
function pruefeFelder($felder, $wo) {
    if (is_array($felder) === false || array_is_list($felder) === false) {
        return "$wo: felder muss eine Liste sein";
    }
    if (count($felder) > 3) {
        return "$wo: höchstens 3 Zusatzfelder erlaubt";
    }
    foreach ($felder as $nr => $feld) {
        if (is_string($feld) === false || istGueltigerFeldname($feld) === false) {
            return "$wo: ungültiges Zusatzfeld Nr. " . ($nr + 1);
        }
        if (array_search($feld, $felder, true) !== $nr) {
            return "$wo: doppeltes Zusatzfeld '$feld'";
        }
    }
    return "";
}

// Prüft den gesamten Datenbestand (Liste aller Gruppen mit ihren Geräten)
function findeDatenFehler($gruppen) {
    if (is_array($gruppen) === false || array_is_list($gruppen) === false) {
        return "gruppen ist keine Liste";
    }

    // Bereits gesehene Werte als Schlüssel merken: isset() ist schneller als die Liste jedes Mal zu durchsuchen
    $gruppenIds = [];
    $namen = [];
    $dateien = [];

    foreach ($gruppen as $nr => $gruppe) {
        $wo = "Gruppe Nr. " . ($nr + 1);
        $fehler = pruefeSchluessel($gruppe, ["id", "name", "datei", "felder", "geraete"], $wo);
        if ($fehler !== "") {
            return $fehler;
        }

        if (istPositiveId($gruppe["id"]) === false) {
            return "$wo: id muss eine positive ganze Zahl sein";
        }
        if (isset($gruppenIds[$gruppe["id"]])) {
            return "$wo: doppelte Gruppen-ID " . $gruppe["id"];
        }
        $gruppenIds[$gruppe["id"]] = true;
        $wo = "Gruppe ID " . $gruppe["id"];

        if (istNichtLeererText($gruppe["name"]) === false) {
            return "$wo: name muss ein nicht leerer Text sein";
        }
        $nameKlein = strtolower(trim($gruppe["name"]));
        if (isset($namen[$nameKlein])) {
            return "$wo: doppelter Gruppenname '" . $gruppe["name"] . "'";
        }
        $namen[$nameKlein] = true;

        $datei = $gruppe["datei"];
        if (is_string($datei) === false || substr($datei, -4) !== ".csv"
            || istGueltigeKennung(substr($datei, 0, -4)) === false) {
            return "$wo: ungültiger Dateiname";
        }
        if (isset($dateien[$datei])) {
            return "$wo: doppelter Dateiname '$datei'";
        }
        $dateien[$datei] = true;

        $fehler = pruefeFelder($gruppe["felder"], $wo);
        if ($fehler !== "") {
            return $fehler;
        }

        if (is_array($gruppe["geraete"]) === false || array_is_list($gruppe["geraete"]) === false) {
            return "$wo: geraete muss eine Liste sein";
        }
        $geraeteIds = [];
        foreach ($gruppe["geraete"] as $gNr => $geraet) {
            $fehler = pruefeGeraet($geraet, $gruppe["felder"], "$wo, Gerät Nr. " . ($gNr + 1));
            if ($fehler !== "") {
                return $fehler;
            }
            if (isset($geraeteIds[$geraet["id"]])) {
                return "$wo: doppelte Geräte-ID " . $geraet["id"];
            }
            $geraeteIds[$geraet["id"]] = true;
        }
    }
    return "";
}


// ============================================================
// JSON speichern und laden
// ============================================================

// Speichert alle Gruppen als JSON. Rückgabe: true nur bei vollständigem Erfolg
function speichereJson($pfad, $gruppen) {
    // 1. Erst prüfen – fehlerhafte Daten werden gar nicht geschrieben
    $fehler = findeDatenFehler($gruppen);
    if ($fehler !== "") {
        echo "Speichern abgebrochen, Daten fehlerhaft: $fehler\n";
        return false;
    }

    // 2. In JSON-Text umwandeln. Kein JSON_NUMERIC_CHECK: "00123" soll Text bleiben.
    $daten = [
        "schema_version" => SCHEMA_VERSION,
        "gruppen" => $gruppen
    ];
    try {
        $json = json_encode($daten, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    } catch (JsonException $e) {
        echo "Fehler beim Umwandeln in JSON: " . $e->getMessage() . "\n";
        return false;
    }

    if (is_dir(dirname($pfad)) === false) {
        echo "Fehler: Der Ordner " . dirname($pfad) . " existiert nicht.\n";
        return false;
    }

    // 3. Erst eine temporäre Datei im selben Ordner schreiben ...
    $temp = $pfad . ".tmp";
    if (file_put_contents($temp, $json) !== strlen($json)) {
        echo "Fehler beim Schreiben von " . basename($temp) . "\n";
        if (file_exists($temp)) {
            unlink($temp);
        }
        return false;
    }

    // 4. ... und dann umbenennen. Geht etwas schief, bleibt die alte Datei vollständig erhalten.
    if (rename($temp, $pfad) === false) {
        echo "Fehler: " . basename($temp) . " konnte nicht in " . basename($pfad) . " umbenannt werden.\n";
        unlink($temp);
        return false;
    }
    return true;
}

// Lädt alle Gruppen aus der JSON-Datei.
// Rückgabe: Gruppenliste (auch leer möglich) oder false bei einem Fehler – deshalb mit === false prüfen!
function ladeJson($pfad) {
    $datei = basename($pfad);

    if (file_exists($pfad) === false) {
        echo "Keine $datei gefunden – Start mit leerer Verwaltung.\n";
        return [];
    }

    // 1. Lesen
    $text = file_get_contents($pfad);
    if ($text === false) {
        echo "Ladefehler: $datei konnte nicht gelesen werden.\n";
        return false;
    }
    if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
        $text = substr($text, 3);   // BOM entfernen (z. B. nach Bearbeiten mit dem Windows-Editor)
    }
    if (trim($text) === "") {
        echo "Ladefehler: $datei ist leer.\n";
        return false;
    }

    // 2. Decodieren: JSON-Text → PHP-Array
    try {
        $daten = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        echo "Ladefehler: $datei ist kein gültiges JSON (" . $e->getMessage() . ").\n";
        return false;
    }

    // 3. Gültige JSON-Syntax heißt noch nicht gültige Daten → Aufbau und Inhalt prüfen
    $fehler = pruefeSchluessel($daten, ["schema_version", "gruppen"], $datei);
    if ($fehler !== "") {
        echo "Ladefehler: $fehler\n";
        return false;
    }
    if ($daten["schema_version"] !== SCHEMA_VERSION) {
        echo "Ladefehler: unbekannte schema_version " . json_encode($daten["schema_version"])
            . " (erwartet: " . SCHEMA_VERSION . ")\n";
        return false;
    }
    $fehler = findeDatenFehler($daten["gruppen"]);
    if ($fehler !== "") {
        echo "Ladefehler in $datei: $fehler\n";
        return false;
    }

    return $daten["gruppen"];
}


// ============================================================
// CSV-Import
// ============================================================

// Liest die CSV-Dateien mit ladeAlles(). Die CSV-Dateien werden dabei nur gelesen, nie verändert.
// Rückgabe: Gruppenliste oder false
function importiereCsv($ordner) {
    // Beim Import ist eine fehlende Gruppendatei ein Fehler (ladeAlles würde sonst leer starten)
    if (file_exists($ordner . "/" . GRUPPEN_DATEI) === false) {
        echo "Importfehler: " . GRUPPEN_DATEI . " fehlt im Ordner daten.\n";
        return false;
    }
    $gruppen = ladeAlles($ordner);
    if ($gruppen === false) {
        return false;
    }
    $fehler = findeDatenFehler($gruppen);
    if ($fehler !== "") {
        echo "Importfehler: $fehler\n";
        return false;
    }
    return $gruppen;
}
