# Geräteverwaltung mit PHP und JSON

Menügesteuerte PHP-Konsolenanwendung zur Verwaltung von Gerätegruppen (z. B. Notebook, Beamer) und deren Gerätebeständen. Jede Gruppe hat eigene Zusatzfelder.

Diese Version (Erweiterungsauftrag) speichert alle Daten in **einer JSON-Datei**. Die bisherigen CSV-Dateien dienen nur noch als Quelle für einen gezielten Import. Die reine CSV-Version liegt auf dem Branch `main`.

## Startanleitung

Unter Windows am einfachsten über die Startdatei (wählt automatisch das PHP aus Laragon, sonst PHP im PATH oder XAMPP):

```
start.cmd
```

Oder direkt:

```
php verwaltung.php
```

Auf dem Schul-PC (PHP nicht im PATH):

```
C:\xampp\php\php.exe verwaltung.php
```

Getestet mit PHP 8.2 (XAMPP). Benötigt PHP 8.1 oder neuer (`array_is_list`, `JSON_THROW_ON_ERROR`). Der Ordner `daten/` muss vorhanden sein.

Fehlt `daten/geraeteverwaltung.json`, startet das Programm mit leerer Verwaltung. Die bisherigen Beispieldaten holt man dann mit Menüpunkt **5 CSV-Daten importieren** und **3 JSON speichern**.

## Hauptmenü

```
1 Gruppen verwalten
2 Geräte verwalten
3 JSON speichern
4 JSON neu laden
5 CSV-Daten importieren
6 Geräte suchen
0 Beenden
```

Ungespeicherte Änderungen werden im Hauptmenü angezeigt. Vor dem Neu laden, vor dem Import und beim Beenden fragt das Programm nach.

## Erweiterung Benutzeroberfläche

Auftrag: `erweiterung_Geraeteverwaltung_UI.md` (Testdaten, UX-Mängel, Verbesserungen, Datenmodell).

| Teil | Umsetzung |
|---|---|
| Testdaten | `generate_testdata.php` erzeugt 10 Gruppen mit je 35–45 Geräten (zufällig, insgesamt ca. 400) und speichert sie mit `speichereJson()` |
| UX-Mängel | Liste in `ux_maengel.md` mit Status (behoben / teilweise / offen) |
| Tabellenansicht | `zeigeGeraete()` zeigt eine Zeile pro Gerät mit festen Spaltenbreiten. Hilfsfunktion `spalte()` gleicht Umlaute aus (`str_pad` zählt Bytes, `mb_strlen` Buchstaben); leere Werte erscheinen als `-` |
| Suche | Menüpunkt 6, `sucheGeraete()`: sucht in allen Gruppen in Bezeichnung, Standort und Zusatzfeldern (Groß-/Kleinschreibung egal, außer bei Umlauten) |
| Bearbeiten | `geraetBearbeiten()`: Auswahl „Was ändern?“, nur das gewählte Feld wird abgefragt, `0` bricht ab, danach Kontrollausgabe |
| Liste vor ID-Eingabe | bei Bearbeiten und Löschen nur auf Wunsch („Liste anzeigen? (j/n)“) |
| Eingabeprüfung | Bezeichnung und Standort dürfen keine reine Zahl sein (`is_numeric`), beim Anlegen und Ändern |
| Standort-Auswahl | `eingabeStandort()`: vorhandene Standorte der Gruppe als nummerierte Liste (ohne Doppelte, alphabetisch), `0` = neuen Standort eingeben; beim Anlegen und Ändern. Verhindert Tippfehler wie „Serverraum 1“ statt „Serverraum 01“ |
| Sortierung | „Geräte auflisten“ fragt: 1 ID, 2 Bestand, 3 Standort. Sortiert wird eine Kopie mit Bubblesort (zwei `for`-Schleifen, Tausch über `$tmp`); die gespeicherte Reihenfolge bleibt gleich |
| Datenmodell | Ordner `datenbank/`: ER-Modell (Chen-Notation), Relationenmodell als PDF, SQL, DBML, Erklärung in `datenmodell.md` |

Offen (freiwillig laut Auftrag): Undo, seitenweise Ausgabe.

## Dateien

