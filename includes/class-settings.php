<?php
/**
 * Global settings (WooCommerce → Category Videos).
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page + CSS variable builder.
 */
final class Settings {

	const OPTION    = 'rcvs_settings';
	const GROUP     = 'rcvs_settings_group';
	const PAGE_SLUG = 'rcvs-settings';

	/**
	 * Singleton.
	 *
	 * @var Settings|null
	 */
	private static $instance = null;

	/**
	 * Cached merged settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Get instance.
	 *
	 * @return Settings
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
		add_action( 'admin_menu', array( $this, 'add_menu' ), 60 );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * Default values (no translations here – safe to call early).
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// General.
			'auto_insert'        => 1,
			'hook_wc'            => 1,
			'hook_filter'        => 0,
			'position'           => 'before',
			'default_heading'    => '',
			'heading_tag'        => 'h2',
			'play_mode'          => 'lightbox',
			'autoplay'           => 0,
			'autoplay_delay'     => 5000,
			'loop'               => 1,
			'show_arrows'        => 1,
			'show_dots'          => 1,
			'show_titles'        => 1,
			'image_size'         => 'medium_large',
			'schema'             => 1,
			// Layout.
			'per_view_desktop'   => 3,
			'per_view_tablet'    => 2,
			'per_view_mobile'    => 1.2,
			'gap'                => 16,
			'aspect_ratio'       => '16/9',
			'media_height'       => 0,
			'wrapper_padding'    => '0',
			'wrapper_margin'     => '0 0 24px 0',
			'wrapper_bg'         => '',
			'wrapper_radius'     => 0,
			'item_radius'        => 12,
			'item_border_width'  => 0,
			'item_border_color'  => '#e5e5e5',
			'item_shadow'        => 0,
			'font_family'        => '',
			// Heading.
			'heading_size'       => 20,
			'heading_weight'     => '700',
			'heading_color'      => '',
			'heading_align'      => 'start',
			'heading_spacing'    => 12,
			// Video titles.
			'title_size'         => 14,
			'title_weight'       => '500',
			'title_color'        => '',
			'title_bg'           => '',
			'title_align'        => 'start',
			'title_padding'      => '8px 2px',
			'title_lines'        => 2,
			// Play button & overlay.
			'play_size'          => 56,
			'play_color'         => '#ffffff',
			'play_bg'            => 'rgba(0,0,0,0.55)',
			'play_hover_bg'      => 'rgba(0,0,0,0.8)',
			'overlay_color'      => '#000000',
			'overlay_opacity'    => 0.15,
			// Arrows & dots.
			'arrow_size'         => 40,
			'arrow_color'        => '#222222',
			'arrow_bg'           => '#ffffff',
			'arrow_radius'       => 50,
			'dot_size'           => 8,
			'dot_color'          => '#d0d0d0',
			'dot_active_color'   => '#222222',
			// Lightbox.
			'lightbox_bg'        => 'rgba(0,0,0,0.88)',
			'lightbox_width'     => 1000,
			// Advanced.
			'custom_css'         => '',
		);
	}

	/**
	 * Field definitions grouped by section.
	 *
	 * @return array
	 */
	public static function fields() {
		$weights = array(
			'300' => '300',
			'400' => '400',
			'500' => '500',
			'600' => '600',
			'700' => '700',
			'800' => '800',
			'900' => '900',
		);
		$align   = array(
			'start'  => __( 'Start', 'wc-category-video-slider' ),
			'center' => __( 'Center', 'wc-category-video-slider' ),
			'end'    => __( 'End', 'wc-category-video-slider' ),
		);

		return array(
			'general'  => array(
				'title'  => __( 'General', 'wc-category-video-slider' ),
				'fields' => array(
					'auto_insert'     => array( 'checkbox', __( 'Show automatically on category pages', 'wc-category-video-slider' ) ),
					'hook_wc'         => array( 'checkbox', __( 'Insert into the "woocommerce_archive_description" hook (Elementor "Archive Description" widget, WoodMart and most themes)', 'wc-category-video-slider' ) ),
					'hook_filter'     => array( 'checkbox', __( 'Insert into the "get_the_archive_description" filter (Elementor "Archive Description" dynamic tag inside a Text Editor widget)', 'wc-category-video-slider' ) ),
					'position'        => array(
						'select',
						__( 'Position relative to description text', 'wc-category-video-slider' ),
						array(
							'before' => __( 'Above the description', 'wc-category-video-slider' ),
							'after'  => __( 'Below the description', 'wc-category-video-slider' ),
						),
					),
					'default_heading' => array( 'text', __( 'Default slider heading', 'wc-category-video-slider' ), __( 'Leave empty for no heading. Use {category} for the category name.', 'wc-category-video-slider' ) ),
					'heading_tag'     => array(
						'select',
						__( 'Heading HTML tag', 'wc-category-video-slider' ),
						array(
							'h2'  => 'H2',
							'h3'  => 'H3',
							'h4'  => 'H4',
							'div' => 'div',
							'p'   => 'p',
						),
					),
					'play_mode'       => array(
						'select',
						__( 'Play mode', 'wc-category-video-slider' ),
						array(
							'lightbox' => __( 'Popup (lightbox)', 'wc-category-video-slider' ),
							'inline'   => __( 'Inside the slide', 'wc-category-video-slider' ),
						),
					),
					'autoplay'        => array( 'checkbox', __( 'Slider autoplay', 'wc-category-video-slider' ) ),
					'autoplay_delay'  => array( 'number', __( 'Autoplay delay (ms)', 'wc-category-video-slider' ), '', 1000, 30000, 100 ),
					'loop'            => array( 'checkbox', __( 'Loop', 'wc-category-video-slider' ) ),
					'show_arrows'     => array( 'checkbox', __( 'Show arrows', 'wc-category-video-slider' ) ),
					'show_dots'       => array( 'checkbox', __( 'Show dots', 'wc-category-video-slider' ) ),
					'show_titles'     => array( 'checkbox', __( 'Show video titles', 'wc-category-video-slider' ) ),
					'image_size'      => array( 'image_size', __( 'Cover image size', 'wc-category-video-slider' ) ),
					'schema'          => array( 'checkbox', __( 'Output VideoObject structured data (SEO)', 'wc-category-video-slider' ) ),
				),
			),
			'layout'   => array(
				'title'  => __( 'Layout & size', 'wc-category-video-slider' ),
				'fields' => array(
					'per_view_desktop'  => array( 'number', __( 'Slides per view – desktop', 'wc-category-video-slider' ), __( 'Decimals allowed (e.g. 2.5) to show part of the next slide.', 'wc-category-video-slider' ), 1, 8, 0.1 ),
					'per_view_tablet'   => array( 'number', __( 'Slides per view – tablet (≤1024px)', 'wc-category-video-slider' ), '', 1, 8, 0.1 ),
					'per_view_mobile'   => array( 'number', __( 'Slides per view – mobile (≤767px)', 'wc-category-video-slider' ), '', 1, 8, 0.1 ),
					'gap'               => array( 'number', __( 'Gap between slides (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'aspect_ratio'      => array(
						'select',
						__( 'Video box aspect ratio', 'wc-category-video-slider' ),
						array(
							'16/9' => '16:9',
							'9/16' => __( '9:16 (vertical / reels)', 'wc-category-video-slider' ),
							'4/3'  => '4:3',
							'3/4'  => '3:4',
							'1/1'  => '1:1',
							'21/9' => '21:9',
						),
					),
					'media_height'      => array( 'number', __( 'Fixed video box height (px)', 'wc-category-video-slider' ), __( '0 = use aspect ratio.', 'wc-category-video-slider' ), 0, 1200, 1 ),
					'wrapper_padding'   => array( 'spacing', __( 'Slider padding', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 16px or 10px 20px 10px 20px', 'wc-category-video-slider' ) ),
					'wrapper_margin'    => array( 'spacing', __( 'Slider margin', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 0 0 24px 0', 'wc-category-video-slider' ) ),
					'wrapper_bg'        => array( 'color', __( 'Slider background', 'wc-category-video-slider' ) ),
					'wrapper_radius'    => array( 'number', __( 'Slider border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'item_radius'       => array( 'number', __( 'Video box border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'item_border_width' => array( 'number', __( 'Video box border width (px)', 'wc-category-video-slider' ), '', 0, 20, 1 ),
					'item_border_color' => array( 'color', __( 'Video box border color', 'wc-category-video-slider' ) ),
					'item_shadow'       => array( 'checkbox', __( 'Video box shadow', 'wc-category-video-slider' ) ),
					'font_family'       => array( 'text', __( 'Font family', 'wc-category-video-slider' ), __( 'Leave empty to inherit the theme font. Example: IRANSansX, sans-serif', 'wc-category-video-slider' ) ),
				),
			),
			'heading'  => array(
				'title'  => __( 'Slider heading', 'wc-category-video-slider' ),
				'fields' => array(
					'heading_size'    => array( 'number', __( 'Font size (px)', 'wc-category-video-slider' ), '', 8, 80, 1 ),
					'heading_weight'  => array( 'select', __( 'Font weight', 'wc-category-video-slider' ), $weights ),
					'heading_color'   => array( 'color', __( 'Color', 'wc-category-video-slider' ) ),
					'heading_align'   => array( 'select', __( 'Alignment', 'wc-category-video-slider' ), $align ),
					'heading_spacing' => array( 'number', __( 'Space below heading (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
				),
			),
			'title'    => array(
				'title'  => __( 'Video titles', 'wc-category-video-slider' ),
				'fields' => array(
					'title_size'    => array( 'number', __( 'Font size (px)', 'wc-category-video-slider' ), '', 8, 60, 1 ),
					'title_weight'  => array( 'select', __( 'Font weight', 'wc-category-video-slider' ), $weights ),
					'title_color'   => array( 'color', __( 'Color', 'wc-category-video-slider' ) ),
					'title_bg'      => array( 'color', __( 'Background', 'wc-category-video-slider' ) ),
					'title_align'   => array( 'select', __( 'Alignment', 'wc-category-video-slider' ), $align ),
					'title_padding' => array( 'spacing', __( 'Padding', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 8px 2px', 'wc-category-video-slider' ) ),
					'title_lines'   => array( 'number', __( 'Maximum lines', 'wc-category-video-slider' ), '', 1, 6, 1 ),
				),
			),
			'controls' => array(
				'title'  => __( 'Play button, arrows & dots', 'wc-category-video-slider' ),
				'fields' => array(
					'play_size'        => array( 'number', __( 'Play button size (px)', 'wc-category-video-slider' ), '', 20, 160, 1 ),
					'play_color'       => array( 'color', __( 'Play icon color', 'wc-category-video-slider' ) ),
					'play_bg'          => array( 'color', __( 'Play button background', 'wc-category-video-slider' ) ),
					'play_hover_bg'    => array( 'color', __( 'Play button background (hover)', 'wc-category-video-slider' ) ),
					'overlay_color'    => array( 'color', __( 'Cover overlay color', 'wc-category-video-slider' ) ),
					'overlay_opacity'  => array( 'number', __( 'Cover overlay opacity (0–1)', 'wc-category-video-slider' ), '', 0, 1, 0.05 ),
					'arrow_size'       => array( 'number', __( 'Arrow size (px)', 'wc-category-video-slider' ), '', 20, 100, 1 ),
					'arrow_color'      => array( 'color', __( 'Arrow icon color', 'wc-category-video-slider' ) ),
					'arrow_bg'         => array( 'color', __( 'Arrow background', 'wc-category-video-slider' ) ),
					'arrow_radius'     => array( 'number', __( 'Arrow border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'dot_size'         => array( 'number', __( 'Dot size (px)', 'wc-category-video-slider' ), '', 4, 30, 1 ),
					'dot_color'        => array( 'color', __( 'Dot color', 'wc-category-video-slider' ) ),
					'dot_active_color' => array( 'color', __( 'Active dot color', 'wc-category-video-slider' ) ),
					'lightbox_bg'      => array( 'color', __( 'Popup background', 'wc-category-video-slider' ) ),
					'lightbox_width'   => array( 'number', __( 'Popup max width (px)', 'wc-category-video-slider' ), '', 300, 2400, 10 ),
				),
			),
			'advanced' => array(
				'title'  => __( 'Advanced', 'wc-category-video-slider' ),
				'fields' => array(
					'custom_css' => array( 'textarea', __( 'Custom CSS', 'wc-category-video-slider' ), __( 'Root class: .rcvs – items: .rcvs__slide, .rcvs__media, .rcvs__title, .rcvs__play, .rcvs__arrow, .rcvs__dot', 'wc-category-video-slider' ) ),
				),
			),
		);
	}

	/**
	 * Merged settings.
	 *
	 * @return array
	 */
	public static function get_all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	/**
	 * Single setting.
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::get_all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Flush cache after save.
	 */
	public static function flush_cache() {
		self::$cache = null;
	}

	/**
	 * Admin menu.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Category Video Slider', 'wc-category-video-slider' ),
			__( 'Category Videos', 'wc-category-video-slider' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting.
	 */
	public function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize the whole option.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize( $input ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by options.php.
		if ( isset( $_POST['rcvs_reset'] ) ) {
			add_settings_error( self::OPTION, 'rcvs_reset', __( 'Settings were reset to defaults.', 'wc-category-video-slider' ), 'updated' );
			return self::defaults();
		}

		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$out      = array();

		foreach ( self::fields() as $section ) {
			foreach ( $section['fields'] as $key => $field ) {
				$type  = $field[0];
				$value = isset( $input[ $key ] ) ? $input[ $key ] : null;

				switch ( $type ) {
					case 'checkbox':
						$out[ $key ] = empty( $value ) ? 0 : 1;
						break;

					case 'number':
						$min         = isset( $field[3] ) ? (float) $field[3] : 0;
						$max         = isset( $field[4] ) ? (float) $field[4] : PHP_INT_MAX;
						$num         = is_numeric( $value ) ? (float) $value : (float) $defaults[ $key ];
						$num         = min( $max, max( $min, $num ) );
						$out[ $key ] = ( floor( $num ) === $num ) ? (int) $num : round( $num, 2 );
						break;

					case 'select':
						$options     = isset( $field[2] ) ? $field[2] : array();
						$value       = is_string( $value ) ? $value : '';
						$out[ $key ] = array_key_exists( $value, $options ) ? $value : $defaults[ $key ];
						break;

					case 'image_size':
						$value       = is_string( $value ) ? sanitize_key( $value ) : '';
						$sizes       = array_merge( get_intermediate_image_sizes(), array( 'full' ) );
						$out[ $key ] = in_array( $value, $sizes, true ) ? $value : $defaults[ $key ];
						break;

					case 'color':
						$out[ $key ] = self::sanitize_color( $value );
						break;

					case 'spacing':
						$out[ $key ] = self::sanitize_spacing( $value, $defaults[ $key ] );
						break;

					case 'textarea':
						$out[ $key ] = is_string( $value ) ? trim( wp_strip_all_tags( $value ) ) : '';
						break;

					case 'text':
					default:
						$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
						if ( 'font_family' === $key ) {
							$value = preg_replace( '/[^\p{L}\p{N}\s,\'"\-_]/u', '', $value );
						}
						$out[ $key ] = $value;
						break;
				}
			}
		}

		return $out;
	}

	/**
	 * Allow hex, rgb(a), hsl(a), transparent, currentColor and named colors.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function sanitize_color( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^#([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[\d.%\s,\/]+\)$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^[a-z]{3,20}$/i', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Sanitize CSS spacing shorthand (1–4 values).
	 *
	 * @param mixed  $value    Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	public static function sanitize_spacing( $value, $fallback = '0' ) {
		if ( ! is_string( $value ) ) {
			return $fallback;
		}
		$value = trim( preg_replace( '/\s+/', ' ', $value ) );
		if ( '' === $value ) {
			return '0';
		}
		if ( preg_match( '/^(-?\d*\.?\d+(px|em|rem|%|vw|vh)?|auto)( (-?\d*\.?\d+(px|em|rem|%|vw|vh)?|auto)){0,3}$/', $value ) ) {
			return $value;
		}
		return $fallback;
	}

	/**
	 * Build front-end CSS from settings (custom properties).
	 *
	 * @return string
	 */
	public static function build_css() {
		$s = self::get_all();

		$color = static function ( $value, $fallback ) {
			$value = self::sanitize_color( $value );
			return '' !== $value ? $value : $fallback;
		};

		$align_map = array(
			'start'  => 'start',
			'center' => 'center',
			'end'    => 'end',
		);

		$vars = array(
			'--rcvs-per-view'        => (float) $s['per_view_desktop'],
			'--rcvs-gap'             => absint( $s['gap'] ) . 'px',
			'--rcvs-ratio'           => preg_match( '#^\d+/\d+$#', $s['aspect_ratio'] ) ? $s['aspect_ratio'] : '16/9',
			'--rcvs-height'          => absint( $s['media_height'] ) > 0 ? absint( $s['media_height'] ) . 'px' : 'auto',
			'--rcvs-padding'         => self::sanitize_spacing( $s['wrapper_padding'], '0' ),
			'--rcvs-margin'          => self::sanitize_spacing( $s['wrapper_margin'], '0 0 24px 0' ),
			'--rcvs-bg'              => $color( $s['wrapper_bg'], 'transparent' ),
			'--rcvs-wrap-radius'     => absint( $s['wrapper_radius'] ) . 'px',
			'--rcvs-radius'          => absint( $s['item_radius'] ) . 'px',
			'--rcvs-border-width'    => absint( $s['item_border_width'] ) . 'px',
			'--rcvs-border-color'    => $color( $s['item_border_color'], 'transparent' ),
			'--rcvs-shadow'          => $s['item_shadow'] ? '0 6px 20px rgba(0,0,0,.12)' : 'none',
			'--rcvs-font'            => '' !== trim( (string) $s['font_family'] ) ? $s['font_family'] : 'inherit',
			'--rcvs-h-size'          => absint( $s['heading_size'] ) . 'px',
			'--rcvs-h-weight'        => absint( $s['heading_weight'] ),
			'--rcvs-h-color'         => $color( $s['heading_color'], 'inherit' ),
			'--rcvs-h-align'         => isset( $align_map[ $s['heading_align'] ] ) ? $align_map[ $s['heading_align'] ] : 'start',
			'--rcvs-h-spacing'       => absint( $s['heading_spacing'] ) . 'px',
			'--rcvs-t-size'          => absint( $s['title_size'] ) . 'px',
			'--rcvs-t-weight'        => absint( $s['title_weight'] ),
			'--rcvs-t-color'         => $color( $s['title_color'], 'inherit' ),
			'--rcvs-t-bg'            => $color( $s['title_bg'], 'transparent' ),
			'--rcvs-t-align'         => isset( $align_map[ $s['title_align'] ] ) ? $align_map[ $s['title_align'] ] : 'start',
			'--rcvs-t-padding'       => self::sanitize_spacing( $s['title_padding'], '8px 2px' ),
			'--rcvs-t-lines'         => max( 1, absint( $s['title_lines'] ) ),
			'--rcvs-play-size'       => absint( $s['play_size'] ) . 'px',
			'--rcvs-play-color'      => $color( $s['play_color'], '#fff' ),
			'--rcvs-play-bg'         => $color( $s['play_bg'], 'rgba(0,0,0,.55)' ),
			'--rcvs-play-hover-bg'   => $color( $s['play_hover_bg'], 'rgba(0,0,0,.8)' ),
			'--rcvs-overlay'         => $color( $s['overlay_color'], '#000' ),
			'--rcvs-overlay-opacity' => min( 1, max( 0, (float) $s['overlay_opacity'] ) ),
			'--rcvs-arrow-size'      => absint( $s['arrow_size'] ) . 'px',
			'--rcvs-arrow-color'     => $color( $s['arrow_color'], '#222' ),
			'--rcvs-arrow-bg'        => $color( $s['arrow_bg'], '#fff' ),
			'--rcvs-arrow-radius'    => absint( $s['arrow_radius'] ) . 'px',
			'--rcvs-dot-size'        => absint( $s['dot_size'] ) . 'px',
			'--rcvs-dot-color'       => $color( $s['dot_color'], '#d0d0d0' ),
			'--rcvs-dot-active'      => $color( $s['dot_active_color'], '#222' ),
		);

		$decl = '';
		foreach ( $vars as $name => $value ) {
			$decl .= $name . ':' . $value . ';';
		}

		$bp_tablet = (int) apply_filters( 'rcvs_breakpoint_tablet', 1024 );
		$bp_mobile = (int) apply_filters( 'rcvs_breakpoint_mobile', 767 );

		$css  = '.rcvs{' . $decl . '}';
		$css .= '@media (max-width:' . $bp_tablet . 'px){.rcvs{--rcvs-per-view:' . (float) $s['per_view_tablet'] . ';}}';
		$css .= '@media (max-width:' . $bp_mobile . 'px){.rcvs{--rcvs-per-view:' . (float) $s['per_view_mobile'] . ';}}';
		$css .= '.rcvs-lightbox{--rcvs-lb-bg:' . $color( $s['lightbox_bg'], 'rgba(0,0,0,.88)' ) . ';--rcvs-lb-width:' . absint( $s['lightbox_width'] ) . 'px;}';

		if ( '' !== $s['custom_css'] ) {
			$css .= wp_strip_all_tags( $s['custom_css'] );
		}

		/**
		 * Filter the generated front-end CSS.
		 *
		 * @param string $css      CSS.
		 * @param array  $settings Settings.
		 */
		return (string) apply_filters( 'rcvs_inline_css', $css, $s );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$values = self::get_all();
		?>
		<div class="wrap rcvs-settings">
			<h1><?php esc_html_e( 'Category Video Slider', 'wc-category-video-slider' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Videos are added per category in Products → Categories → Edit category. These options control how every category video slider looks and behaves. If you use the Elementor "Category Video Slider" widget, its own style controls override these values.', 'wc-category-video-slider' ); ?>
			</p>
			<?php settings_errors( self::OPTION ); ?>

			<nav class="nav-tab-wrapper rcvs-tabs">
				<?php
				$first = true;
				foreach ( self::fields() as $id => $section ) {
					printf(
						'<a href="#rcvs-tab-%1$s" class="nav-tab%2$s" data-tab="%1$s">%3$s</a>',
						esc_attr( $id ),
						$first ? ' nav-tab-active' : '',
						esc_html( $section['title'] )
					);
					$first = false;
				}
				?>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<?php
				$first = true;
				foreach ( self::fields() as $id => $section ) :
					?>
					<div class="rcvs-tab-panel<?php echo $first ? ' is-active' : ''; ?>" id="rcvs-tab-<?php echo esc_attr( $id ); ?>">
						<table class="form-table" role="presentation">
							<?php
							foreach ( $section['fields'] as $key => $field ) {
								$this->render_field( $key, $field, $values[ $key ] );
							}
							?>
						</table>
					</div>
					<?php
					$first = false;
				endforeach;
				?>
				<p class="submit">
					<?php submit_button( __( 'Save changes', 'wc-category-video-slider' ), 'primary', 'submit', false ); ?>
					&nbsp;
					<button type="submit" name="rcvs_reset" value="1" class="button rcvs-reset" data-confirm="<?php esc_attr_e( 'Reset all settings to defaults?', 'wc-category-video-slider' ); ?>">
						<?php esc_html_e( 'Reset to defaults', 'wc-category-video-slider' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Render one field row.
	 *
	 * @param string $key   Key.
	 * @param array  $field Definition.
	 * @param mixed  $value Value.
	 */
	private function render_field( $key, $field, $value ) {
		$type  = $field[0];
		$label = $field[1];
		$id    = 'rcvs_' . $key;
		$name  = self::OPTION . '[' . $key . ']';
		$help  = '';

		if ( in_array( $type, array( 'text', 'spacing', 'textarea', 'number' ), true ) && ! empty( $field[2] ) && is_string( $field[2] ) ) {
			$help = $field[2];
		}
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<?php
				switch ( $type ) {
					case 'checkbox':
						printf(
							'<input type="hidden" name="%1$s" value="0"><label class="rcvs-switch"><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s><span></span></label>',
							esc_attr( $name ),
							esc_attr( $id ),
							checked( 1, (int) $value, false )
						);
						break;

					case 'number':
						printf(
							'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$s" max="%5$s" step="%6$s">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value ),
							esc_attr( $field[3] ),
							esc_attr( $field[4] ),
							esc_attr( $field[5] )
						);
						break;

					case 'select':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( $field[2] as $opt_value => $opt_label ) {
							printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt_value ), selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
						}
						echo '</select>';
						break;

					case 'image_size':
						echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
						foreach ( array_merge( get_intermediate_image_sizes(), array( 'full' ) ) as $size ) {
							printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $size ), selected( (string) $value, $size, false ) );
						}
						echo '</select>';
						break;

					case 'color':
						$hex = preg_match( '/^#[a-f0-9]{6}$/i', (string) $value ) ? $value : '#000000';
						printf(
							'<span class="rcvs-color"><input type="color" value="%1$s" aria-label="%4$s"><input type="text" class="regular-text code" id="%2$s" name="%3$s" value="%5$s" placeholder="#000000 / rgba(0,0,0,0.5)"></span>',
							esc_attr( $hex ),
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $label ),
							esc_attr( $value )
						);
						break;

					case 'textarea':
						printf(
							'<textarea class="large-text code" rows="8" id="%1$s" name="%2$s" dir="ltr">%3$s</textarea>',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_textarea( $value )
						);
						break;

					case 'spacing':
						printf(
							'<input type="text" class="regular-text code" dir="ltr" id="%1$s" name="%2$s" value="%3$s">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value )
						);
						break;

					case 'text':
					default:
						printf(
							'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s">',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value )
						);
						break;
				}

				if ( $help ) {
					echo '<p class="description">' . esc_html( $help ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}
}
