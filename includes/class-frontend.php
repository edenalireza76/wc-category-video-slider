<?php
/**
 * Front-end: assets, automatic insertion, shortcodes, theme/optimizer compatibility.
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
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp', array( $this, 'setup_auto_insert' ) );

		add_shortcode( 'rcvs_video_slider', array( $this, 'shortcode_category' ) );
		add_shortcode( 'rcvs_gallery', array( $this, 'shortcode_gallery' ) );

		if ( Settings::get( 'compat_optimizers' ) ) {
			$this->optimizer_compat();
		}
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
	 * Enqueue early (in <head>) when we know the page will need it – avoids late styles / CLS.
	 */
	public function maybe_enqueue() {
		$term = $this->current_term();
		if ( $term && ( Video_Helper::has_videos( $term->term_id ) || get_term_meta( $term->term_id, Video_Helper::META_IMG, true ) ) ) {
			self::enqueue_assets();
			return;
		}

		// AJAX shop themes (WoodMart AJAX shop, filters, Elementor loop AJAX) swap the page content
		// without reloading <head>, so the files must already be present on every shop archive.
		if ( Settings::get( 'assets_archives' ) && function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
			self::enqueue_assets();
			return;
		}

		// Singular content containing a shortcode.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ( has_shortcode( $post->post_content, 'rcvs_gallery' ) || has_shortcode( $post->post_content, 'rcvs_video_slider' ) ) ) {
				self::enqueue_assets();
			}
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
			// Classic themes + Elementor Pro "Archive Description" widget + WoodMart.
			add_action( 'woocommerce_archive_description', array( $this, 'print_auto' ), (int) apply_filters( 'rcvs_archive_hook_priority', $priority ) );
		}
		if ( Settings::get( 'hook_block' ) ) {
			// Block themes (core/term-description).
			add_filter( 'render_block_core/term-description', array( $this, 'filter_block' ), 10, 2 );
		}
		if ( Settings::get( 'hook_filter' ) ) {
			add_filter( 'get_the_archive_description', array( $this, 'filter_description' ), 20 );
		}
		if ( Settings::get( 'hook_term_desc' ) ) {
			add_filter( 'term_description', array( $this, 'filter_description' ), 20 );
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
	 * Body context only (never inside <head>, titles or meta descriptions).
	 *
	 * @return bool
	 */
	private function in_body() {
		return did_action( 'wp_body_open' ) || ( did_action( 'wp_head' ) && ! doing_action( 'wp_head' ) && ! doing_filter( 'document_title_parts' ) );
	}

	/**
	 * Output on woocommerce_archive_description.
	 */
	public function print_auto() {
		$term = $this->auto_term();
		if ( $term ) {
			echo Renderer::render_category( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
		}
	}

	/**
	 * Block themes: core/term-description block.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Block.
	 * @return string
	 */
	public function filter_block( $content, $block ) {
		unset( $block );
		$term = $this->auto_term();
		if ( ! $term ) {
			return $content;
		}
		$html = Renderer::render_category( $term, array( 'context' => 'block' ) );
		if ( '' === $html ) {
			return $content;
		}
		return 'after' === Settings::get( 'position' ) ? $content . $html : $html . $content;
	}

	/**
	 * Prepend/append on description filters (body only).
	 *
	 * @param string $description Description.
	 * @return string
	 */
	public function filter_description( $description ) {
		if ( ! $this->in_body() || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $description;
		}
		$term = $this->auto_term();
		if ( ! $term ) {
			return $description;
		}
		$html = Renderer::render_category( $term, array( 'context' => 'filter' ) );
		if ( '' === $html ) {
			return $description;
		}
		return 'after' === Settings::get( 'position' ) ? $description . $html : $html . $description;
	}

	/**
	 * Common shortcode attributes → render args.
	 *
	 * @param array $atts Attributes.
	 * @return array
	 */
	private function shortcode_args( $atts ) {
		$layout = isset( $atts['layout'] ) ? sanitize_key( $atts['layout'] ) : '';
		if ( 'main' === $layout ) {
			$layout = 'featured';
		}
		return array(
			'layout'       => $layout,
			'heading'      => null !== $atts['heading'] ? sanitize_text_field( $atts['heading'] ) : null,
			'show_heading' => 'no' !== $atts['show_heading'],
			'play_mode'    => sanitize_key( $atts['play_mode'] ),
			'autoplay'     => $atts['autoplay'],
			'loop'         => $atts['loop'],
			'show_arrows'  => $atts['arrows'],
			'show_dots'    => $atts['dots'],
			'show_titles'  => $atts['titles'],
			'show_play'    => $atts['play_icon'],
			'per_view'     => $atts['columns'],
			'per_view_t'   => $atts['columns_tablet'],
			'per_view_m'   => $atts['columns_mobile'],
			'ratio'        => sanitize_text_field( $atts['ratio'] ),
			'class'        => sanitize_text_field( $atts['class'] ),
			'max_width'    => absint( $atts['max_width'] ),
			'align'        => sanitize_key( $atts['align'] ),
			'show_image'   => 'no' !== $atts['image'],
		);
	}

	/**
	 * Shortcode defaults.
	 *
	 * @return array
	 */
	private function shortcode_defaults() {
		return array(
			'id'             => 0,
			'layout'         => '',
			'heading'        => null,
			'show_heading'   => 'yes',
			'play_mode'      => '',
			'play_icon'      => '',
			'autoplay'       => '',
			'loop'           => '',
			'arrows'         => '',
			'dots'           => '',
			'titles'         => '',
			'columns'        => '',
			'columns_tablet' => '',
			'columns_mobile' => '',
			'ratio'          => '',
			'class'          => '',
			'max_width'      => '',
			'align'          => '',
			'image'          => 'yes',
		);
	}

	/**
	 * [rcvs_video_slider id="12" layout="featured"] – videos of a product category.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode_category( $atts ) {
		$atts    = shortcode_atts( $this->shortcode_defaults(), $atts, 'rcvs_video_slider' );
		$term_id = absint( $atts['id'] );
		if ( ! $term_id ) {
			$term    = $this->current_term();
			$term_id = $term ? $term->term_id : 0;
		}
		if ( ! $term_id ) {
			return '';
		}
		$args            = $this->shortcode_args( $atts );
		$args['context'] = 'shortcode';
		return Renderer::render_category( $term_id, $args );
	}

	/**
	 * [rcvs_gallery id="123" layout="grid" columns="3"] – standalone gallery.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function shortcode_gallery( $atts ) {
		$atts = shortcode_atts( $this->shortcode_defaults(), $atts, 'rcvs_gallery' );
		$id   = absint( $atts['id'] );
		if ( ! $id ) {
			return '';
		}
		$args            = $this->shortcode_args( $atts );
		$args['context'] = 'gallery';
		return Renderer::render_gallery( $id, $args );
	}

	/**
	 * WP Rocket / Perfmatters compatibility.
	 */
	private function optimizer_compat() {
		$js_patterns = array( 'wc-category-video-slider/assets/js/frontend', 'rcvsFront' );
		$selectors   = array( '.rcvs-lightbox', '.rcvs-lock', '.rcvs__player', '.rcvs__dot', '.rcvs--ready', '.rcvs--static', '.rcvs--rtl', '.is-current', '.is-open', '.is-active', '.is-playing' );

		$add = static function ( $list ) use ( $js_patterns ) {
			return array_merge( (array) $list, $js_patterns );
		};

		// Delay JS exclusions (the script is ~6 KB and deferred; delaying it makes the first tap on a video do nothing).
		add_filter( 'rocket_delay_js_exclusions', $add );
		add_filter( 'perfmatters_delay_js_exclusions', $add );

		// Remove unused CSS: keep selectors that only exist after JS runs.
		add_filter(
			'rocket_rucss_safelist',
			static function ( $list ) use ( $selectors ) {
				return array_merge( (array) $list, $selectors );
			}
		);
		add_filter(
			'perfmatters_rucss_excluded_selectors',
			static function ( $list ) use ( $selectors ) {
				return array_merge( (array) $list, $selectors );
			}
		);
	}
}
