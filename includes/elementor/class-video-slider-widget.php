<?php
/**
 * Elementor widget: Category Video Slider.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use RCVS\Renderer;
use RCVS\Video_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Widget class.
 */
class Video_Slider_Widget extends Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'rcvs-video-slider';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Category Video Slider', 'wc-category-video-slider' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-video';
	}

	/**
	 * Categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements-archive', 'woocommerce-elements', 'general' );
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'video', 'slider', 'category', 'woocommerce', 'archive', 'gallery', 'ویدئو', 'اسلایدر' );
	}

	/**
	 * Style deps.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'rcvs-frontend' );
	}

	/**
	 * Script deps.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'rcvs-frontend' );
	}

	/**
	 * Optimized DOM (no inner wrapper).
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Output depends on the current category → never cache the element output.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Category options.
	 *
	 * @return array
	 */
	private function term_options() {
		$terms = get_terms(
			array(
				'taxonomy'   => Video_Helper::TAXONOMY,
				'hide_empty' => false,
				'number'     => 1000,
			)
		);
		$out   = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$count                  = count( Video_Helper::get_raw_items( $term->term_id ) );
				$out[ $term->term_id ] = $term->name . ( $count ? ' (' . $count . ')' : '' );
			}
		}
		return $out;
	}

	/**
	 * Yes/No/Default select options.
	 *
	 * @return array
	 */
	private function tri_options() {
		return array(
			''    => esc_html__( 'Global setting', 'wc-category-video-slider' ),
			'yes' => esc_html__( 'Yes', 'wc-category-video-slider' ),
			'no'  => esc_html__( 'No', 'wc-category-video-slider' ),
		);
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->content_controls();
		$this->style_layout_controls();
		$this->style_item_controls();
		$this->style_heading_controls();
		$this->style_title_controls();
		$this->style_play_controls();
		$this->style_nav_controls();
	}

	/**
	 * Content tab.
	 */
	private function content_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => esc_html__( 'Videos', 'wc-category-video-slider' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Category', 'wc-category-video-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'current',
				'options' => array(
					'current' => esc_html__( 'Current category (archive template)', 'wc-category-video-slider' ),
					'custom'  => esc_html__( 'Choose a category', 'wc-category-video-slider' ),
				),
			)
		);

		$this->add_control(
			'term_id',
			array(
				'label'     => esc_html__( 'Product category', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SELECT2,
				'options'   => $this->term_options(),
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$this->add_control(
			'source_note',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Videos are managed in Products → Categories → Edit category. If you place this widget, consider disabling automatic insertion in WooCommerce → Category Videos to avoid showing the slider twice.', 'wc-category-video-slider' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->add_control(
			'show_heading',
			array(
				'label'        => esc_html__( 'Show heading', 'wc-category-video-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Heading', 'wc-category-video-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'Empty = category / global heading', 'wc-category-video-slider' ),
				'description' => esc_html__( 'Use {category} for the category name.', 'wc-category-video-slider' ),
				'condition'   => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_tag',
			array(
				'label'     => esc_html__( 'Heading HTML tag', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''    => esc_html__( 'Global setting', 'wc-category-video-slider' ),
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
					'p'   => 'p',
				),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'play_mode',
			array(
				'label'     => esc_html__( 'Play mode', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''         => esc_html__( 'Global setting', 'wc-category-video-slider' ),
					'lightbox' => esc_html__( 'Popup (lightbox)', 'wc-category-video-slider' ),
					'inline'   => esc_html__( 'Inside the slide', 'wc-category-video-slider' ),
				),
				'separator' => 'before',
			)
		);

		$tri = array(
			'autoplay'    => esc_html__( 'Autoplay', 'wc-category-video-slider' ),
			'loop'        => esc_html__( 'Loop', 'wc-category-video-slider' ),
			'show_arrows' => esc_html__( 'Arrows', 'wc-category-video-slider' ),
			'show_dots'   => esc_html__( 'Dots', 'wc-category-video-slider' ),
			'show_titles' => esc_html__( 'Video titles', 'wc-category-video-slider' ),
			'schema'      => esc_html__( 'VideoObject schema', 'wc-category-video-slider' ),
		);
		foreach ( $tri as $key => $label ) {
			$this->add_control(
				$key,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SELECT,
					'default' => '',
					'options' => $this->tri_options(),
				)
			);
		}

		$this->add_control(
			'autoplay_delay',
			array(
				'label'       => esc_html__( 'Autoplay delay (ms)', 'wc-category-video-slider' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1000,
				'max'         => 30000,
				'step'        => 100,
				'placeholder' => esc_html__( 'Global', 'wc-category-video-slider' ),
				'condition'   => array( 'autoplay!' => 'no' ),
			)
		);

		$sizes = array( '' => esc_html__( 'Global setting', 'wc-category-video-slider' ) );
		foreach ( array_merge( get_intermediate_image_sizes(), array( 'full' ) ) as $size ) {
			$sizes[ $size ] = $size;
		}
		$this->add_control(
			'image_size',
			array(
				'label'   => esc_html__( 'Cover image size', 'wc-category-video-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $sizes,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: layout.
	 */
	private function style_layout_controls() {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => esc_html__( 'Slider layout', 'wc-category-video-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'per_view',
			array(
				'label'       => esc_html__( 'Slides per view', 'wc-category-video-slider' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 8,
				'step'        => 0.1,
				'description' => esc_html__( 'Decimals allowed (e.g. 2.5).', 'wc-category-video-slider' ),
				'selectors'   => array( '{{WRAPPER}} .rcvs' => '--rcvs-per-view: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => esc_html__( 'Gap', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'ratio',
			array(
				'label'     => esc_html__( 'Aspect ratio', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''     => esc_html__( 'Global setting', 'wc-category-video-slider' ),
					'16/9' => '16:9',
					'9/16' => '9:16',
					'4/3'  => '4:3',
					'3/4'  => '3:4',
					'1/1'  => '1:1',
					'21/9' => '21:9',
				),
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-ratio: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'media_height',
			array(
				'label'       => esc_html__( 'Fixed video height', 'wc-category-video-slider' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh', 'em' ),
				'range'       => array( 'px' => array( 'min' => 80, 'max' => 1000 ) ),
				'description' => esc_html__( 'Overrides the aspect ratio.', 'wc-category-video-slider' ),
				'selectors'   => array( '{{WRAPPER}} .rcvs' => '--rcvs-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'wrapper_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
				'separator'  => 'before',
			)
		);

		$this->add_responsive_control(
			'wrapper_margin',
			array(
				'label'      => esc_html__( 'Margin', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'wrapper_bg',
			array(
				'label'     => esc_html__( 'Background', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-bg: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'wrapper_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'font_family',
			array(
				'label'     => esc_html__( 'Font family', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::FONT,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-font: "{{VALUE}}";' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: video box.
	 */
	private function style_item_controls() {
		$this->start_controls_section(
			'section_style_item',
			array(
				'label' => esc_html__( 'Video box', 'wc-category-video-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'item_radius',
			array(
				'label'      => esc_html__( 'Border radius', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .rcvs button.rcvs__media, {{WRAPPER}} .rcvs .rcvs__player' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'item_border',
				'selector' => '{{WRAPPER}} .rcvs button.rcvs__media',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'item_shadow',
				'selector' => '{{WRAPPER}} .rcvs button.rcvs__media',
			)
		);

		$this->add_control(
			'overlay_color',
			array(
				'label'     => esc_html__( 'Overlay color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-overlay: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'overlay_opacity',
			array(
				'label'     => esc_html__( 'Overlay opacity', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					),
				),
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-overlay-opacity: {{SIZE}};' ),
			)
		);

		$this->add_control(
			'overlay_opacity_hover',
			array(
				'label'     => esc_html__( 'Overlay opacity (hover)', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 1,
						'step' => 0.05,
					),
				),
				'selectors' => array( '{{WRAPPER}} .rcvs .rcvs__media:hover .rcvs__overlay' => 'opacity: {{SIZE}};' ),
			)
		);

		$this->add_control(
			'disable_zoom',
			array(
				'label'        => esc_html__( 'Disable cover zoom on hover', 'wc-category-video-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'selectors'    => array( '{{WRAPPER}} .rcvs .rcvs__media:hover .rcvs__cover' => 'transform: none;' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: heading.
	 */
	private function style_heading_controls() {
		$this->start_controls_section(
			'section_style_heading',
			array(
				'label'     => esc_html__( 'Heading', 'wc-category-video-slider' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typography',
				'selector' => '{{WRAPPER}} .rcvs .rcvs__heading',
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-h-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'heading_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-h-align: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'heading_spacing',
			array(
				'label'      => esc_html__( 'Spacing', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-h-spacing: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: titles.
	 */
	private function style_title_controls() {
		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => esc_html__( 'Video titles', 'wc-category-video-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .rcvs .rcvs__title',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => esc_html__( 'Color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-t-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'title_bg',
			array(
				'label'     => esc_html__( 'Background', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-t-bg: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'title_align',
			array(
				'label'     => esc_html__( 'Alignment', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'wc-category-video-slider' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-t-align: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'title_padding',
			array(
				'label'      => esc_html__( 'Padding', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-t-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'title_lines',
			array(
				'label'     => esc_html__( 'Maximum lines', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 6,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-t-lines: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: play button.
	 */
	private function style_play_controls() {
		$this->start_controls_section(
			'section_style_play',
			array(
				'label' => esc_html__( 'Play button', 'wc-category-video-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'play_size',
			array(
				'label'      => esc_html__( 'Size', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 160 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-play-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'play_color',
			array(
				'label'     => esc_html__( 'Icon color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-play-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'play_bg',
			array(
				'label'     => esc_html__( 'Background', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-play-bg: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'play_hover_bg',
			array(
				'label'     => esc_html__( 'Background (hover)', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-play-hover-bg: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'play_border',
				'selector' => '{{WRAPPER}} .rcvs .rcvs__play',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Style: arrows & dots.
	 */
	private function style_nav_controls() {
		$this->start_controls_section(
			'section_style_nav',
			array(
				'label' => esc_html__( 'Arrows & dots', 'wc-category-video-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'      => esc_html__( 'Arrow size', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-arrow-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'arrow_offset',
			array(
				'label'      => esc_html__( 'Arrow horizontal offset', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => -60, 'max' => 60 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .rcvs button.rcvs__arrow--prev' => 'inset-inline-start: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .rcvs button.rcvs__arrow--next' => 'inset-inline-end: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_radius',
			array(
				'label'      => esc_html__( 'Arrow border radius', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-arrow-radius: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => esc_html__( 'Arrow color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-arrow-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => esc_html__( 'Arrow background', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-arrow-bg: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'dot_size',
			array(
				'label'      => esc_html__( 'Dot size', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 4, 'max' => 30 ) ),
				'separator'  => 'before',
				'selectors'  => array( '{{WRAPPER}} .rcvs' => '--rcvs-dot-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => esc_html__( 'Dot color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-dot-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => esc_html__( 'Active dot color', 'wc-category-video-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .rcvs' => '--rcvs-dot-active: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'dots_spacing',
			array(
				'label'      => esc_html__( 'Dots top spacing', 'wc-category-video-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .rcvs .rcvs__dots' => 'margin-top: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Whether we are inside the Elementor editor / preview.
	 *
	 * @return bool
	 */
	private function is_editor() {
		$plugin = \Elementor\Plugin::$instance;
		return ( $plugin->editor && $plugin->editor->is_edit_mode() ) || ( $plugin->preview && $plugin->preview->is_preview_mode() );
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s       = $this->get_settings_for_display();
		$term_id = 0;

		if ( 'custom' === $s['source'] ) {
			$term_id = absint( $s['term_id'] );
		} else {
			$queried = get_queried_object();
			if ( $queried instanceof \WP_Term && Video_Helper::TAXONOMY === $queried->taxonomy ) {
				$term_id = (int) $queried->term_id;
			}
		}

		$editor = $this->is_editor();
		if ( ! $term_id && $editor ) {
			$term_id = Video_Helper::get_preview_term_id();
		}

		$html = $term_id ? Renderer::render(
			$term_id,
			array(
				'heading'        => isset( $s['heading'] ) && '' !== $s['heading'] ? $s['heading'] : null,
				'show_heading'   => 'yes' === $s['show_heading'],
				'heading_tag'    => $s['heading_tag'],
				'play_mode'      => $s['play_mode'],
				'autoplay'       => $s['autoplay'],
				'autoplay_delay' => isset( $s['autoplay_delay'] ) ? absint( $s['autoplay_delay'] ) : 0,
				'loop'           => $s['loop'],
				'show_arrows'    => $s['show_arrows'],
				'show_dots'      => $s['show_dots'],
				'show_titles'    => $s['show_titles'],
				'schema'         => $editor ? 'no' : $s['schema'],
				'image_size'     => $s['image_size'],
				'context'        => 'elementor',
			)
		) : '';

		if ( '' === $html ) {
			if ( $editor ) {
				echo '<div class="elementor-alert elementor-alert-info">' . esc_html__( 'No videos found. Add videos to a product category (Products → Categories → Edit) to preview this slider.', 'wc-category-video-slider' ) . '</div>';
			}
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in renderer.
	}
}
