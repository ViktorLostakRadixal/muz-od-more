<?php

// Soubor: test_vytvoreni_kodu.php
// Účel: Otestuje vytvoření nového 30denního přístupového kódu.

echo "Zahajuji test vytvoreni noveho klice...\n\n";

require_once __DIR__ . '/vendor/autoload.php';

use Seam\SeamClient;
use Seam\Exceptions\SeamException;

// --- SIMULOVANÁ DATA PRO TEST ---
$testOrderId = rand(10000, 99999); // Náhodné číslo objednávky pro test
$testZakaznikEmail = "jan.novak.opravdu.velmi.dlouhy.email.s.mnoha.znaky@example-extra-long-domain-name-for-testing.com";
$testZakaznikJmeno = "Jan Maria Kristián";
$testZakaznikPrijmeni = "Dlouhatánský-Přespřílišjmenný";
$testIndexVstupenky = 1;
$maxCelkovaDelkaNazvu = 48; // Navrhovaná maximální délka celého názvu
// ------------------------------

// --- KONFIGURACE ---
// Vložte sem vaše údaje
$apiKey = 'seam_rDe3KHPr_8ekuqQzmr92fkB7deBSiYZYN';
$deviceId = 'c08527c6-9a7c-4a5c-82be-58e85ea730d0'; // ID vašeho zámku z předchozího testu
// --------------------

if ($apiKey === 'TVUJ_SEAM_API_KLIC' || empty($apiKey)) {
    echo "CHYBA: Vlozte vas skutecny Seam API klic.\n";
    exit;
}

// Funkce pro jednoduchou sanitizaci (odstranění diakritiky a nahrazení mezer podtržítkem)
function pripravTextProNazev($text) {
    // Převod na ASCII pro odstranění diakritiky
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    // Nahrazení čehokoli, co není písmeno, číslo, zavináč, tečka, pomlčka, podtržítko za podtržítko
    $text = preg_replace('/[^A-Za-z0-9@._-]/', '_', $text);
    // Nahrazení vícenásobných podtržítek jedním
    $text = preg_replace('/_+/', '_', $text);
    return $text;
}

// Příprava částí názvu
$emailSanitizovany = $testZakaznikEmail; // Email necháváme v původní podobě s @ a .
$jmenoSanitizovane = pripravTextProNazev($testZakaznikJmeno);
$prijmeniSanitizovane = pripravTextProNazev($testZakaznikPrijmeni);

// Sestavení základního názvu
$zakladniNazev = "O{$testOrderId}-V{$testIndexVstupenky}-{$emailSanitizovany}_{$jmenoSanitizovane}_{$prijmeniSanitizovane}";

// Oříznutí na maximální délku, pokud je potřeba
$finalniNazevKodu = $zakladniNazev;
if (mb_strlen($finalniNazevKodu) > $maxCelkovaDelkaNazvu) {
    $finalniNazevKodu = mb_substr($finalniNazevKodu, 0, $maxCelkovaDelkaNazvu);
}

echo "Pokousim se vytvorit novy 30denni klic pro zamek ID: " . $deviceId . "\n";

try {
    $seam = new SeamClient($apiKey);

    // Nastavení časové zóny a platnosti
    $timezone = new DateTimeZone('Europe/Prague');
    $starts_at = new DateTime('now', $timezone);
    $ends_at = (clone $starts_at)->modify('+30 days');

    echo "Platnost kodu bude od: " . $starts_at->format('Y-m-d H:i:s') . "\n";
    echo "Platnost kodu bude do: " . $ends_at->format('Y-m-d H:i:s') . "\n\n";
    
    $params_array = [
        'device_id' => $deviceId,
        'name' => $finalniNazevKodu,
        'starts_at' => $starts_at->format(DateTime::ATOM),
        'ends_at' => $ends_at->format(DateTime::ATOM),
        'is_offline_access_code' => false, // Používáme ONLINE kódy
    ];

    $access_code = $seam->access_codes->create(
        device_id: $params_array['device_id'],
        name: $params_array['name'],
        starts_at: $params_array['starts_at'],
        ends_at: $params_array['ends_at'],
        is_offline_access_code: $params_array['is_offline_access_code']
    );

    echo "\n--- VYSLEDEK ---\n";
    echo "TEST USPĚŠNÝ! Novy klic byl uspesne vygenerovan.\n\n";
    echo "Vygenerovany PIN: " . ($access_code->code ?? 'CHYBA: Kod nebyl v odpovedi nalezen') . "\n";
    echo "Nazev klice: " . $access_code->name . "\n";
    echo "ID klice v Seam: " . $access_code->access_code_id . "\n";
    echo "Stav: " . $access_code->status . "\n";

    echo "\nNyni muzete tento PIN vyzkouset na vasem zamku. Nezapomente na 24hodinove aktivacni okno od casu vystaveni!\n";

} catch (SeamException $e) {
    echo "\n\n--- CHYBA API ---\n";
    echo "Doslo k chybě pri komunikaci se Seam API:\n";
    echo "Chybova zprava: " . $e->getMessage() . "\n";
    echo "Chybovy kod: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n\n--- OBECNA CHYBA ---\n";
    echo "Doslo k neocekavane chybe:\n";
    echo $e->getMessage() . "\n";
}

?>