```
geraeteverwaltung/
├── verwaltung.php        Hauptprogramm: Laden beim Start, Hauptmenü, Untermenüs
├── funktionen.php        Eingabe, Gruppen- und Geräteverwaltung, CSV laden/speichern
├── json.php              JSON speichern/laden, gemeinsame Datenprüfung, CSV-Import
├── start.cmd             Start mit Laragon-PHP (Fallback: PATH, XAMPP)
├── generate_testdata.php Testdaten erzeugen (überschreibt daten/geraeteverwaltung.json)
├── tabelle_test.php      Übung: Tabellenausgabe mit str_pad
├── ux_maengel.md         UX-Mängelliste mit Status
├── README.md
├── datenbank/
│   ├── geraeteverwaltung.sql       Tabellen für MariaDB/MySQL
│   ├── geraeteverwaltung.dbml      Modell für dbdiagram.io
│   ├── erm.pdf, erm.png, erm.svg   ER-Modell (Chen-Notation)
│   ├── datenmodell.pdf             Relationenmodell (Tabellen mit Schlüsseln)
│   └── datenmodell.md              Erklärung: Wiederholungen, ER-Modell, Tabellen, Entscheidungen
└── daten/
    ├── geraeteverwaltung.json      Arbeitsdaten (Testdaten, 10 Gruppen)
    ├── geraetegruppen.csv          CSV-Quelle für den Import
    └── notebook.csv, beamer.csv, … eine CSV pro Gruppe (nur Import)
```

Arbeitsdaten: Testdaten aus `generate_testdata.php` (10 Gruppen, ca. 400 Geräte). Die CSV-Dateien enthalten noch die älteren Beispieldaten (476 Geräte); ein CSV-Import ersetzt die Testdaten durch diese.

## Aufbau der JSON-Datei

```json
{
    "schema_version": 1,
    "gruppen": [
        {
            "id": 1,
            "name": "Notebook",
            "datei": "notebook.csv",
            "felder": ["seriennummer", "ram", "prozessor"],
            "geraete": [
                {
                    "id": 1,
                    "bezeichnung": "ThinkPad T14",
                    "bestand": 1,
                    "standort": "Raum 101",
                    "seriennummer": "NB-001",
                    "ram": "16 GB",
                    "prozessor": "Ryzen 5"
                }
            ]
        }
    ]
}
```

- `id` und `bestand` sind Zahlen, alle anderen Werte Text. Seriennummern wie `00123` bleiben Text (kein `JSON_NUMERIC_CHECK`).
- Ein Zusatzwert darf auch eine Liste von Texten sein, z. B. `"seriennummer": ["0019382487243", "367467823"]` (wie im Beispiel des Auftrags).
- `datei` ist ein historisches Feld aus der CSV-Version und bleibt vorerst erhalten.

## Umsetzung

| Bereich | Umgesetzt |
|---|---|
| Verwaltung | Gruppen und Geräte anlegen, auflisten, bearbeiten, löschen wie in der CSV-Version; Gesamtbestand = Summe der Bestände; max. 3 Zusatzfelder; Zusatzfelder nur bei leerer Gruppe änderbar |
| `speichereJson($pfad, $gruppen)` | prüft zuerst den Datenbestand, schreibt lesbares UTF-8-JSON in eine temporäre Datei (`.tmp`) im selben Ordner und benennt sie dann um; Erfolg erst danach; Datei- und Konvertierungsfehler werden gemeldet |
| `ladeJson($pfad)` | ohne Datei: leere Verwaltung; leere, unlesbare oder kaputte Datei: Fehler; erst decodieren, dann `schema_version` und Aufbau prüfen; Rückgabe `false` bei Fehler |
| Gemeinsame Prüfung `findeDatenFehler($gruppen)` | wird beim Speichern, beim Laden und nach dem CSV-Import verwendet: Pflichtfelder, keine unbekannten Felder, Typen (`bestand` ganze Zahl ≥ 0), eindeutige IDs, Gruppennamen und Dateinamen, gültige Zusatzfelder |
| Programmstart | fehlerhafte JSON-Datei → Abbruch, kein Weiterarbeiten mit leeren Daten |
| CSV-Import (Menüpunkt 5) | nur auf Wunsch, mit Erklärung und Bestätigung; ersetzt die Liste (kein Zusammenführen); fehlende `geraetegruppen.csv` = Fehler; alle CSV werden vollständig mit `ladeAlles()` geladen und geprüft; erst dann wird der Arbeitsspeicher ersetzt; Ergebnis zunächst ungespeichert; CSV-Dateien werden nur gelesen |

## Durchgeführte Tests

Alle 14 Abnahmetests wurden automatisiert mit Kopien des Programms und des Datenordners durchgespielt:

