<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */

require_once get_stylesheet_directory() . '/vendor/autoload.php';

// Použití potřebných tříd ze Seam knihovny
use Seam\SeamClient;
use Seam\Exceptions\SeamException; // Pro lepší odchytávání chyb ze Seam API

// --- ZAČÁTEK KONFIGURACE ---
if (!defined('SEAM_API_KEY')) {
    define('SEAM_API_KEY', 'seam_rDe3KHPr_8ekuqQzmr92fkB7deBSiYZYN'); // <<< SEM VLOŽTE VÁŠ SKUTEČNÝ SEAM API KLÍČ
}
if (!defined('IGLOOHOME_DEVICE_ID')) {
    define('IGLOOHOME_DEVICE_ID', 'c08527c6-9a7c-4a5c-82be-58e85ea730d0'); // <<< ID VAŠEHO ZÁMKU (už máme ověřené)
}
if (!defined('PRODUKT_ID_VSTUPENKY')) {
    define('PRODUKT_ID_VSTUPENKY', 61); // <<< ID PRODUKTU VE WOOCOMMERCE, KTERÝ PŘEDSTAVUJE VSTUPENKU
}
if (!defined('MAX_DELKA_NAZVU_KODU')) {
    define('MAX_DELKA_NAZVU_KODU', 48); // Ověřená maximální délka názvu kódu
}
// --- KONEC KONFIGURACE ---


/**
 * Hlavní funkce pro generování Seam (Igloohome) kódů po dokončení platby.
 * Napojuje se na hooky WooCommerce.
 */
add_action('woocommerce_payment_complete', 'generuj_seam_kody_po_platbe', 10, 1);
//add_action('woocommerce_order_status_processing', 'generuj_seam_kody_po_platbe', 10, 1); 

