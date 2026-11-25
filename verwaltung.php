<?php
// verwaltung.php – Geräteverwaltung als PHP-Konsolenanwendung mit JSON-Speicherung
// Start: php verwaltung.php

require __DIR__ . "/funktionen.php";
require __DIR__ . "/json.php";

$ordner = __DIR__ . "/daten";                      // Quelle für den CSV-Import
$jsonPfad = $ordner . "/geraeteverwaltung.json";   // normaler Speicherort


// Untermenü Gerätegruppen. Rückgabe: aktualisierte Gruppenliste
function gruppenMenue($gruppen) {
    while (true) {
        zeigeUeberschrift("Gerätegruppen");
        echo "1 Gruppen auflisten\n";
        echo "2 Gruppe hinzufügen\n";
        echo "3 Gruppe bearbeiten\n";
        echo "4 Gruppe löschen\n";
        echo "0 Zurück\n";

        switch (eingabe("Auswahl: ")) {
            case "1":
                zeigeGruppen($gruppen);
                break;
            case "2":
                $gruppen = gruppeAnlegen($gruppen);
                break;
            case "3":
                zeigeGruppen($gruppen);
                if (count($gruppen) > 0) {
                    $id = eingabeId("ID der Gruppe: ");
                    if ($id !== -1) {
                        $gruppen = gruppeBearbeiten($gruppen, $id);
                    }
                }
                break;
            case "4":
                zeigeGruppen($gruppen);
                if (count($gruppen) > 0) {
                    $id = eingabeId("ID der Gruppe: ");
                    if ($id !== -1) {
                        $gruppen = gruppeLoeschen($gruppen, $id);
                    }
                }
                break;
            case "0":
                return $gruppen;
            default:
                echo "Ungültige Auswahl.\n";
        }
    }
}

// Untermenü Geräte einer Gruppe. Rückgabe: aktualisierte Gruppe
function geraeteMenue($gruppe) {
    while (true) {
        zeigeUeberschrift("Geräteverwaltung: " . $gruppe["name"]);
        echo "1 Geräte auflisten\n";
        echo "2 Gerät hinzufügen\n";
        echo "3 Gerät bearbeiten\n";
        echo "4 Gerät löschen\n";
        echo "0 Zurück\n";

        switch (eingabe("Auswahl: ")) {
            case "1":
                $anzeige = $gruppe; // Kopie, damit das Original unverändert bleibt
                if (count($anzeige["geraete"]) > 1) {
                    echo "Sortieren nach: 1 ID (Standard), 2 Bestand, 3 Standort\n";
                    $sort = eingabe("Auswahl (Enter = ID): ");

                    if ($sort === "2" || $sort === "3") {
                        // Bubblesort: benachbarte Geräte vergleichen und bei Bedarf tauschen
                        $liste = $anzeige["geraete"];
                        $anzahl = count($liste);

                        for ($runde = 0; $runde < $anzahl - 1; $runde++) {
                            for ($i = 0; $i < $anzahl - 1 - $runde; $i++) {
                                $tauschen = false;
                                if ($sort === "2" && $liste[$i]["bestand"] > $liste[$i + 1]["bestand"]) {
                                    $tauschen = true;
                                }
                                if ($sort === "3" && strcmp($liste[$i]["standort"], $liste[$i + 1]["standort"]) > 0) {
                                    $tauschen = true;
                                }

                                if ($tauschen) {
                                    $tmp = $liste[$i];
                                    $liste[$i] = $liste[$i + 1];
                                    $liste[$i + 1] = $tmp;
                                }
                            }
                        }
                        $anzeige["geraete"] = $liste;
                    }
                }
                zeigeGeraete($anzeige);
                break;
            case "2":
                $gruppe = geraetAnlegen($gruppe);
                break;
            case "3":
                if (count($gruppe["geraete"]) === 0) {
                    echo "Diese Gruppe enthält noch keine Geräte.\n";
                    break;
                }
                // Liste nur auf Wunsch – wer die ID kennt, muss nicht durch alle Geräte scrollen
                if (bestaetigen("Liste anzeigen?")) {
                    zeigeGeraete($gruppe);
                }
                $id = eingabeId("ID des Geräts: ");
                if ($id !== -1) {
                    $gruppe = geraetBearbeiten($gruppe, $id);
                }
                break;
            case "4":
                if (count($gruppe["geraete"]) === 0) {
                    echo "Diese Gruppe enthält noch keine Geräte.\n";
                    break;
                }
                if (bestaetigen("Liste anzeigen?")) {
                    zeigeGeraete($gruppe);
                }
                $id = eingabeId("ID des Geräts: ");
                if ($id !== -1) {
                    $gruppe = geraetLoeschen($gruppe, $id);
                }
                break;
            case "0":
                return $gruppe;
            default:
                echo "Ungültige Auswahl.\n";
        }
    }
}


// ============================================================
// Programmstart: gespeicherten Stand aus JSON laden
// ============================================================

zeigeUeberschrift("Geräteverwaltung");