| Nr. | Test | Ergebnis |
|---|---|---|
| 1 | Ohne JSON starten → leere Verwaltung | ok |
| 2 | Notebook (seriennummer, ram) und Großgeräte (aufloesung) mit zusammen 3 Geräten angelegt | ok |
| 3 | JSON gespeichert, neu gestartet, Inhalte verglichen | ok |
| 4 | Bestand nach dem Laden `int(0)`, Seriennummer `00123` bleibt `string(5) "00123"` | ok |
| 5 | Bestand 0, Umlaute (`Großgeräte`, `Ä`), Anführungszeichen (`Gerät "Ä"`) und Semikolon (`Lager; Nord`) überstehen Speichern und Laden | ok |
| 6 | Leere Gruppe sowie komplett leere Verwaltung (`"gruppen": []`) gespeichert und geladen | ok |
| 7 | Gruppe umbenannt und Gerät ID 1 gelöscht → übrige IDs unverändert | ok |
| 8 | Kopie mit entferntem Komma → „kein gültiges JSON (Syntax error)“ | ok |
| 9 | Bestand `-4` und Bestand `"4"` (Text) → abgelehnt | ok |
| 10 | Doppelte Geräte-ID, fehlendes Pflichtfeld `standort`, `schema_version` 2 → abgelehnt | ok |
| 11 | CSV importiert, JSON gespeichert, neu geladen → Daten identisch mit `ladeAlles()` (10 Gruppen) | ok |
| 12 | Prüfsummen aller CSV-Dateien vor und nach dem Import gleich | ok |
| 13 | Import mit „n“ abgebrochen und Import mit beschädigter `beamer.csv` → vorherige Daten (inkl. neuer Gruppe) bleiben | ok |
| 14 | Neu laden und Beenden mit ungespeicherten Änderungen → Rückfrage | ok |

**Manuelle Tests (selbst in Cmder mit Laragon-PHP 8.3 durchgeführt):**

- Start mit Beispiel-JSON → 10 Gruppen geladen, Datensätze und Gesamtbestand werden angezeigt
- Gerät mit Bestand 0 und Seriennummer `00123` angelegt → neue ID 61 (größte ID + 1), Hinweis „ungespeicherte Änderungen“, gespeichert; in der JSON-Datei steht `"bestand": 0` als Zahl und `"seriennummer": "00123"` als Text (Test 4)
- CSV-Import mit „n“ abgebrochen → Daten bleiben (Test 13)
- CSV-Import mit „j“ → Liste ersetzt, Notebook wieder 60 statt 61 Datensätze, erst ungespeichert, dann mit „JSON speichern“ dauerhaft (Test 11)
- Gruppe Notebook in „Test“ umbenannt (Zusatzfelder bei gefüllter Gruppe wurden abgelehnt) → „JSON neu laden“ fragt nach, „n“ behält die Änderung; „Beenden“ bietet Speichern / Ohne Speichern / Abbrechen an, „Abbrechen“ führt zurück ins Menü; „Ohne Speichern“ beendet; nach Neustart heißt die Gruppe wieder Notebook (Test 14)

**Manuelle Tests UI-Erweiterung (selbst in Cmder durchgeführt, Gruppe Notebooks mit 36 Geräten):**

- Sortierung nach Bestand → 0 bis 8 aufsteigend, bei gleichem Bestand bleibt die ID-Reihenfolge
- Sortierung nach Standort → alphabetisch („Empfang / Infotheke“ oben, „Serverraum 01“ unten); Enter → Reihenfolge nach ID
- Gerät anlegen, Standort-Auswahl: `abc` und `99` → „Ungültige Auswahl“; `0` → neuer Standort, `5` als reine Zahl abgelehnt, „Keller“ angenommen → Gerät ID 37 angelegt
- Gerät ID 1 ändern, Punkt 3 Standort: der neue Standort „Keller“ steht sofort in der Auswahlliste, Auswahl `4` → Kontrollausgabe zeigt „Keller“
- Beenden mit „Abbrechen“ → zurück ins Menü, Hinweis „ungespeicherte Änderungen“ bleibt; leere Eingabe bei „Gruppe wählen“ → „Ungültige ID“, zurück ins Hauptmenü
- „Ohne Speichern beenden“ → JSON-Datei unverändert (Prüfsumme vor und nach dem Test gleich)

Zusätzlich: leere JSON-Datei → Fehler; Beispiel-JSON aus dem Auftrag (Seriennummern als Liste) wird geladen und angezeigt; nach dem Speichern bleibt keine `.tmp`-Datei übrig.