function generuj_seam_kody_po_platbe($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || $order->get_meta('_seam_kody_vygenerovano_flag')) {
        if ($order && $order->get_meta('_seam_kody_vygenerovano_flag')) {
            $order->add_order_note('Seam API: Pokus o opětovné generování kódů byl zablokován.');
        }
        return;
    }

    if (empty(SEAM_API_KEY) || SEAM_API_KEY === 'TVUJ_SEAM_API_KLIC' || empty(IGLOOHOME_DEVICE_ID)) {
        $order->add_order_note('CHYBA Seam API: API klíč nebo Device ID není nakonfigurováno. Kódy nelze vygenerovat.');
        error_log('Seam API integrace: Chybí API klíč nebo Device ID.');
        return;
    }

    $celkovy_pocet_vstupenek_k_vygenerovani = 0;
    foreach ($order->get_items() as $item_id => $item) {
        $product_id_polozky = $item->get_product_id(); // ID rodičovského produktu pro varianty
        if ($product_id_polozky == PRODUKT_ID_VSTUPENKY) {
            $celkovy_pocet_vstupenek_k_vygenerovani += $item->get_quantity();
        }
    }

    if ($celkovy_pocet_vstupenek_k_vygenerovani == 0) {
        $order->add_order_note('Seam API: V objednávce nebyly nalezeny žádné vstupenky k vygenerování kódů (ID produktu vstupenky: ' . PRODUKT_ID_VSTUPENKY . ').');
        return;
    }

    $vygenerovane_kody_info = []; 
    $global_code_index = 0; 

    $zakaznik_email = $order->get_billing_email();
    $zakaznik_jmeno = $order->get_billing_first_name();
    $zakaznik_prijmeni = $order->get_billing_last_name();

    try {
        $seam = new SeamClient(SEAM_API_KEY);
        $timezone = new DateTimeZone('Europe/Prague');

        foreach ($order->get_items() as $item_id => $item) {
            $product_id_polozky = $item->get_product_id();
            if ($product_id_polozky == PRODUKT_ID_VSTUPENKY) {
                $quantity = $item->get_quantity();
                for ($i = 0; $i < $quantity; $i++) {
                    $global_code_index++; // Ujistěte se, že $global_code_index je správně inkrementován


                    $starts_at_obj = new DateTime('now', $timezone);
                    $starts_at_obj->setTime($starts_at_obj->format('H'), $starts_at_obj->format('i'), 0); 
                    $ends_at_obj = (clone $starts_at_obj)->modify('+30 days');

                    $email_pro_nazev = $zakaznik_email;
                    $jmeno_pro_nazev = pripravTextProNazev($zakaznik_jmeno);
                    $prijmeni_pro_nazev = pripravTextProNazev($zakaznik_prijmeni);
                    
                    $zaklad_nazvu = "O{$order_id}-V{$global_code_index}-{$email_pro_nazev}_{$jmeno_pro_nazev}_{$prijmeni_pro_nazev}";
                    $finalni_nazev_kodu = mb_substr($zaklad_nazvu, 0, MAX_DELKA_NAZVU_KODU);

                    $params_pro_kod = [
                        'device_id' => IGLOOHOME_DEVICE_ID,
                        'name' => $finalni_nazev_kodu,
                        'starts_at' => $starts_at_obj->format(DateTime::ATOM),
                        'ends_at' => $ends_at_obj->format(DateTime::ATOM),
                        'is_offline_access_code' => false, 
                    ];

                    $access_code_response = $seam->access_codes->create(
                        device_id: $params_pro_kod['device_id'],
                        name: $params_pro_kod['name'],
                        starts_at: $params_pro_kod['starts_at'],
                        ends_at: $params_pro_kod['ends_at'],
                        is_offline_access_code: $params_pro_kod['is_offline_access_code']
                    );

                    $generated_pin = $access_code_response->code ?? null;

                    if ($generated_pin) {
                        $vygenerovane_kody_info[] = [
                            'pin' => $generated_pin,
                            'name' => $finalni_nazev_kodu, 
                            'api_name' => $access_code_response->name, 
                            'id_v_seam' => $access_code_response->access_code_id,
                            'valid_from' => $starts_at_obj->format('Y-m-d H:i:s'),
                            'valid_until' => $ends_at_obj->format('Y-m-d H:i:s'),
                        ];
                        $order->add_order_note('Seam API: PIN ' . $global_code_index . ' úspěšně vygenerován: ' . esc_html($generated_pin) . ' (Název: ' . esc_html($finalni_nazev_kodu) . ')');
                    } else {
                        $order->add_order_note('CHYBA Seam API: PIN ' . $global_code_index . ' se nepodařilo získat z odpovědi API pro název: ' . esc_html($finalni_nazev_kodu));
                    }
                } 
            } 
        } 

        if (!empty($vygenerovane_kody_info)) {
            $order->update_meta_data('_seam_access_codes_info', $vygenerovane_kody_info);
            $order->update_meta_data('_seam_kody_vygenerovano_flag', true);
            $order->add_order_note('Seam API: Celkem vygenerováno ' . count($vygenerovane_kody_info) . ' kódů.');
            $order->update_status('completed', 'PIN kódy byly úspěšně vygenerovány a objednávka automaticky dokončena.');
            
            if (function_exists('WC') && WC()->session && !WC()->session->has_session()) {
                WC()->session->set_customer_session_cookie(true);
            }
            if (function_exists('WC') && WC()->session) {
                 WC()->session->set('seam_kody_pro_zobrazeni_' . $order_id, $vygenerovane_kody_info);
            }

        } else {
            $order->add_order_note('Seam API: Nebyly vygenerovány žádné kódy (nebo došlo k chybě u všech).');
        }
        $order->save(); 

    } catch (SeamException $e) {
        $order->add_order_note('CHYBA Seam API (Výjimka): ' . $e->getMessage() . ' (Kód: ' . $e->getCode() . ')');
        error_log('Seam API Exception pro objednávku ' . $order_id . ': ' . $e->getMessage());
    } catch (Exception $e) {
        $order->add_order_note('OBECNÁ CHYBA při generování Seam kódů: ' . $e->getMessage());
        error_log('Obecná Exception při generování Seam kódů pro objednávku ' . $order_id . ': ' . $e->getMessage());
    }
}

/**
 * Pomocná funkce pro sanitizaci textu pro název kódu.
 */
function pripravTextProNazev($text) {
    if (empty($text)) return '';
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^A-Za-z0-9@._-]/', '_', (string)$text);
    $text = preg_replace('/_+/', '_', $text);
    return $text;
}


