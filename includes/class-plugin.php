<?php
/**
 * Plugin bootstrap.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_woocommerce_notice' ) );
			return;
		}

		Settings::instance();
		Frontend::instance();

		if ( is_admin() ) {
			Admin_Term::instance();
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( RCVS_FILE ), array( $this, 'action_links' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( RCVS_TEXT_DOMAIN, false, dirname( plugin_basename( RCVS_FILE ) ) . '/languages' );
	}

	/**
	 * Register the Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function register_elementor_widget( $widgets_manager ) {
		require_once RCVS_PATH . 'includes/elementor/class-video-slider-widget.php';
		$widgets_manager->register( new Elementor\Video_Slider_Widget() );
	}

	/**
	 * Settings link on plugins screen.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wc-category-video-slider' ) . '</a>' );
		return $links;
	}

	/**
	 * Admin notice when WooCommerce is not active.
	 */
	public function missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'WC Category Video Slider requires WooCommerce to be installed and active.', 'wc-category-video-slider' ) . '</p></div>';
	}
}