$gruppen = ladeJson($jsonPfad);
if ($gruppen === false) {
    // Nicht mit leerer Verwaltung weitermachen – sonst könnte ein Speichern die Datei überschreiben
    exit("Start abgebrochen. Bitte die Datei " . basename($jsonPfad) . " prüfen.\n");
}
echo count($gruppen) . " Gruppe(n) geladen.\n";

$geaendert = false;
$laeuft = true;

while ($laeuft) {
    zeigeUeberschrift("Hauptmenü");
    if ($geaendert) {
        echo "(ungespeicherte Änderungen)\n";
    }
    echo "1 Gruppen verwalten\n";
    echo "2 Geräte verwalten\n";
    echo "3 JSON speichern\n";
    echo "4 JSON neu laden\n";
    echo "5 CSV-Daten importieren\n";
    echo "6 Geräte suchen\n";
    echo "0 Beenden\n";

    switch (eingabe("Auswahl: ")) {
        case "1":
            $neu = gruppenMenue($gruppen);
            // Hat sich etwas geändert? Arrays können direkt verglichen werden.
            if ($neu !== $gruppen) {
                $geaendert = true;
            }
            $gruppen = $neu;
            break;

        case "2":
            if (count($gruppen) === 0) {
                echo "Es gibt noch keine Gruppen. Bitte zuerst eine Gruppe anlegen.\n";
                break;
            }
            foreach ($gruppen as $gruppe) {
                echo "ID " . $gruppe["id"] . ": " . $gruppe["name"] . "\n";
            }
            $id = eingabeId("Gruppe wählen (ID): ");
            if ($id === -1) {
                break;
            }
            $index = findeIndexNachId($gruppen, $id);
            if ($index === -1) {
                echo "Keine Gruppe mit ID $id vorhanden.\n";
                break;
            }
            $neu = geraeteMenue($gruppen[$index]);
            if ($neu !== $gruppen[$index]) {
                $geaendert = true;
                $gruppen[$index] = $neu;
            }
            break;

        case "3":
            if (speichereJson($jsonPfad, $gruppen)) {
                echo "Gespeichert in " . basename($jsonPfad) . ".\n";
                $geaendert = false;
            } else {
                echo "Speichern war NICHT erfolgreich! Die bisherige Datei ist unverändert.\n";
            }
            break;

        case "4":
            if ($geaendert && bestaetigen("Ungespeicherte Änderungen gehen verloren. Trotzdem neu laden?") === false) {
                break;
            }
            $neu = ladeJson($jsonPfad);
            if ($neu === false) {
                echo "Neu laden fehlgeschlagen. Der bisherige Stand im Arbeitsspeicher bleibt erhalten.\n";
            } else {
                $gruppen = $neu;
                $geaendert = false;
                echo count($gruppen) . " Gruppe(n) neu geladen.\n";
            }
            break;

        case "5":
            echo "Der CSV-Import liest geraetegruppen.csv und die Gerätedateien im Ordner daten.\n";
            echo "Die aktuelle Liste wird dabei vollständig ERSETZT, nicht zusammengeführt.\n";
            echo "Die CSV-Dateien werden nur gelesen. Dauerhaft wird der Import erst mit 'JSON speichern'.\n";
            if ($geaendert) {
                echo "Achtung: Deine ungespeicherten Änderungen gehen dabei verloren.\n";
            }
            if (bestaetigen("CSV-Daten importieren?") === false) {
                echo "Import abgebrochen. Die bisherigen Daten bleiben erhalten.\n";
                break;
            }
            $neu = importiereCsv($ordner);
            if ($neu === false) {
                echo "Import fehlgeschlagen. Die bisherigen Daten bleiben erhalten.\n";
            } else {
                $gruppen = $neu;
                $geaendert = true;   // Import ist zunächst nur ein ungespeicherter Arbeitsstand
                echo count($gruppen) . " Gruppe(n) importiert (noch nicht gespeichert).\n";
            }
            break;

        case "6":
            // Suche ändert nichts an den Daten, deshalb kein $geaendert
            sucheGeraete($gruppen);
            break;

        case "0":
            if ($geaendert === false) {
                $laeuft = false;
                break;
            }
            echo "Es gibt ungespeicherte Änderungen.\n";
            echo "1 Speichern und beenden\n";
            echo "2 Ohne Speichern beenden\n";
            echo "0 Abbrechen (zurück zum Menü)\n";
            $antwort = eingabe("Auswahl: ");
            if ($antwort === "1") {
                if (speichereJson($jsonPfad, $gruppen)) {
                    echo "Gespeichert in " . basename($jsonPfad) . ".\n";
                    $laeuft = false;
                } else {
                    echo "Speichern fehlgeschlagen. Das Programm läuft weiter.\n";
                }
            } elseif ($antwort === "2") {
                $laeuft = false;
            } elseif ($antwort !== "0") {
                echo "Ungültige Auswahl. Zurück zum Hauptmenü.\n";
            }
            break;

        default:
            echo "Ungültige Auswahl.\n";
    }
}

echo "Programm beendet.\n";