/**
 * Zobrazení vygenerovaných kódů v administraci objednávky.
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'zobraz_seam_kody_v_admin_objednavky', 10, 1);
function zobraz_seam_kody_v_admin_objednavky($order){
    $kody_info = $order->get_meta('_seam_access_codes_info');
    if (!empty($kody_info) && is_array($kody_info)) {
        echo '<div class="order_data_column" style="margin-top: 20px; width: 100%;">';
        echo '<h4><span class="dashicons dashicons-lock" style="margin-right: 5px;"></span> Vygenerované Seam Igloohome PINy</h4>';
        echo '<table style="width:100%; text-align:left;">';
        echo '<thead><tr><th>PIN</th><th>Název (odeslaný)</th><th>Platnost od</th><th>Platnost do</th></tr></thead>';
        echo '<tbody>';
        foreach ($kody_info as $info) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($info['pin'] ?? 'N/A') . '</strong></td>';
            echo '<td>' . esc_html($info['name'] ?? 'N/A') . '</td>';
            echo '<td>' . esc_html($info['valid_from'] ?? 'N/A') . '</td>';
            echo '<td>' . esc_html($info['valid_until'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
}


/**
 * Přidání kódů a instrukcí do e-mailu zákazníkovi. (FINÁLNÍ OPRAVA)
 */
add_action('woocommerce_email_after_order_table', 'pridej_seam_kody_do_emailu_zakaznikovi', 15, 4);
function pridej_seam_kody_do_emailu_zakaznikovi($order, $sent_to_admin, $plain_text, $email) {
    if ($sent_to_admin || !is_a($order, 'WC_Order') || !in_array($email->id, ['customer_completed_order', 'customer_processing_order'])) {
        return;
    }

    $kody_info = $order->get_meta('_seam_access_codes_info');

    if (!empty($kody_info) && is_array($kody_info)) {
        $date_obj_for_formatting = $order->get_date_completed();
        if (!$date_obj_for_formatting && method_exists($order, 'get_date_paid')) { // get_date_paid může někdy chybět
            $date_obj_for_formatting = $order->get_date_paid();
        }
        if (!$date_obj_for_formatting) {
            $date_obj_for_formatting = $order->get_date_created();
        }
        
        $timestamp_for_formatting = time(); // Výchozí hodnota pro případ, že datum nelze získat
        if ($date_obj_for_formatting && is_callable([$date_obj_for_formatting, 'get_timestamp'])) { // Správný název metody s malými písmeny
            $timestamp_for_formatting = $date_obj_for_formatting->get_timestamp();
        } elseif ($date_obj_for_formatting && is_a($date_obj_for_formatting, 'DateTime')) { // Pro případ, že by to byl standardní DateTime objekt
             $timestamp_for_formatting = $date_obj_for_formatting->getTimestamp();
        }

        $start_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp_for_formatting);

        if ($plain_text) {
            echo "\n\n----------------------------------------\n";
            echo mb_strtoupper(__('Vaše vstupní kódy do výstavních prostor', 'woocommerce')) . "\n";
            echo __('Děkujeme za nákup. Níže naleznete vaše unikátní vstupní kódy. Každý kód platí pro jednu osobu a je platný 30 dní od okamžiku zakoupení (přibližně od ', 'woocommerce') . $start_date_formatted . ").\n\n";
            foreach ($kody_info as $index => $info) {
                echo ($index + 1) . ". Vstupní kód: " . esc_html($info['pin'] ?? 'N/A') . "\n";
            }
            echo "\n" . __('Kódy jsou platné dle uvedených časů.', 'woocommerce') . "\n";
            echo "----------------------------------------\n";
        } else {
            ?>
            <div style="margin-top: 20px; padding: 15px; border: 1px solid #e0e0e0; background-color: #f9f9f9;">
                <h2 style="font-family: Arial, sans-serif; color: #333; margin-top:0;">Vaše vstupní kódy do výstavních prostor</h2>
                <p style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6;">Děkujeme za váš nákup. Níže naleznete vaše unikátní vstupní kódy. Každý kód platí pro jednu osobu a je platný 30 dní od okamžiku zakoupení (přibližně od <?php echo esc_html($start_date_formatted); ?>).</p>
                <ul style="list-style-type: none; padding-left: 0; margin-bottom: 16px;">
                <?php foreach ($kody_info as $index => $info) : ?>
                    <li style="margin-bottom: 8px; padding: 10px; border: 1px solid #e0e0e0; background-color: #fff; border-radius: 4px; font-size: 14px;">
                        <strong style="font-size: 18px; font-family: monospace; letter-spacing: 2px; color: #000;"><?php echo esc_html($info['pin'] ?? 'N/A'); ?></strong>
                        (<?php echo ($index + 1); ?>. vstupenka)
                    </li>
                <?php endforeach; ?>
                </ul>
                <p style="font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #777; border-left: 3px solid #1abc9c; padding-left: 10px; margin-top: 15px;">
                    <strong>DŮLEŽITÉ UPOZORNĚNÍ:</strong> Tyto kódy jsou platné po dobu 30 dní od uvedeného data a času začátku platnosti.
                </p>
            </div>
            <?php
        }
    }
}

