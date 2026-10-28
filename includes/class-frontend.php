<?php
/**
 * Front-end: assets, automatic insertion, shortcode.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end controller.
 */
final class Frontend {

	/**
	 * Singleton.
	 *
	 * @var Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Frontend
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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ), 20 );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_assets' ) );
		add_action( 'wp', array( $this, 'setup_auto_insert' ) );
		add_shortcode( 'rcvs_video_slider', array( $this, 'shortcode' ) );
	}

	/**
	 * Register front-end assets.
	 */
	public function register_assets() {
		if ( wp_style_is( 'rcvs-frontend', 'registered' ) ) {
			return;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$css = file_exists( RCVS_PATH . "assets/css/frontend{$min}.css" ) ? "frontend{$min}.css" : 'frontend.css';
		$js  = file_exists( RCVS_PATH . "assets/js/frontend{$min}.js" ) ? "frontend{$min}.js" : 'frontend.js';

		wp_register_style( 'rcvs-frontend', RCVS_URL . 'assets/css/' . $css, array(), RCVS_VERSION );
		wp_add_inline_style( 'rcvs-frontend', Settings::build_css() );

		wp_register_script(
			'rcvs-frontend',
			RCVS_URL . 'assets/js/' . $js,
			array(),
			RCVS_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'rcvs-frontend',
			'rcvsFront',
			array(
				'close'  => __( 'Close video', 'wc-category-video-slider' ),
				'dialog' => __( 'Video player', 'wc-category-video-slider' ),
				/* translators: %d: slide number */
				'goTo'   => __( 'Go to slide %d', 'wc-category-video-slider' ),
			)
		);
	}

	/**
	 * Enqueue assets (callable from the renderer).
	 */
	public static function enqueue_assets() {
		if ( ! wp_style_is( 'rcvs-frontend', 'registered' ) ) {
			self::instance()->register_assets();
		}
		wp_enqueue_style( 'rcvs-frontend' );
		wp_enqueue_script( 'rcvs-frontend' );
	}

	/**
	 * Enqueue early on category pages that have videos (avoids late styles / CLS).
	 */
	public function maybe_enqueue() {
		$term = $this->current_term();
		if ( $term && Video_Helper::has_videos( $term->term_id ) ) {
			self::enqueue_assets();
		}
	}

	/**
	 * Current product category on the main query.
	 *
	 * @return \WP_Term|null
	 */
	private function current_term() {
		if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
			return null;
		}
		$term = get_queried_object();
		return ( $term instanceof \WP_Term && Video_Helper::TAXONOMY === $term->taxonomy ) ? $term : null;
	}

	/**
	 * Hook automatic insertion.
	 */
	public function setup_auto_insert() {
		if ( is_admin() || ! Settings::get( 'auto_insert' ) ) {
			return;
		}

		$after    = 'after' === Settings::get( 'position' );
		$priority = $after ? 11 : 9; // WooCommerce prints the description at priority 10.

		if ( Settings::get( 'hook_wc' ) ) {
			add_action( 'woocommerce_archive_description', array( $this, 'print_auto' ), (int) apply_filters( 'rcvs_archive_hook_priority', $priority ) );
		}
		if ( Settings::get( 'hook_filter' ) ) {
			add_filter( 'get_the_archive_description', array( $this, 'filter_description' ), 20 );
		}
	}

	/**
	 * Whether automatic output is allowed right now.
	 *
	 * @return \WP_Term|null
	 */
	private function auto_term() {
		$term = $this->current_term();
		if ( ! $term || is_feed() ) {
			return null;
		}
		// Same rule WooCommerce uses for the description: first page only.
		if ( absint( get_query_var( 'paged' ) ) > 1 && apply_filters( 'rcvs_first_page_only', true ) ) {
			return null;
		}
		if ( Renderer::was_rendered( $term->term_id ) ) {
			return null;
		}
		return $term;
	}

	/**
	 * Output on woocommerce_archive_description.
	 */
	public function print_auto() {
		$term = $this->auto_term();
		if ( $term ) {
			echo Renderer::render( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
		}
	}

	/**
	 * Prepend/append on get_the_archive_description (body only, never inside <head>).
	 *
	 * @param string $description Description.
	 * @return string
	 */
	public function filter_description( $description ) {
		if ( ! did_action( 'wp_head' ) || doing_action( 'wp_head' ) || doing_filter( 'document_title_parts' ) ) {
			return $description;
		}
		$term = $this->auto_term();
		if ( ! $term ) {
			return $description;
		}
		$html = Renderer::render( $term );
		if ( '' === $html ) {
			return $description;
		}
		return 'after' === Settings::get( 'position' ) ? $description . $html : $html . $description;
	}

	/**
	 * [rcvs_video_slider id="12" heading="..." autoplay="yes"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'          => 0,
				'heading'     => null,
				'show_heading' => 'yes',
				'play_mode'   => '',
				'autoplay'    => '',
				'loop'        => '',
				'arrows'      => '',
				'dots'        => '',
				'titles'      => '',
			),
			$atts,
			'rcvs_video_slider'
		);

		$term_id = absint( $atts['id'] );
		if ( ! $term_id ) {
			$term    = $this->current_term();
			$term_id = $term ? $term->term_id : 0;
		}
		if ( ! $term_id ) {
			return '';
		}

		return Renderer::render(
			$term_id,
			array(
				'heading'      => null !== $atts['heading'] ? sanitize_text_field( $atts['heading'] ) : null,
				'show_heading' => 'no' !== $atts['show_heading'],
				'play_mode'    => sanitize_key( $atts['play_mode'] ),
				'autoplay'     => $atts['autoplay'],
				'loop'         => $atts['loop'],
				'show_arrows'  => $atts['arrows'],
				'show_dots'    => $atts['dots'],
				'show_titles'  => $atts['titles'],
				'context'      => 'shortcode',
			)
		);
	}
}
