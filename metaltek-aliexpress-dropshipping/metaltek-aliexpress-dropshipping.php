<?php
/**
 * Plugin Name: Metaltek AliExpress Dropshipping for WooCommerce
 * Description: Connect WooCommerce with AliExpress for dropshipping (import, sync, fulfillment).
 * Version: 1.0.0
 * Author: Metaltek
 * Text Domain: metaltek-aliexpress-dropshipping
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MAD_VERSION', '1.0.0' );
define( 'MAD_PLUGIN_FILE', __FILE__ );
define( 'MAD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
require_once MAD_PLUGIN_DIR . 'includes/Autoloader.php';

MAD\Autoloader::register();

function mad_bootstrap(): void {
    MAD\Plugin::instance();
}

add_action( 'plugins_loaded', 'mad_bootstrap' );

register_activation_hook( __FILE__, array( 'MAD\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MAD\\Deactivator', 'deactivate' ) );