/**
 * Zobrazení kódů na děkovné stránce. (FINÁLNÍ OPRAVA)
 */
add_action('woocommerce_thankyou', 'zobraz_seam_kody_na_thankyou_strance', 10, 1);
function zobraz_seam_kody_na_thankyou_strance($order_id) {
    if (!function_exists('WC')) {
        return;
    }
    
    $order = wc_get_order($order_id); 
    if (!$order) return;

    $kody_info_k_zobrazeni = $order->get_meta('_seam_access_codes_info');

    if (!empty($kody_info_k_zobrazeni) && is_array($kody_info_k_zobrazeni)) {
        $date_obj_for_formatting = $order->get_date_completed();
        if (!$date_obj_for_formatting && method_exists($order, 'get_date_paid')) { // get_date_paid může někdy chybět
            $date_obj_for_formatting = $order->get_date_paid();
        }
        if (!$date_obj_for_formatting) {
            $date_obj_for_formatting = $order->get_date_created();
        }

        $timestamp_for_formatting = time(); // Výchozí hodnota
        if ($date_obj_for_formatting && is_callable([$date_obj_for_formatting, 'get_timestamp'])) { // Správný název metody s malými písmeny
            $timestamp_for_formatting = $date_obj_for_formatting->get_timestamp();
        } elseif ($date_obj_for_formatting && is_a($date_obj_for_formatting, 'DateTime')) { // Pro případ, že by to byl standardní DateTime objekt
             $timestamp_for_formatting = $date_obj_for_formatting->getTimestamp();
        }

        $start_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp_for_formatting);

        echo '<div class="woocommerce-order-details" style="margin-top: 2em; margin-bottom: 2em; padding: 1.5em; border: 1px solid #e0e0e0; background-color: #fdfdfd; border-radius: 5px;">';
        echo '<h2 class="woocommerce-order-details__title" style="font-size: 1.5em; margin-bottom: 0.8em;">Vaše vstupní kódy</h2>';
        echo '<p style="font-size: 1.1em; margin-bottom: 1em;">Děkujeme za váš nákup! Níže naleznete vaše unikátní vstupní kódy. Byly vám také zaslány e-mailem. Každý kód platí pro jednu osobu a je platný 30 dní od okamžiku zakoupení (přibližně od ' . esc_html($start_date_formatted) . ').</p>';
        echo '<ul style="list-style-type: none; padding-left: 0; margin-bottom: 1em;">';
        foreach ($kody_info_k_zobrazeni as $index => $info) {
            echo '<li style="font-size: 1.8em; font-weight: bold; text-align: center; letter-spacing: 2px; padding: 15px; margin-bottom: 10px; background-color: #f0f8ff; border: 1px dashed #b0c4de; border-radius: 4px;">' . esc_html($info['pin'] ?? 'N/A') . '</li>';
        }
        echo '</ul>';
        echo '<p style="font-size: 0.9em; color: #555; margin-top:1.5em;"><strong>DŮLEŽITÉ UPOZORNĚNÍ:</strong> Tyto kódy jsou platné po dobu 30 dní od uvedeného data a času začátku platnosti.</p>';
        echo '</div>';
    }
}


// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );

// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( get_parent_theme_file_uri( 'assets/css/editor-style.css' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );

// Enqueues style.css on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues style.css on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( 'style.css' ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );

// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );

// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {

		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);

		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );

// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( ! function_exists( 'twentytwentyfive_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function twentytwentyfive_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

