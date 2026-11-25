<?php

$geraete = [
    ["id" => 1, "bezeichnung" => "ThinkPad T14", "bestand" => 1],
    ["id" => 2, "bezeichnung" => "Schulungsnotebook", "bestand" => 4],
    ["id" => 12, "bezeichnung" => "MacBook Pro 16 M3", "bestand" => 0],
];

// 1. Tabellenkopf
echo str_pad("ID", 4) . str_pad("Bezeichnung", 22) . "Bestand\n";

// 2. Trennlinie (4 + 22 + 7 = 33 Zeichen)
echo str_repeat("-", 33) . "\n";

// 3. Schleife über alle Geräte
foreach ($geraete as $g) {
    echo str_pad($g["id"], 4) . str_pad($g["bezeichnung"], 22) . $g["bestand"] . "\n";
}
