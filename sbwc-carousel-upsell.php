<?php
/**
 * Plugin Name: SBWC Carousel Upsell
 * Plugin URI:
 * Description: Product specific upsells displayed in a carousel on the single product page. Supports Riode and Flatsome themes.
 * Version: 1.0.2
 * Author: WC Bessinger
 * Author URI:
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sbwc-carousel-upsell
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// plugin init
add_action('plugins_loaded', function() {

    // plugin file and uri constants
    define('SBWC_CU_PATH', plugin_dir_path(__FILE__));
    define('SBWC_CU_URI', plugin_dir_url(__FILE__));

    // text domain constant
    define('SBWC_CU_TDOM', 'sbwc-carousel-upsell');

    // version constant
    define('SBWC_CU_VERSION', '1.0.2');

    // back class
    require_once(SBWC_CU_PATH . 'inc/classes/sbcu_back.php');

    // front class
    require_once(SBWC_CU_PATH . 'inc/classes/sbcu_front.php');

});

?>