<?php
require __DIR__ . "/funktionen.php";
require __DIR__ . "/json.php";

$vorlagen = [
    ["Notebooks", "notebooks.csv", ["seriennummer", "ram", "prozessor"], ["ThinkPad T14 Gen 4", "MacBook Pro 16 M3", "Dell Latitude 5540", "HP EliteBook 840 G10"], [], ["16 GB", "32 GB", "64 GB"], ["Intel i7", "AMD Ryzen 7", "Apple M3"]],
    ["Monitore", "monitore.csv", ["aufloesung", "diagonale", "panel"], ["Dell UltraSharp U2723QE", "LG UltraGear 27GP850", "Samsung Odyssey G9 49\"", "EIZO FlexScan EV2480"], ["3840x2160", "2560x1440", "1920x1080"], ["24 Zoll", "27 Zoll", "49 Zoll"], ["IPS", "OLED", "VA"]],
    ["Beamer", "beamer.csv", ["aufloesung", "anschluss", "lichtleistung"], ["Epson EB-L530U", "BenQ TK700STi", "Sony VPL-PHZ51", "Optoma UHD35x"], ["1920x1200", "3840x2160", "1920x1080"], ["HDMI", "HDBaseT", "USB-C"], ["5200 lm", "3000 lm", "3600 lm"]],
    ["Netzwerk-Switches", "switches.csv", ["ports", "poe", "speed"], ["Cisco Catalyst 9200L", "UniFi Pro Max 48", "Aruba Instant On 1930", "Netgear GS308E"], ["8 Ports", "24 Ports", "48 Ports"], ["Ja (370W)", "Ja (740W)", "Nein"], ["1 Gbit/s", "2.5 Gbit/s", "10 Gbit/s"]],
    ["Diensthandys", "smartphones.csv", ["imei", "speicher", "os"], ["iPhone 15 Pro", "Samsung Galaxy S24", "Google Pixel 8a", "Fairphone 5"], [], ["128 GB", "256 GB", "512 GB"], ["iOS 17", "Android 14"]],
    ["Tablets", "tablets.csv", ["diagonale", "stift", "mobilfunk"], ["iPad Air 11 M2", "Surface Pro 9", "Galaxy Tab S9", "Wacom Cintiq 16"], ["11 Zoll", "13 Zoll", "15.6 Zoll"], ["Inklusive", "Optional", "Nein"], ["Wi-Fi", "5G + Wi-Fi"]],
    ["Drucker", "drucker.csv", ["typ", "farbe", "duplex"], ["Brother MFC-L8690CDW", "HP LaserJet M404dn", "Kyocera ECOSYS P5026", "Epson EcoTank ET-4850"], ["Laser", "Tinte"], ["Farbe", "Monochrom"], ["Ja", "Nein"]],
    ["Dockingstationen", "docks.csv", ["host", "leistung", "video"], ["ThinkPad USB-C Dock", "Dell WD19TBS", "CalDigit TS4", "HP USB-C Dock G5"], ["USB-C", "Thunderbolt 4"], ["65 W", "90 W", "130 W"], ["2x DP + HDMI", "2x HDMI"]],
    ["Audiotechnik", "audio.csv", ["typ", "verbindung", "akku"], ["Jabra Speak2 75", "Logitech Rally Bar", "Sennheiser Ceiling 2", "Rode Wireless PRO"], ["Freisprecher", "Videobar", "Mikrofon"], ["Bluetooth/USB", "PoE", "Funk"], ["32 Std.", "Netzbetrieb", "7 Std."]],
    ["USV-Anlagen", "usv.csv", ["leistung", "bauform", "anschluss"], ["APC Smart-UPS 1500VA", "Eaton 5PX 2200i", "CyberPower CP1500", "APC Back-UPS 700G"], ["700 VA", "1500 VA", "2200 VA"], ["Tower", "19-Zoll Rack"], ["IEC Kaltgeräte", "Schuko"]]
];
$standorte = ["Hauptgebäude Raum 101", "Hauptgebäude Raum 204 (IT)", "Nebengebäude Nord Seminar A", "Nebengebäude Süd Keller", "Serverraum 01", "Empfang / Infotheke"];

$gruppen = [];
$sn = 10001;
foreach ($vorlagen as $gIdx => $v) {
    $geraete = [];
    $anzahl = random_int(35, 45);
    for ($i = 1; $i <= $anzahl; $i++) {
        $g = [
            "id" => $i,
            "bezeichnung" => $v[3][array_rand($v[3])],   // zufälliger Name aus der Liste an Position 3
            "bestand" => random_int(0, 8),
            "standort" => $standorte[array_rand($standorte)]
        ];
        // Nur die Zusatzfelder der Gruppe ($v[2]), nicht den ganzen Eintrag
        foreach ($v[2] as $fIdx => $feld) {
            if ($feld === "seriennummer" || $feld === "imei") {
                $g[$feld] = str_pad((string)$sn, 8, "0", STR_PAD_LEFT);
                $sn++;
            } else {
                $pool = $v[4 + $fIdx];   // Wertliste dieses Feldes (ab Position 4)
                $g[$feld] = $pool[array_rand($pool)];
            }
        }
        $geraete[] = $g;
    }
    $gruppen[] = [
        "id" => $gIdx + 1,
        "name" => $v[0],
        "datei" => $v[1],
        "felder" => $v[2],
        "geraete" => $geraete
    ];
}

$ziel = __DIR__ . "/daten/geraeteverwaltung.json";
if (speichereJson($ziel, $gruppen)) {
    echo "Erfolg: " . count($gruppen) . " Gruppen mit Testdaten in $ziel gespeichert.\n";
} else {
    echo "FEHLER: Die Testdaten wurden NICHT gespeichert.\n";
}