## Viele Daten

Gemessen mit einer Gruppe und künstlich erzeugten Geräten (PHP 8.2, Schul-PC):

| Geräte | CSV laden | JSON laden (inkl. Prüfung) | JSON-Datei | Arbeitsspeicher JSON |
|---|---|---|---|---|
| 10.000 | 1,3 s | 0,09 s | 3,2 MB | 18 MB |
| 100.000 | 152 s | 0,6 s | 32,5 MB | 177 MB |
| 300.000 | – (abgebrochen) | 1,4 s | 98 MB | 522 MB |

- Das CSV-Laden wird quadratisch langsamer, weil bei jeder Zeile die ganze bisherige Liste nach doppelten IDs durchsucht wird. Die neue gemeinsame Prüfung merkt sich gesehene IDs als Array-Schlüssel und prüft mit `isset()`. Das ist linear.
- JSON braucht mehr Platz und Speicher (Feldnamen stehen in jedem Datensatz, `json_decode` liest die ganze Datei auf einmal). Ab ca. 300.000 Geräten ist das Speicherlimit von XAMPP (512 MB) erreicht.
- Bei so vielen Daten wären Suche und seitenweise Ausgabe nötig, auf Dauer eine Datenbank.

## Bekannte Lücken

- Zusatzwerte als Liste (mehrere Seriennummern) werden geladen, angezeigt und gespeichert, können im Menü aber nur als ein Text neu eingegeben werden.
- Die CSV-Einlesefunktion `ladeGeraete()` aus der CSV-Version ist bei sehr vielen Daten weiterhin langsam (siehe oben).
- Erweiterungen (Sicherungskopie vor dem Speichern, beliebig viele Zusatzfelder, Entfernen des `datei`-Feldes, JSON-Export für JavaScript) sind nicht umgesetzt.
- Wird in der Konsole kein UTF-8 verwendet, können Umlaute in der Eingabe ungültig sein. `json_encode` meldet dann einen Fehler und speichert nicht. `start.cmd` stellt UTF-8 ein (`chcp 65001`).

## KI-Nutzung

Dieses Projekt wurde mit Hilfe von KI erstellt. Verwendetes Werkzeug: **Claude Code** in Visual Studio Code.

| Teil | Wer |
|---|---|
| Programmcode CSV-Version (`verwaltung.php`, `funktionen.php`) | Anforderungen von mir, Umsetzung mit KI, von mir nachvollzogen und getestet |
| JSON-Erweiterung (`json.php`, Umbau Hauptmenü, Anzeige von Listenwerten) | Anforderungen von mir, Umsetzung mit KI, von mir nachvollzogen und getestet |
| Beispieldaten in `daten/` | mit KI erstellt (Mockdaten per Skript erzeugt; JSON per CSV-Import erzeugt) |
| Automatisierte Abnahmetests und Messung mit vielen Daten | KI |
| Manuelle Tests in Cmder (CSV-Version: Test 7; JSON-Version: Tests 4, 11, 13, 14; UI-Erweiterung: Sortierung, Standort-Auswahl – siehe oben) | ich |
| Aufgabenstellung an die KI, Prüfung und Abgabe | ich |
| README | mit KI erstellt, von mir angepasst |
| UI-Erweiterung: Testdaten-Skript, Tabellenkopf, Suche, Bearbeiten-Menü, UX-Mängelliste, Datenmodell (DBML/SQL) | von mir erstellt (mit KI-Hilfe), von der KI geprüft und korrigiert |
| UI-Erweiterung: „Liste anzeigen?“, Prüfung gegen reine Zahlen beim Anlegen | Problem von mir gefunden (UX-Liste), Umsetzung mit KI, von mir nachvollzogen und getestet |
| UI-Erweiterung: Tabellenzeilen mit Umlaut-Ausgleich, SQL-Tests, Diagramm-PDF, `datenmodell.md` | mit KI erstellt, von mir nachvollzogen |
| Durchklicken mit Testdaten in Cmder (Grundlage der UX-Liste) | ich |
| Standort-Auswahl `eingabeStandort()` und Sortierung mit Bubblesort | von mir geschrieben (Hinweise und Grundgerüst für Bubblesort von der KI), von der KI geprüft, eingebaut und getestet |
| ER-Modell (Diagramm), Abschnitt ER-Modell in `datenmodell.md` | mit KI erstellt, von mir nachvollzogen |

Alle Kommentare im Code sind auf Deutsch, damit der Ablauf nachvollziehbar ist.
