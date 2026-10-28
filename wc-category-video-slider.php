<?php
/**
 * Plugin Name:          WC Category Video Slider
 * Plugin URI:           https://raykapack.com/
 * Description:          Adds a video gallery (uploaded files or external links: YouTube, Aparat, Vimeo, direct video files) with custom covers to every WooCommerce product category, displayed as a fully customizable slider right above the category description (Elementor Archive Description widget, WoodMart and any WooCommerce theme).
 * Version:              1.0.0
 * Requires at least:    6.8
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Raykapack
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          wc-category-video-slider
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.1
 * Elementor tested up to:     4.2
 * Elementor Pro tested up to: 4.2
 *
 * @package WC_Category_Video_Slider
 */

defined( 'ABSPATH' ) || exit;

define( 'RCVS_VERSION', '1.0.0' );
define( 'RCVS_FILE', __FILE__ );
define( 'RCVS_PATH', plugin_dir_path( __FILE__ ) );
define( 'RCVS_URL', plugin_dir_url( __FILE__ ) );
define( 'RCVS_TEXT_DOMAIN', 'wc-category-video-slider' );

require_once RCVS_PATH . 'includes/class-settings.php';
require_once RCVS_PATH . 'includes/class-video-helper.php';
require_once RCVS_PATH . 'includes/class-renderer.php';
require_once RCVS_PATH . 'includes/class-frontend.php';
require_once RCVS_PATH . 'includes/class-admin-term.php';
require_once RCVS_PATH . 'includes/class-plugin.php';

/**
 * Declare compatibility with WooCommerce features (HPOS, Cart & Checkout blocks).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RCVS_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', RCVS_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', array( 'RCVS\\Plugin', 'instance' ), 20 );
