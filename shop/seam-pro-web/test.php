<?php

// Soubor: test.php
// Umístění: C:\tmp\seam-pro-web\test.php

echo "Zahajuji lokalni test Seam API knihovny...\n\n";

// Načtení autoloaderu, přesně jako by to udělal WordPress
require_once __DIR__ . '/vendor/autoload.php';

// Použití Seam tříd
use Seam\SeamClient;
use Seam\Exceptions\SeamException;

// --- ZDE VLOŽTE VÁŠ SKUTEČNÝ SEAM API KLÍČ ---
$apiKey = 'seam_rDe3KHPr_8ekuqQzmr92fkB7deBSiYZYN';
// -------------------------------------------

if ($apiKey === 'TVUJ_SEAM_API_KLIC' || empty($apiKey)) {
    echo "CHYBA: Prosim, vlozte vas skutecny Seam API klic do promenne \$apiKey v souboru test.php.\n";
    exit; // Ukončení skriptu
}

echo "API klic nalezen. Pokousim se inicializovat Seam klienta...\n";

try {
    // Vytvoření instance Seam klienta
    $seam = new SeamClient($apiKey);
    echo "Seam klient uspesne inicializovan.\n";

    // Provedení jednoduchého testovacího volání - získáme seznam vašich zámků
    echo "Pokousim se ziskat seznam zarizeni (zamku) ze Seam uctu...\n";
    $devices = $seam->devices->list();

    // Zobrazení výsledku
    echo "\n\n--- VYSLEDEK ---\n";
    echo "Test uspesny! Spojeni se Seam API funguje.\n\n";
    echo "Nalezena zarizeni ve vasem uctu:\n";
    
    if (count($devices) > 0) {
        foreach ($devices as $device) {
            echo " - ID Zarizeni: " . $device->device_id . "\n";
            echo "   Nazev: " . $device->properties->name . "\n";
            echo "   Typ: " . $device->device_type . "\n\n";
        }
    } else {
        echo "Nebylo nalezeno zadne zarizeni. To je v poradu, pokud jeste zadne nemate pridane v Seam uctu.\n";
    }

} catch (SeamException $e) {
    // Zachycení specifické chyby z Seam API
    echo "\n\n--- CHYBA API ---\n";
    echo "Doslo k chybě pri komunikaci se Seam API:\n";
    echo "Chybova zprava: " . $e->getMessage() . "\n";
    echo "Chybovy kod: " . $e->getCode() . "\n";

} catch (Exception $e) {
    // Zachycení jakékoliv jiné obecné chyby (např. problém s autoloaderem)
    echo "\n\n--- OBECNA CHYBA ---\n";
    echo "Doslo k neocekavane chybe:\n";
    echo $e->getMessage() . "\n";
}

?>