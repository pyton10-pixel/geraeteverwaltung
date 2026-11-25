# Datenmodell für eine relationale Datenbank

Grundlage ist die JSON-Datei `daten/geraeteverwaltung.json` mit den Testdaten (erzeugt mit `generate_testdata.php`).

Dateien in diesem Ordner:

| Datei | Inhalt |
|---|---|
| `geraeteverwaltung.sql` | Tabellen für MariaDB/MySQL (getestet mit MariaDB 10.4 aus XAMPP) |
| `geraeteverwaltung.dbml` | dasselbe Modell für dbdiagram.io |
| `erm.pdf`, `erm.png`, `erm.svg` | ER-Modell (Chen-Notation) |
| `datenmodell.pdf` | Diagramm (Relationenmodell) |

## 1. Was steht in den Daten?

| Gemessen | Anzahl |
|---|---|
| Gruppen | 10 |
| Geräte | 402 |
| Werte in Zusatzfeldern | 1206 |
| verschiedene Standorte | 6 (z. B. „Serverraum 01“ 83-mal) |
| verschiedene Bezeichnungen | 40 (z. B. „Surface Pro 9“ 15-mal) |

Jedes Gerät hat 4 feste Felder (`id`, `bezeichnung`, `bestand`, `standort`) und bis zu 3 Zusatzfelder. Die Zusatzfelder sind je Gruppe verschieden: Notebooks haben `seriennummer, ram, prozessor`, USV-Anlagen `leistung, bauform, anschluss`.

## 2. Was wiederholt sich?

1. **Standort:** Derselbe Text steht in sehr vielen Geräten. Ein Tippfehler („Serverraum 1“ statt „Serverraum 01“) würde einen neuen Standort erzeugen. → eigene Tabelle `standorte`.
2. **Gruppe:** Im JSON stecken die Geräte in ihrer Gruppe. In einer Tabelle braucht jedes Gerät einen Verweis auf seine Gruppe. → Tabelle `gruppen`, im Gerät `gruppe_id`.
3. **Zusatzfelder:** Die Feldnamen stehen im JSON bei jeder Gruppe und als Schlüssel in jedem Gerät. Eine Tabelle mit Spalten `ram`, `zoll`, `poe`, … für alle Gruppen hätte sehr viele leere Zellen, und für jedes neue Feld müsste man die Tabelle ändern. → Feldnamen in `gruppen_felder`, Werte in `geraete_werte`.

## 3. ER-Modell

Entitäten: **Standort**, **Gerät**, **Gruppe**, **Zusatzfeld**.

| Beziehung | Kardinalität | Bedeutung |
|---|---|---|
| Gerät *steht in* Standort | n : 1 | ein Standort hat viele Geräte |
| Gerät *gehört zu* Gruppe | n : 1 | jedes Gerät gehört zu genau einer Gruppe |
| Gruppe *definiert* Zusatzfeld | 1 : n | eine Gruppe hat bis zu 3 Zusatzfelder |
| Gerät *hat Wert* Zusatzfeld | m : n | ein Gerät hat Werte für mehrere Felder, ein Feld hat Werte bei vielen Geräten. Attribut: **Wert** |

Vom ER-Modell zu den Tabellen:

- Jede Entität wird eine Tabelle (`standorte`, `geraete`, `gruppen`, `gruppen_felder`).
- Bei 1:n kommt der Fremdschlüssel auf die n-Seite (z. B. `standort_id` in `geraete`).
- Die m:n-Beziehung *hat Wert* wird eine eigene Tabelle `geraete_werte` mit beiden Fremdschlüsseln und dem Attribut `wert`.

## 4. Tabellen

| Tabelle | Bedeutung | Schlüssel |
|---|---|---|
| `gruppen` | Gerätegruppe | PK `gruppe_id`, `name` eindeutig |
| `standorte` | Raum / Ort | PK `standort_id`, `bezeichnung` eindeutig |
| `geraete` | ein Gerätedatensatz mit festen Feldern | PK `geraet_id`, FK `gruppe_id`, FK `standort_id` |
| `gruppen_felder` | Zusatzfelder einer Gruppe mit Reihenfolge | PK `feld_id`, FK `gruppe_id`, eindeutig: (Gruppe, Feldname) und (Gruppe, Sortierung) |
| `geraete_werte` | Wert eines Zusatzfelds für ein Gerät | PK `wert_id`, FK `geraet_id`, FK `feld_id`, eindeutig: (Gerät, Feld) |

Beziehungen (alle 1:n):

- eine Gruppe hat viele Geräte, ein Gerät gehört zu genau einer Gruppe
- ein Standort hat viele Geräte
- eine Gruppe hat bis zu 3 Felder
- ein Gerät hat je Feld höchstens einen Wert

## 5. Regeln in der Datenbank

Die Regeln aus dem Programm stehen auch in der Datenbank:

| Regel im Programm | In der Datenbank |
|---|---|
| Bestand ist eine ganze Zahl ≥ 0 | `CHECK (bestand >= 0)` |
| Gruppenname eindeutig | `UNIQUE` |
| Gruppe mit Geräten kann nicht gelöscht werden | `ON DELETE RESTRICT` |
| Zusatzfelder nur bei leerer Gruppe änderbar | `ON DELETE RESTRICT` von `geraete_werte` auf `gruppen_felder` |
| Gerät löschen | `ON DELETE CASCADE`: seine Werte werden mitgelöscht |
| leerer Zusatzwert | es gibt einfach keine Zeile in `geraete_werte` |

Getestet: doppeltes Feld, doppelte Sortierung, Bestand −5, zwei Werte für dasselbe Feld, Löschen einer Gruppe mit Geräten und Löschen eines benutzten Standorts werden abgelehnt.

## 6. Entscheidungen

- **Keine Tabelle `modelle`:** Die Bezeichnung wiederholt sich zwar (40 verschiedene bei 402 Geräten). In den Daten sind die technischen Werte (RAM, Prozessor) aber pro Gerät verschieden und hängen nicht vom Modell ab. Den Hersteller gibt es nicht als eigenes Feld. Eine Tabelle `modelle (modell_id, gruppe_id, hersteller, modellname)` wäre eine mögliche Erweiterung.
- **Seriennummern:** Im JSON darf eine Seriennummer auch eine Liste sein. In den Testdaten kommt das nicht vor. In der Datenbank wird eine Liste als ein Text mit Komma gespeichert (so zeigt das Programm sie auch an). Sauberer wäre eine eigene Tabelle `seriennummern (geraet_id, seriennummer)`.
- **Feld `datei`:** stammt aus der CSV-Version. Es bleibt drin, damit die Daten aus der CSV-Version zugeordnet werden können. In einer reinen Datenbankversion kann es entfallen.

## 7. Bekannte Lücke

Ein Wert in `geraete_werte` könnte auf ein Feld einer **anderen** Gruppe zeigen (z. B. `zoll` bei einem Notebook). Mit einfachen Fremdschlüsseln lässt sich das nicht verbieten. Diese Prüfung übernimmt das Programm.
