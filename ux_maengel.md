# UX-Mängelliste – Geräteverwaltung (Konsolenanwendung)

## 1. Unübersichtliche Geräteliste – [Behoben]
- Jedes Gerät belegte in der Ausgabe 6 Zeilen untereinander.
- Bei 40 Geräten pro Gruppe scrollten die ersten Einträge sofort aus dem Fenster.
- **Lösung:** Kompakte Tabellenansicht mit ausgerichteten Spalten (`str_pad`) umgesetzt.

## 2. Fehlende Such- und Filterfunktion – [Behoben]
- Man konnte nicht nach Bezeichnung, Standort oder Seriennummer suchen.
- Um ein bestimmtes Gerät zu finden, musste man die ganze Liste durchsuchen.
- **Lösung:** Gruppenübergreifende Suchfunktion über alle Felder im Hauptmenü ergänzt.

## 3. Umständliche Auswahl beim Bearbeiten und Löschen – [Behoben]
- Vor der ID-Eingabe wurde immer automatisch die gesamte Geräteliste ausgegeben.
- Bei vielen Einträgen musste man wieder nach oben scrollen, um die ID abzulesen.
- **Lösung:** Vor der ID-Eingabe fragt das Programm „Liste anzeigen? (j/n)“. Wer die ID kennt, gibt sie direkt ein. Wird die Liste gewünscht, erscheint sie als kompakte Tabelle (1 Zeile pro Gerät).

## 4. Alle Felder müssen beim Bearbeiten durchgeklickt werden – [Behoben]
- Beim Ändern eines Geräts fragte das Programm jedes Feld einzeln ab.
- Wenn man nur den Bestand ändern wollte, musste man alle anderen Felder überspringen.
- Nach dem Ändern wurden die neuen Werte nicht noch einmal angezeigt.
- **Lösung:** Auswahlmenü für gezieltes Ändern einzelner Felder und Kontrollausgabe nach dem Ändern eingebaut.

## 5. Fehlende Eingabevalidierung und Auswahlhilfen – [Behoben]
- Bei Bezeichnung und Standort wurden auch sinnlose Eingaben wie `0` oder `1` akzeptiert.
- Wiederkehrende Standorte mussten jedes Mal neu getippt werden (Tippfehler-Gefahr: „Serverraum 1“ statt „Serverraum 01“).
- **Lösung:** Prüfung gegen reine Zahleneingaben (`is_numeric`) beim Anlegen und beim Ändern ergänzt. Neue Funktion `eingabeStandort()`: zeigt die vorhandenen Standorte der Gruppe als nummerierte Liste (sortiert, ohne Doppelte), Auswahl per Nummer oder `0` für einen neuen Standort.

## 6. Keine Sortierung und kein Rückgängig (Undo) – [Teilweise behoben]
- Die Liste war fest nach ID sortiert; eine Sortierung nach Bestand oder Standort fehlte.
- Versehentliche Änderungen lassen sich nicht über eine Undo-Funktion rückgängig machen (offen).
- **Lösung (teilweise):** Beim Auflisten der Geräte fragt das Programm „Sortieren nach: 1 ID, 2 Bestand, 3 Standort“. Sortiert wird eine Kopie mit Bubblesort (zwei `for`-Schleifen, Tausch über `$tmp`), die gespeicherten Daten bleiben unverändert. Im Änderungsmenü gibt es einen Abbruch (`0 Abbrechen`). Undo ist noch offen.
