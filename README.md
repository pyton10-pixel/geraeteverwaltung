# Geräteverwaltung mit PHP und CSV

Menügesteuerte PHP-Konsolenanwendung zur Verwaltung von Gerätegruppen (z. B. Notebook, Beamer) und deren Gerätebeständen. Jede Gruppe hat eigene Zusatzfelder. Die Daten werden in CSV-Dateien gespeichert.

## Startanleitung

```
php verwaltung.php
```

Auf dem Schul-PC (PHP nicht im PATH):

```
C:\xampp\php\php.exe verwaltung.php
```

Getestet mit PHP 8.2. Der Ordner `daten/` muss vorhanden sein.

## Dateien

```
geraeteverwaltung/
├── verwaltung.php        Hauptprogramm: Laden beim Start, Hauptmenü, Untermenüs
├── funktionen.php        alle Funktionen (Eingabe, Gruppen, Geräte, CSV)
├── README.md
└── daten/
    ├── geraetegruppen.csv
    ├── notebook.csv
    └── beamer.csv
```

Mitgelieferte Beispieldaten: 2 Gruppen (Notebook, Beamer), 4 Gerätedatensätze.

## Vorhandene Funktionen (Pflichtversion)

| Bereich | Umgesetzt |
|---|---|
| Menüs | Hauptmenü, Gruppen- und Geräte-Untermenü, ungültige Eingaben → Meldung, 0 = Zurück |
| G1 Gruppen auflisten | ID, Name, Datei, Zusatzfelder, Anzahl Datensätze, Gesamtbestand |
| G2 Gruppe anlegen | Name + Dateikennung + bis zu 3 Zusatzfelder, alle Regeln geprüft (eindeutig, erlaubte Zeichen, reservierte Namen) |
| G3 Gruppe bearbeiten | Name änderbar, Dateikennung bleibt; Zusatzfelder nur bei leerer Gruppe |
| G4 Gruppe löschen | nur ohne Datensätze, mit Bestätigung; Datei wird erst beim Speichern entfernt |
| D1–D5 Geräte | Gruppe per ID wählen, auflisten, anlegen, bearbeiten (leer = alter Wert, 0 ist gültig, `-` leert ein Zusatzfeld), löschen mit Bestätigung |
| IDs | größte ID + 1, Auswahl immer per ID, nie per Listenposition |
| Laden | Kopfzeilen, Spaltenanzahl, IDs, Bestände, Doppelte, Dateinamen geprüft; fehlende Gerätedatei = Fehler; erst temporär laden, nur bei Erfolg übernehmen |
| Speichern | alle Dateien inkl. leerer Gruppen mit Kopfzeile; Rückgabewerte geprüft; nur Dateien gelöschter Gruppen werden entfernt |
| Änderungen | `$geaendert`; Nachfrage beim Neu laden und Beenden (speichern / ohne speichern / abbrechen) |

Beim Programmstart mit fehlerhaften CSV-Dateien bricht das Programm ab, statt mit leerer Verwaltung zu starten. So kann ein späteres Speichern die Originaldateien nicht überschreiben.

## Noch fehlende Funktionen (Zusatzaufgaben)

- Zusatzfelder bei vorhandenen Geräten hinzufügen oder umbenennen (Migration)
- Suche nach Bezeichnung oder Standort
- Bestandsübersicht pro Standort
- Sicherungskopien vor dem Überschreiben
- automatische Prüfung „Seriennummer → Bestand 1“
- mehr als drei Zusatzfelder

## Durchgeführte Tests

Alle 14 Punkte des Abnahmetests wurden mit Kopien des Datenordners durchgespielt:

| Nr. | Test | Ergebnis |
|---|---|---|
| 1–2 | Notebook (seriennummer, ram) und Beamer (aufloesung, anschluss) anlegen | ok |
| 3–4 | Notebook: Bestand 1 und 4 → Gesamtbestand 5 bei 2 Datensätzen | ok |
| 5 | Beamer-Gerät: nur Beamer-Felder werden abgefragt | ok |
| 6 | Standort `Lager; Nord` übersteht Speichern und Laden | ok |
| 7 | Bestand auf 0 → Datensatz bleibt erhalten | ok |
| 8 | Notebook → Mobile Rechner, Datei notebook.csv bleibt zugeordnet | ok |
| 9 | Zusatzfelder bei gefüllter Gruppe ändern → abgelehnt | ok |
| 10 | Gefüllte Gruppe löschen → abgelehnt | ok |
| 11 | Mittleres Gerät (ID 2) gelöscht → IDs 1 und 3 bleiben | ok |
| 12 | Speichern, Neustart, Inhalte verglichen | ok |
| 13 | Kopfzeile von beamer.csv beschädigt → Ladefehler, Arbeitsspeicher bleibt; Start mit kaputter Datei wird abgebrochen | ok |
| 14 | Beamer-Geräte und Gruppe gelöscht, gespeichert → beamer.csv entfernt, notebook.csv unverändert (Prüfsumme gleich) | ok |

Zusätzlich geprüft: doppelter Gruppenname (Groß-/Kleinschreibung egal), doppelte Dateikennung, reservierter Feldname `id`, ungültiger Bestand (`x`), Abbruch beim Beenden mit ungespeicherten Änderungen.

## KI-Nutzung

Dieses Projekt wurde mit Hilfe von KI erstellt. Verwendetes Werkzeug: **Claude Code** in Visual Studio Code, am 10.11.2026.

| Teil | Wer |
|---|---|
| Programmcode (`verwaltung.php`, `funktionen.php`) | mit KI erstellt, auf Basis meiner Übungen `geraeteliste.php` und `dateien2.php` |
| Beispieldaten in `daten/` | mit KI erstellt (über das Programm selbst eingegeben) |
| Automatisierte Abnahmetests 1–14 | KI |
| Manuelle Tests in Cmder (u. a. Test 7: Bestand 0 → Datensatz bleibt, Summe stimmt) | ich |
| Aufgabenstellung an die KI, Prüfung und Abgabe | ich |
| README | mit KI erstellt, von mir angepasst |

Alle Kommentare im Code sind auf Deutsch, damit der Ablauf nachvollziehbar ist.
