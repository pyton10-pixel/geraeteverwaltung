<?php
// verwaltung.php – Geräteverwaltung als PHP-Konsolenanwendung mit CSV-Speicherung
// Start: php verwaltung.php

require __DIR__ . "/funktionen.php";

$ordner = __DIR__ . "/daten";


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
                zeigeGeraete($gruppe);
                break;
            case "2":
                $gruppe = geraetAnlegen($gruppe);
                break;
            case "3":
                zeigeGeraete($gruppe);
                if (count($gruppe["geraete"]) > 0) {
                    $id = eingabeId("ID des Geräts: ");
                    if ($id !== -1) {
                        $gruppe = geraetBearbeiten($gruppe, $id);
                    }
                }
                break;
            case "4":
                zeigeGeraete($gruppe);
                if (count($gruppe["geraete"]) > 0) {
                    $id = eingabeId("ID des Geräts: ");
                    if ($id !== -1) {
                        $gruppe = geraetLoeschen($gruppe, $id);
                    }
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
// Programmstart: gespeicherten Stand laden
// ============================================================

zeigeUeberschrift("Geräteverwaltung");

$gruppen = ladeAlles($ordner);
if ($gruppen === false) {
    // Nicht mit leerer Verwaltung weitermachen – sonst könnte ein Speichern die Dateien überschreiben
    exit("Start abgebrochen. Bitte die CSV-Dateien im Ordner daten prüfen.\n");
}
echo count($gruppen) . " Gruppe(n) geladen.\n";

$bekannteDateien = dateienDerGruppen($gruppen);   // für das Entfernen gelöschter Gruppen-Dateien
$geaendert = false;
$laeuft = true;

while ($laeuft) {
    zeigeUeberschrift("Hauptmenü");
    if ($geaendert) {
        echo "(ungespeicherte Änderungen)\n";
    }
    echo "1 Gerätegruppen verwalten\n";
    echo "2 Geräte einer Gruppe verwalten\n";
    echo "3 Alles speichern\n";
    echo "4 Gespeicherten Stand neu laden\n";
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
            if (speichereAlles($ordner, $gruppen, $bekannteDateien)) {
                echo "Alles gespeichert.\n";
                $geaendert = false;
                $bekannteDateien = dateienDerGruppen($gruppen);
            } else {
                echo "Speichern war NICHT vollständig erfolgreich!\n";
                $bekannteDateien = array_unique(array_merge($bekannteDateien, dateienDerGruppen($gruppen)));
            }
            break;

        case "4":
            if ($geaendert && bestaetigen("Ungespeicherte Änderungen gehen verloren. Trotzdem neu laden?") === false) {
                break;
            }
            $neu = ladeAlles($ordner);
            if ($neu === false) {
                echo "Neu laden fehlgeschlagen. Der bisherige Stand im Arbeitsspeicher bleibt erhalten.\n";
            } else {
                $gruppen = $neu;
                $bekannteDateien = dateienDerGruppen($gruppen);
                $geaendert = false;
                echo count($gruppen) . " Gruppe(n) neu geladen.\n";
            }
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
                if (speichereAlles($ordner, $gruppen, $bekannteDateien)) {
                    echo "Alles gespeichert.\n";
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
