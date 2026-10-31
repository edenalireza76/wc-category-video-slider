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
			'layout'                => 'slider',
			'auto_insert'           => 1,
			'position'              => 'before',
			'default_heading'       => '',
			'heading_tag'           => 'h2',
			'play_mode'             => 'lightbox',
			'autoplay'              => 0,
			'autoplay_delay'        => 5000,
			'loop'                  => 1,
			'show_arrows'           => 1,
			'show_dots'             => 1,
			'show_titles'           => 1,
			'image_size'            => 'medium_large',
			'schema'                => 1,
			// Slider layout.
			'per_view_desktop'      => 3,
			'per_view_tablet'       => 2,
			'per_view_mobile'       => 1.2,
			'grid_cols_desktop'     => 3,
			'grid_cols_tablet'      => 2,
			'grid_cols_mobile'      => 1,
			'gap'                   => 16,
			'aspect_ratio'          => '16/9',
			'media_height'          => 0,
			'wrapper_padding'       => '0',
			'wrapper_margin'        => '0 0 24px 0',
			'wrapper_max_width'     => 0,
			'wrapper_align'         => 'center',
			'wrapper_bg'            => '',
			'wrapper_radius'        => 0,
			'item_radius'           => 12,
			'item_border_width'     => 0,
			'item_border_color'     => '#e5e5e5',
			'item_shadow'           => 0,
			'font_family'           => '',
			// Featured (main video + gallery).
			'featured_ratio'        => 'auto',
			'featured_max_width'    => 0,
			'featured_max_height'   => 75,
			'featured_align'        => 'center',
			'featured_spacing'      => 16,
			'featured_pv_desktop'   => 4,
			'featured_pv_tablet'    => 3,
			'featured_pv_mobile'    => 2.2,
			'featured_thumb_action' => 'main',
			'featured_include_main' => 1,
			'featured_show_title'   => 1,
			'featured_title_size'   => 18,
			'featured_title_weight' => '700',
			'featured_title_color'  => '',
			'featured_active_color' => '#2271b1',
			// Heading.
			'heading_size'          => 20,
			'heading_weight'        => '700',
			'heading_color'         => '',
			'heading_align'         => 'start',
			'heading_spacing'       => 12,
			// Video titles.
			'title_size'            => 14,
			'title_weight'          => '500',
			'title_color'           => '',
			'title_bg'              => '',
			'title_align'           => 'start',
			'title_padding'         => '8px 2px',
			'title_lines'           => 2,
			// Play button.
			'show_play'             => 1,
			'play_icon'             => 'triangle',
			'play_custom_id'        => 0,
			'play_shape'            => 'circle',
			'play_position'         => 'center',
			'play_offset'           => 16,
			'play_size'             => 56,
			'play_icon_scale'       => 42,
			'play_color'            => '#ffffff',
			'play_hover_color'      => '',
			'play_bg'               => 'rgba(0,0,0,0.55)',
			'play_hover_bg'         => 'rgba(0,0,0,0.8)',
			'play_border_width'     => 0,
			'play_border_color'     => 'rgba(255,255,255,0.85)',
			'play_animation'        => 'none',
			'overlay_color'         => '#000000',
			'overlay_opacity'       => 0.15,
			// Arrows, dots & popup.
			'arrow_size'            => 40,
			'arrow_color'           => '#222222',
			'arrow_bg'              => '#ffffff',
			'arrow_radius'          => 50,
			'dot_size'              => 8,
			'dot_color'             => '#d0d0d0',
			'dot_active_color'      => '#222222',
			'lightbox_bg'           => 'rgba(0,0,0,0.88)',
			'lightbox_width'        => 1000,
			// Category description image.
			'img_position'          => 'before',
			'img_size'              => 'large',
			'img_max_width'         => 0,
			'img_align'             => 'center',
			'img_radius'            => 12,
			'img_spacing'           => 16,
			'img_shadow'            => 0,
			// Compatibility.
			'hook_wc'               => 1,
			'hook_block'            => 1,
			'hook_filter'           => 0,
			'hook_term_desc'        => 0,
			'assets_archives'       => 1,
			'compat_optimizers'     => 1,
			// Advanced.
			'custom_css'            => '',
		);
	}

	/**
	 * Field definitions grouped by section.
	 *
	 * @return array
	 */
	public static function fields() {
		$ratios = array(
			'16/9' => '16:9',
			'9/16' => __( '9:16 (vertical / reels)', 'wc-category-video-slider' ),
			'4/3'  => '4:3',
			'3/4'  => '3:4',
			'1/1'  => '1:1',
			'21/9' => '21:9',
		);

		return array(
			'general'  => array(
				'title'  => __( 'General', 'wc-category-video-slider' ),
				'fields' => array(
					'layout'          => array( 'select', __( 'Default layout', 'wc-category-video-slider' ), self::layout_options(), __( 'Can be changed per category, per gallery, in the Elementor widget and in the shortcode.', 'wc-category-video-slider' ) ),
					'auto_insert'     => array( 'checkbox', __( 'Show automatically on category pages', 'wc-category-video-slider' ) ),
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
					'per_view_desktop'  => array( 'number', __( 'Slider: slides per view – desktop', 'wc-category-video-slider' ), __( 'Decimals allowed (e.g. 2.5) to show part of the next slide.', 'wc-category-video-slider' ), 1, 8, 0.1 ),
					'per_view_tablet'   => array( 'number', __( 'Slider: slides per view – tablet (≤1024px)', 'wc-category-video-slider' ), '', 1, 8, 0.1 ),
					'per_view_mobile'   => array( 'number', __( 'Slider: slides per view – mobile (≤767px)', 'wc-category-video-slider' ), '', 1, 8, 0.1 ),
					'grid_cols_desktop' => array( 'number', __( 'Grid: columns – desktop', 'wc-category-video-slider' ), '', 1, 8, 1 ),
					'grid_cols_tablet'  => array( 'number', __( 'Grid: columns – tablet', 'wc-category-video-slider' ), '', 1, 8, 1 ),
					'grid_cols_mobile'  => array( 'number', __( 'Grid: columns – mobile', 'wc-category-video-slider' ), '', 1, 8, 1 ),
					'gap'               => array( 'number', __( 'Gap between videos (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'aspect_ratio'      => array( 'select', __( 'Video box aspect ratio', 'wc-category-video-slider' ), $ratios ),
					'media_height'      => array( 'number', __( 'Fixed video box height (px)', 'wc-category-video-slider' ), __( '0 = use aspect ratio.', 'wc-category-video-slider' ), 0, 1200, 1 ),
					'wrapper_padding'   => array( 'spacing', __( 'Slider padding', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 16px or 10px 20px 10px 20px', 'wc-category-video-slider' ) ),
					'wrapper_margin'    => array( 'spacing', __( 'Slider margin', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 0 0 24px 0', 'wc-category-video-slider' ) ),
					'wrapper_max_width' => array( 'number', __( 'Gallery max width (px)', 'wc-category-video-slider' ), __( '0 = full width. Limits the whole video block (slider, grid or main video). Can be changed per category / gallery.', 'wc-category-video-slider' ), 0, 3000, 10 ),
					'wrapper_align'     => array( 'select', __( 'Gallery alignment (when narrower than the page)', 'wc-category-video-slider' ), self::aligns() ),
					'wrapper_bg'        => array( 'color', __( 'Slider background', 'wc-category-video-slider' ) ),
					'wrapper_radius'    => array( 'number', __( 'Slider border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'item_radius'       => array( 'number', __( 'Video box border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'item_border_width' => array( 'number', __( 'Video box border width (px)', 'wc-category-video-slider' ), '', 0, 20, 1 ),
					'item_border_color' => array( 'color', __( 'Video box border color', 'wc-category-video-slider' ) ),
					'item_shadow'       => array( 'checkbox', __( 'Video box shadow', 'wc-category-video-slider' ) ),
					'font_family'       => array( 'text', __( 'Font family', 'wc-category-video-slider' ), __( 'Leave empty to inherit the theme font. Example: IRANSansX, sans-serif', 'wc-category-video-slider' ) ),
				),
			),
			'featured' => array(
				'title'  => __( 'Main video layout', 'wc-category-video-slider' ),
				'intro'  => __( 'One large main video with the rest of the gallery as a slider underneath. Choose the main video with the "Main video" option on each video; otherwise the first video is used.', 'wc-category-video-slider' ),
				'fields' => array(
					'featured_ratio'        => array( 'select', __( 'Main video aspect ratio', 'wc-category-video-slider' ), array( 'auto' => __( 'Auto – fit each video (vertical / horizontal)', 'wc-category-video-slider' ) ) + $ratios ),
					'featured_max_width'    => array( 'number', __( 'Main video max width (px)', 'wc-category-video-slider' ), __( '0 = full width.', 'wc-category-video-slider' ), 0, 2400, 10 ),
					'featured_max_height'   => array( 'number', __( 'Main video max height (% of screen height)', 'wc-category-video-slider' ), __( 'Keeps vertical videos from becoming huge: the width shrinks to fit. 0 = no limit.', 'wc-category-video-slider' ), 0, 100, 1 ),
					'featured_align'        => array( 'select', __( 'Main video alignment', 'wc-category-video-slider' ), self::aligns() ),
					'featured_spacing'      => array( 'number', __( 'Space between main video and gallery (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'featured_pv_desktop'   => array( 'number', __( 'Gallery thumbnails per view – desktop', 'wc-category-video-slider' ), '', 1, 10, 0.1 ),
					'featured_pv_tablet'    => array( 'number', __( 'Gallery thumbnails per view – tablet', 'wc-category-video-slider' ), '', 1, 10, 0.1 ),
					'featured_pv_mobile'    => array( 'number', __( 'Gallery thumbnails per view – mobile', 'wc-category-video-slider' ), '', 1, 10, 0.1 ),
					'featured_thumb_action' => array(
						'select',
						__( 'Clicking a gallery video', 'wc-category-video-slider' ),
						array(
							'main'     => __( 'Plays it in the main video box', 'wc-category-video-slider' ),
							'lightbox' => __( 'Opens it in a popup', 'wc-category-video-slider' ),
						),
					),
					'featured_include_main' => array( 'checkbox', __( 'Also show the main video in the gallery below', 'wc-category-video-slider' ) ),
					'featured_show_title'   => array( 'checkbox', __( 'Show the main video title', 'wc-category-video-slider' ) ),
					'featured_title_size'   => array( 'number', __( 'Main title font size (px)', 'wc-category-video-slider' ), '', 8, 80, 1 ),
					'featured_title_weight' => array( 'select', __( 'Main title font weight', 'wc-category-video-slider' ), self::weights() ),
					'featured_title_color'  => array( 'color', __( 'Main title color', 'wc-category-video-slider' ) ),
					'featured_active_color' => array( 'color', __( 'Highlight color of the playing video', 'wc-category-video-slider' ) ),
				),
			),
			'heading'  => array(
				'title'  => __( 'Heading & titles', 'wc-category-video-slider' ),
				'fields' => array(
					'heading_size'    => array( 'number', __( 'Heading font size (px)', 'wc-category-video-slider' ), '', 8, 80, 1 ),
					'heading_weight'  => array( 'select', __( 'Heading font weight', 'wc-category-video-slider' ), self::weights() ),
					'heading_color'   => array( 'color', __( 'Heading color', 'wc-category-video-slider' ) ),
					'heading_align'   => array( 'select', __( 'Heading alignment', 'wc-category-video-slider' ), self::aligns() ),
					'heading_spacing' => array( 'number', __( 'Space below heading (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'title_size'      => array( 'number', __( 'Video title font size (px)', 'wc-category-video-slider' ), '', 8, 60, 1 ),
					'title_weight'    => array( 'select', __( 'Video title font weight', 'wc-category-video-slider' ), self::weights() ),
					'title_color'     => array( 'color', __( 'Video title color', 'wc-category-video-slider' ) ),
					'title_bg'        => array( 'color', __( 'Video title background', 'wc-category-video-slider' ) ),
					'title_align'     => array( 'select', __( 'Video title alignment', 'wc-category-video-slider' ), self::aligns() ),
					'title_padding'   => array( 'spacing', __( 'Video title padding', 'wc-category-video-slider' ), __( 'CSS shorthand, e.g. 8px 2px', 'wc-category-video-slider' ) ),
					'title_lines'     => array( 'number', __( 'Video title maximum lines', 'wc-category-video-slider' ), '', 1, 6, 1 ),
				),
			),
			'play'     => array(
				'title'  => __( 'Play button', 'wc-category-video-slider' ),
				'fields' => array(
					'show_play'         => array( 'checkbox', __( 'Show play button on covers', 'wc-category-video-slider' ), __( 'Turn off if your cover images already contain a play icon. It can also be hidden for a single video in the gallery editor.', 'wc-category-video-slider' ) ),
					'play_icon'         => array( 'icon', __( 'Play icon', 'wc-category-video-slider' ), self::icon_options() ),
					'play_custom_id'    => array( 'image', __( 'Custom icon image', 'wc-category-video-slider' ), __( 'Used when "Custom image" is selected. PNG, WebP or SVG (if your site allows SVG uploads).', 'wc-category-video-slider' ) ),
					'play_shape'        => array(
						'select',
						__( 'Button shape', 'wc-category-video-slider' ),
						array(
							'circle'  => __( 'Circle', 'wc-category-video-slider' ),
							'rounded' => __( 'Rounded square', 'wc-category-video-slider' ),
							'square'  => __( 'Square', 'wc-category-video-slider' ),
							'none'    => __( 'Icon only (no background)', 'wc-category-video-slider' ),
						),
					),
					'play_position'     => array(
						'select',
						__( 'Button position', 'wc-category-video-slider' ),
						array(
							'center'       => __( 'Center', 'wc-category-video-slider' ),
							'bottom-start' => __( 'Bottom – start corner', 'wc-category-video-slider' ),
							'bottom-end'   => __( 'Bottom – end corner', 'wc-category-video-slider' ),
							'top-start'    => __( 'Top – start corner', 'wc-category-video-slider' ),
							'top-end'      => __( 'Top – end corner', 'wc-category-video-slider' ),
						),
					),
					'play_offset'       => array( 'number', __( 'Corner offset (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'play_size'         => array( 'number', __( 'Button size (px)', 'wc-category-video-slider' ), '', 16, 200, 1 ),
					'play_icon_scale'   => array( 'number', __( 'Icon size inside button (%)', 'wc-category-video-slider' ), '', 10, 100, 1 ),
					'play_color'        => array( 'color', __( 'Icon color', 'wc-category-video-slider' ) ),
					'play_hover_color'  => array( 'color', __( 'Icon color (hover)', 'wc-category-video-slider' ) ),
					'play_bg'           => array( 'color', __( 'Button background', 'wc-category-video-slider' ) ),
					'play_hover_bg'     => array( 'color', __( 'Button background (hover)', 'wc-category-video-slider' ) ),
					'play_border_width' => array( 'number', __( 'Button border width (px)', 'wc-category-video-slider' ), '', 0, 10, 1 ),
					'play_border_color' => array( 'color', __( 'Button border color', 'wc-category-video-slider' ) ),
					'play_animation'    => array(
						'select',
						__( 'Animation', 'wc-category-video-slider' ),
						array(
							'none'  => __( 'None', 'wc-category-video-slider' ),
							'pulse' => __( 'Pulse ring', 'wc-category-video-slider' ),
						),
					),
					'overlay_color'     => array( 'color', __( 'Cover overlay color', 'wc-category-video-slider' ) ),
					'overlay_opacity'   => array( 'number', __( 'Cover overlay opacity (0–1)', 'wc-category-video-slider' ), '', 0, 1, 0.05 ),
				),
			),
			'controls' => array(
				'title'  => __( 'Arrows, dots & popup', 'wc-category-video-slider' ),
				'fields' => array(
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
			'image'    => array(
				'title'  => __( 'Category image', 'wc-category-video-slider' ),
				'intro'  => __( 'An optional image for each category (set it on the category edit screen), shown above or below the videos.', 'wc-category-video-slider' ),
				'fields' => array(
					'img_position'  => array(
						'select',
						__( 'Default position', 'wc-category-video-slider' ),
						array(
							'before' => __( 'Above the videos', 'wc-category-video-slider' ),
							'after'  => __( 'Below the videos', 'wc-category-video-slider' ),
						),
						__( 'Can be changed per category.', 'wc-category-video-slider' ),
					),
					'img_size'      => array( 'image_size', __( 'Image size', 'wc-category-video-slider' ) ),
					'img_max_width' => array( 'number', __( 'Image max width (px)', 'wc-category-video-slider' ), __( '0 = full width.', 'wc-category-video-slider' ), 0, 3000, 10 ),
					'img_align'     => array( 'select', __( 'Image alignment', 'wc-category-video-slider' ), self::aligns() ),
					'img_radius'    => array( 'number', __( 'Image border radius (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'img_spacing'   => array( 'number', __( 'Space between image and videos (px)', 'wc-category-video-slider' ), '', 0, 100, 1 ),
					'img_shadow'    => array( 'checkbox', __( 'Image shadow', 'wc-category-video-slider' ) ),
				),
			),
			'compat'   => array(
				'title'  => __( 'Theme compatibility', 'wc-category-video-slider' ),
				'intro'  => __( 'Where the slider is inserted automatically. The defaults work with Hello Elementor + Elementor Pro (Archive Description widget), WoodMart, Astra, Flatsome, Kadence, Blocksy, OceanWP, Storefront and block themes. The slider is never printed twice on the same page.', 'wc-category-video-slider' ),
				'fields' => array(
					'hook_wc'           => array( 'checkbox', __( 'WooCommerce "woocommerce_archive_description" hook', 'wc-category-video-slider' ), __( 'Used by the Elementor Pro "Archive Description" widget, WoodMart and almost every classic WooCommerce theme.', 'wc-category-video-slider' ) ),
					'hook_block'        => array( 'checkbox', __( 'Block themes: "Term Description" block', 'wc-category-video-slider' ), __( 'For full-site-editing themes (Twenty Twenty-Five, WooCommerce block templates…).', 'wc-category-video-slider' ) ),
					'hook_filter'       => array( 'checkbox', __( 'Archive description filter (get_the_archive_description)', 'wc-category-video-slider' ), __( 'For the Elementor "Archive Description" dynamic tag inside a Text Editor widget.', 'wc-category-video-slider' ) ),
					'hook_term_desc'    => array( 'checkbox', __( 'Fallback: term_description filter', 'wc-category-video-slider' ), __( 'Only for custom themes that print the description with term_description() directly. Leave off unless the slider does not appear.', 'wc-category-video-slider' ) ),
					'assets_archives'   => array( 'checkbox', __( 'Load slider files on all shop/category pages', 'wc-category-video-slider' ), __( 'Needed by AJAX shop filters / pagination (WoodMart AJAX shop, Elementor loop AJAX). About 6 KB gzip.', 'wc-category-video-slider' ) ),
					'compat_optimizers' => array( 'checkbox', __( 'Optimization plugin compatibility', 'wc-category-video-slider' ), __( 'Excludes the tiny slider script from "Delay JavaScript" and protects popup styles from "Remove unused CSS" in WP Rocket and Perfmatters.', 'wc-category-video-slider' ) ),
				),
			),
			'advanced' => array(
				'title'  => __( 'Advanced', 'wc-category-video-slider' ),
				'fields' => array(
					'custom_css' => array( 'textarea', __( 'Custom CSS', 'wc-category-video-slider' ), __( 'Root class: .rcvs – items: .rcvs__slide, .rcvs__media, .rcvs__title, .rcvs__play, .rcvs__arrow, .rcvs__dot, .rcvs__main, .rcvs__stage', 'wc-category-video-slider' ) ),
				),
			),
		);
	}

	/**
	 * Layout options.
	 *
	 * @return array
	 */
	public static function layout_options() {
		return array(
			'slider'   => __( 'Slider', 'wc-category-video-slider' ),
			'featured' => __( 'Main video + gallery below', 'wc-category-video-slider' ),
			'grid'     => __( 'Grid (no slider)', 'wc-category-video-slider' ),
		);
	}

	/**
	 * Play icon presets.
	 *
	 * @return array
	 */
	public static function icon_options() {
		return array(
			'triangle'         => __( 'Solid triangle', 'wc-category-video-slider' ),
			'triangle-rounded' => __( 'Rounded triangle', 'wc-category-video-slider' ),
			'triangle-outline' => __( 'Outline triangle', 'wc-category-video-slider' ),
			'circle'           => __( 'Play in circle', 'wc-category-video-slider' ),
			'rect'             => __( 'Rounded rectangle', 'wc-category-video-slider' ),
			'chevron'          => __( 'Thin arrow', 'wc-category-video-slider' ),
			'custom'           => __( 'Custom image', 'wc-category-video-slider' ),
		);
	}

	/**
	 * SVG markup of icon presets (static, safe).
	 *
	 * @param string $key Preset.
	 * @return string
	 */
	public static function icon_svg( $key ) {
		$paths = array(
			'triangle'         => '<path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l10.79-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z"/>',
			'triangle-rounded' => '<path d="M7.5 6.3c0-1.5 1.6-2.4 2.9-1.6l9 5.7c1.2.8 1.2 2.5 0 3.2l-9 5.7c-1.3.8-2.9-.1-2.9-1.6z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
			'triangle-outline' => '<path d="M8 5.5v13l10.5-6.5z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>',
			'circle'           => '<circle cx="12" cy="12" r="10.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M10 8.3v7.4a.6.6 0 0 0 .9.5l5.8-3.7a.6.6 0 0 0 0-1l-5.8-3.7a.6.6 0 0 0-.9.5z"/>',
			'rect'             => '<path fill-rule="evenodd" d="M6 4.5h12a4.5 4.5 0 0 1 4.5 4.5v6a4.5 4.5 0 0 1-4.5 4.5H6A4.5 4.5 0 0 1 1.5 15V9A4.5 4.5 0 0 1 6 4.5zm4 4.2v6.6l5.6-3.3z"/>',
			'chevron'          => '<path d="M9 5.5 16.5 12 9 18.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
		);
		$path  = isset( $paths[ $key ] ) ? $paths[ $key ] : $paths['triangle'];
		return '<svg class="rcvs__play-svg rcvs__play-svg--' . esc_attr( isset( $paths[ $key ] ) ? $key : 'triangle' ) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}

	/**
	 * Logical margin value for an alignment.
	 *
	 * @param string $align start|center|end.
	 * @param string $side  start|end.
	 * @return string
	 */
	public static function align_margin( $align, $side ) {
		if ( 'center' === $align ) {
			return 'auto';
		}
		if ( 'end' === $align ) {
			return 'start' === $side ? 'auto' : '0';
		}
		return 'start' === $side ? '0' : 'auto';
	}

	/**
	 * Numeric value of a "w/h" ratio.
	 *
	 * @param string $ratio Ratio.
	 * @return float
	 */
	public static function ratio_number( $ratio ) {
		if ( preg_match( '#^(\d+(?:\.\d+)?)/(\d+(?:\.\d+)?)$#', (string) $ratio, $m ) && (float) $m[2] > 0 ) {
			return round( (float) $m[1] / (float) $m[2], 4 );
		}
		return round( 16 / 9, 4 );
	}

	/**
	 * Font weights.
	 *
	 * @return array
	 */
	private static function weights() {
		$out = array();
		foreach ( array( 300, 400, 500, 600, 700, 800, 900 ) as $w ) {
			$out[ (string) $w ] = (string) $w;
		}
		return $out;
	}

	/**
	 * Alignments.
	 *
	 * @return array
	 */
	public static function aligns() {
		return array(
			'start'  => __( 'Start', 'wc-category-video-slider' ),
			'center' => __( 'Center', 'wc-category-video-slider' ),
			'end'    => __( 'End', 'wc-category-video-slider' ),
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
					case 'icon':
						$options     = isset( $field[2] ) ? $field[2] : array();
						$value       = is_string( $value ) ? $value : '';
						$out[ $key ] = array_key_exists( $value, $options ) ? $value : $defaults[ $key ];
						break;

					case 'image':
						$id          = absint( $value );
						$out[ $key ] = ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
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
		$ratio = static function ( $value ) {
			return preg_match( '#^\d+/\d+$#', (string) $value ) ? $value : '16/9';
		};
		$num   = static function ( $value ) {
			return (float) $value;
		};

		$align_map = array(
			'start'  => 'start',
			'center' => 'center',
			'end'    => 'end',
		);

		$vars = array(
			'--rcvs-pv-d'            => $num( $s['per_view_desktop'] ),
			'--rcvs-pv-t'            => $num( $s['per_view_tablet'] ),
			'--rcvs-pv-m'            => $num( $s['per_view_mobile'] ),
			'--rcvs-gap'             => absint( $s['gap'] ) . 'px',
			'--rcvs-ratio'           => $ratio( $s['aspect_ratio'] ),
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
			'--rcvs-main-ratio'      => 'auto' === $s['featured_ratio'] ? '16/9' : $ratio( $s['featured_ratio'] ),
			'--rcvs-main-max-h'      => absint( $s['featured_max_height'] ) > 0 ? absint( $s['featured_max_height'] ) . 'vh' : '100000px',
			'--rcvs-main-ms'         => self::align_margin( $s['featured_align'], 'start' ),
			'--rcvs-main-me'         => self::align_margin( $s['featured_align'], 'end' ),
			'--rcvs-max-w'           => absint( $s['wrapper_max_width'] ) > 0 ? absint( $s['wrapper_max_width'] ) . 'px' : '100%',
			'--rcvs-ms'              => self::align_margin( $s['wrapper_align'], 'start' ),
			'--rcvs-me'              => self::align_margin( $s['wrapper_align'], 'end' ),
			'--rcvs-main-max'        => absint( $s['featured_max_width'] ) > 0 ? absint( $s['featured_max_width'] ) . 'px' : '100%',
			'--rcvs-main-spacing'    => absint( $s['featured_spacing'] ) . 'px',
			'--rcvs-mt-size'         => absint( $s['featured_title_size'] ) . 'px',
			'--rcvs-mt-weight'       => absint( $s['featured_title_weight'] ),
			'--rcvs-mt-color'        => $color( $s['featured_title_color'], 'inherit' ),
			'--rcvs-active'          => $color( $s['featured_active_color'], '#2271b1' ),
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
			'--rcvs-play-scale'      => min( 100, max( 10, absint( $s['play_icon_scale'] ) ) ) . '%',
			'--rcvs-play-offset'     => absint( $s['play_offset'] ) . 'px',
			'--rcvs-play-color'      => $color( $s['play_color'], '#fff' ),
			'--rcvs-play-hover'      => $color( $s['play_hover_color'], $color( $s['play_color'], '#fff' ) ),
			'--rcvs-play-bg'         => $color( $s['play_bg'], 'rgba(0,0,0,.55)' ),
			'--rcvs-play-hover-bg'   => $color( $s['play_hover_bg'], 'rgba(0,0,0,.8)' ),
			'--rcvs-play-border'     => absint( $s['play_border_width'] ) . 'px solid ' . $color( $s['play_border_color'], 'transparent' ),
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
		$css .= '.rcvs.rcvs--layout-featured{--rcvs-pv-d:' . $num( $s['featured_pv_desktop'] ) . ';--rcvs-pv-t:' . $num( $s['featured_pv_tablet'] ) . ';--rcvs-pv-m:' . $num( $s['featured_pv_mobile'] ) . ';}';
		$css .= '.rcvs.rcvs--layout-grid{--rcvs-pv-d:' . absint( $s['grid_cols_desktop'] ) . ';--rcvs-pv-t:' . absint( $s['grid_cols_tablet'] ) . ';--rcvs-pv-m:' . absint( $s['grid_cols_mobile'] ) . ';}';
		$css .= '@media (max-width:' . $bp_tablet . 'px){.rcvs{--rcvs-per-view:var(--rcvs-pv-t);}}';
		$css .= '@media (max-width:' . $bp_mobile . 'px){.rcvs{--rcvs-per-view:var(--rcvs-pv-m);}}';
		$css .= '.rcvs-catimg{--rcvs-img-max:' . ( absint( $s['img_max_width'] ) > 0 ? absint( $s['img_max_width'] ) . 'px' : '100%' ) . ';--rcvs-img-radius:' . absint( $s['img_radius'] ) . 'px;--rcvs-img-spacing:' . absint( $s['img_spacing'] ) . 'px;--rcvs-img-ms:' . self::align_margin( $s['img_align'], 'start' ) . ';--rcvs-img-me:' . self::align_margin( $s['img_align'], 'end' ) . ';--rcvs-img-shadow:' . ( $s['img_shadow'] ? '0 6px 20px rgba(0,0,0,.12)' : 'none' ) . ';}';
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
						<?php if ( ! empty( $section['intro'] ) ) : ?>
							<p class="rcvs-tab-intro"><?php echo esc_html( $section['intro'] ); ?></p>
						<?php endif; ?>
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

		if ( in_array( $type, array( 'select', 'icon' ), true ) ) {
			$help = ( isset( $field[3] ) && is_string( $field[3] ) ) ? $field[3] : '';
		} elseif ( isset( $field[2] ) && is_string( $field[2] ) ) {
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

					case 'icon':
						echo '<div class="rcvs-icon-picker" role="radiogroup">';
						foreach ( $field[2] as $opt_value => $opt_label ) {
							$preview = 'custom' === $opt_value ? '<span class="dashicons dashicons-format-image"></span>' : self::icon_svg( $opt_value );
							printf(
								'<label class="rcvs-icon-option" title="%3$s"><input type="radio" name="%1$s" value="%2$s" %4$s><span class="rcvs-icon-option__box">%5$s</span><span class="rcvs-icon-option__label">%3$s</span></label>',
								esc_attr( $name ),
								esc_attr( $opt_value ),
								esc_attr( $opt_label ),
								checked( (string) $value, (string) $opt_value, false ),
								$preview // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG / dashicon.
							);
						}
						echo '</div>';
						break;

					case 'image':
						$img = $value ? wp_get_attachment_image( (int) $value, 'thumbnail', false, array( 'alt' => '' ) ) : '';
						printf(
							'<div class="rcvs-image-field"><input type="hidden" id="%1$s" name="%2$s" value="%3$s"><span class="rcvs-image-field__preview">%4$s</span> <button type="button" class="button rcvs-image-field__pick">%5$s</button> <button type="button" class="button-link button-link-delete rcvs-image-field__remove"%6$s>%7$s</button></div>',
							esc_attr( $id ),
							esc_attr( $name ),
							esc_attr( $value ? (string) $value : '' ),
							$img, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup.
							esc_html__( 'Choose image', 'wc-category-video-slider' ),
							$img ? '' : ' hidden',
							esc_html__( 'Remove', 'wc-category-video-slider' )
						);
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
