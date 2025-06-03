<?php

// Soubor: test_smazani_kodu.php
// Účel: Otestuje smazání existujícího přístupového kódu.

echo "Zahajuji test smazani klice...\n\n";

require_once __DIR__ . '/vendor/autoload.php';

use Seam\SeamClient;
use Seam\Exceptions\SeamException;

// --- KONFIGURACE ---
$apiKey = 'seam_rDe3KHPr_8ekuqQzmr92fkB7deBSiYZYN';
$accessCodeIdToDelete = 'b6583623-3cd0-4ed5-9755-cac5d57e1f13'; // ID kódu, který chceme smazat
// --------------------

if ($apiKey === 'TVUJ_SEAM_API_KLIC' || empty($apiKey)) {
    echo "CHYBA: Vlozte vas skutecny Seam API klic.\n";
    exit;
}
if (empty($accessCodeIdToDelete)) {
    echo "CHYBA: Vlozte ID pristoupoveho kodu, ktery chcete smazat.\n";
    exit;
}

echo "Pokousim se smazat klic s ID: " . $accessCodeIdToDelete . "\n";

try {
    $seam = new SeamClient($apiKey);

    // Zavolání API pro smazání kódu
    // Předpokládáme, že metoda delete bere jako parametr access_code_id
    // Některé API mohou vyžadovat i device_id, ale pro Seam by mělo stačit access_code_id
    $seam->access_codes->delete(access_code_id: $accessCodeIdToDelete);

    echo "\n--- VYSLEDEK ---\n";
    echo "TEST USPĚŠNÝ! Pozadavek na smazani klice s ID " . $accessCodeIdToDelete . " byl odeslan.\n";
    echo "Overte prosim stav v Seam/Igloohome aplikaci.\n";
    echo "Poznamka: Smazani offline kodu nemusi byt vzdy okamzite reflektovano na zamku bez synchronizace.\n";
    echo "U online kodu by smazani melo byt rychlejsi.\n";


} catch (SeamException $e) {
    echo "\n\n--- CHYBA API ---\n";
    echo "Doslo k chybě pri komunikaci se Seam API:\n";
    echo "Chybova zprava: " . $e->getMessage() . "\n";
    echo "Chybovy kod: " . $e->getCode() . "\n";
    if (str_contains($e->getMessage(), "Cannot delete access code that is not on a connected device")) {
        echo "INFO: Toto muze znamenat, ze kod je jiz neaktivni nebo byl smazan, nebo zarizeni neni momentalne pripojeno.\n";
    }
} catch (Exception $e) {
    echo "\n\n--- OBECNA CHYBA ---\n";
    echo "Doslo k neocekavane chybe:\n";
    echo $e->getMessage() . "\n";
}

?>