<?php
/**
 * Markup renderer (slider / main video + gallery / grid).
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the video gallery HTML.
 */
final class Renderer {

	/**
	 * Term IDs already rendered on this request (prevents duplicates from several hooks).
	 *
	 * @var array
	 */
	private static $rendered = array();

	/**
	 * Instance counter (unique IDs).
	 *
	 * @var int
	 */
	private static $counter = 0;

	/**
	 * Whether a term was rendered in this request.
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public static function was_rendered( $term_id ) {
		return isset( self::$rendered[ (int) $term_id ] );
	}

	/**
	 * Convert yes/no/'' style values into bool using a fallback.
	 *
	 * @param mixed $value    Value.
	 * @param bool  $fallback Fallback.
	 * @return bool
	 */
	public static function to_bool( $value, $fallback ) {
		if ( null === $value || '' === $value ) {
			return (bool) $fallback;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on', 'show' ), true );
	}

	/**
	 * Default render args.
	 *
	 * @return array
	 */
	public static function default_args() {
		return array(
			'layout'         => '',
			'heading'        => null,
			'show_heading'   => true,
			'heading_tag'    => '',
			'play_mode'      => '',
			'autoplay'       => null,
			'autoplay_delay' => 0,
			'loop'           => null,
			'show_arrows'    => null,
			'show_dots'      => null,
			'show_titles'    => null,
			'show_play'      => null,
			'play_icon'      => '',
			'play_icon_html' => '',
			'play_shape'     => '',
			'play_position'  => '',
			'schema'         => null,
			'image_size'     => '',
			'per_view'       => '',
			'per_view_t'     => '',
			'per_view_m'     => '',
			'ratio'          => '',
			'max_width'      => '',
			'align'          => '',
			'show_image'     => true,
			'context'        => 'auto',
			'class'          => '',
		);
	}

	/**
	 * Category block = optional category image + video gallery (in the configured order).
	 *
	 * @param int|\WP_Term $term Term or ID.
	 * @param array        $args Render args.
	 * @return string
	 */
	public static function render_category( $term, $args = array() ) {
		$term = get_term( $term, Video_Helper::TAXONOMY );
		if ( ! $term instanceof \WP_Term ) {
			return '';
		}
		$args   = wp_parse_args( $args, self::default_args() );
		$videos = self::render( $term, $args );
		$image  = self::to_bool( $args['show_image'], true ) ? self::category_image( $term, '' !== $videos ) : '';

		if ( '' === $image ) {
			return $videos;
		}
		self::$rendered[ $term->term_id ] = true;

		$pos = (string) get_term_meta( $term->term_id, Video_Helper::META_IMG_POS, true );
		if ( ! in_array( $pos, array( 'before', 'after' ), true ) ) {
			$pos = 'after' === Settings::get( 'img_position' ) ? 'after' : 'before';
		}
		return 'after' === $pos ? $videos . $image : $image . $videos;
	}

	/**
	 * Category image markup.
	 *
	 * @param \WP_Term $term       Term.
	 * @param bool     $has_videos Whether videos are printed next to it.
	 * @return string
	 */
	public static function category_image( $term, $has_videos = true ) {
		$id = absint( get_term_meta( $term->term_id, Video_Helper::META_IMG, true ) );
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return '';
		}
		$pos  = (string) get_term_meta( $term->term_id, Video_Helper::META_IMG_POS, true );
		$pos  = in_array( $pos, array( 'before', 'after' ), true ) ? $pos : ( 'after' === Settings::get( 'img_position' ) ? 'after' : 'before' );
		$alt  = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$img  = wp_get_attachment_image(
			$id,
			Settings::get( 'img_size' ),
			false,
			array(
				'class'         => 'rcvs-catimg__img',
				'alt'           => '' !== $alt ? $alt : $term->name,
				'loading'       => 'before' === $pos ? 'eager' : 'lazy',
				'fetchpriority' => 'before' === $pos ? 'high' : 'auto',
				'decoding'      => 'async',
			)
		);
		if ( ! $img ) {
			return '';
		}
		$link = (string) get_term_meta( $term->term_id, Video_Helper::META_IMG_URL, true );
		if ( $link ) {
			$img = '<a class="rcvs-catimg__link" href="' . esc_url( $link ) . '">' . $img . '</a>';
		}
		Frontend::enqueue_assets();

		$classes = 'rcvs-catimg rcvs-catimg--' . $pos . ( $has_videos ? '' : ' rcvs-catimg--alone' );
		$html    = '<figure class="' . esc_attr( $classes ) . '">' . $img . '</figure>';

		/**
		 * Filter the category image markup.
		 *
		 * @param string   $html Markup.
		 * @param \WP_Term $term Term.
		 */
		return (string) apply_filters( 'rcvs_category_image_html', $html, $term );
	}

	/**
	 * Render the gallery of a product category.
	 *
	 * @param int|\WP_Term $term Term or ID.
	 * @param array        $args Overrides.
	 * @return string HTML (escaped).
	 */
	public static function render( $term, $args = array() ) {
		$term = get_term( $term, Video_Helper::TAXONOMY );
		if ( ! $term instanceof \WP_Term || ! Video_Helper::is_enabled( $term->term_id ) ) {
			return '';
		}

		$html = self::render_source(
			array(
				'type'        => 'term',
				'id'          => (int) $term->term_id,
				'name'        => $term->name,
				'description' => (string) $term->description,
				'heading'     => (string) get_term_meta( $term->term_id, Video_Helper::META_HEADING, true ),
				'layout'      => (string) get_term_meta( $term->term_id, Video_Helper::META_LAYOUT, true ),
				'play'        => (string) get_term_meta( $term->term_id, Video_Helper::META_PLAY, true ),
				'max_width'   => absint( get_term_meta( $term->term_id, Video_Helper::META_WIDTH, true ) ),
				'raw'         => Video_Helper::get_raw_items( $term->term_id ),
				'object'      => $term,
			),
			$args
		);

		if ( '' !== $html ) {
			self::$rendered[ $term->term_id ] = true;
		}
		return $html;
	}

	/**
	 * Render a standalone gallery.
	 *
	 * @param int   $post_id Gallery ID.
	 * @param array $args    Overrides.
	 * @return string
	 */
	public static function render_gallery( $post_id, $args = array() ) {
		$post = get_post( absint( $post_id ) );
		if ( ! $post || Video_Helper::POST_TYPE !== $post->post_type ) {
			return '';
		}
		if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post->ID ) ) {
			return '';
		}

		return self::render_source(
			array(
				'type'        => 'gallery',
				'id'          => (int) $post->ID,
				'name'        => get_the_title( $post ),
				'description' => (string) $post->post_excerpt,
				'heading'     => (string) get_post_meta( $post->ID, Video_Helper::META_HEADING, true ),
				'layout'      => (string) get_post_meta( $post->ID, Video_Helper::META_LAYOUT, true ),
				'play'        => (string) get_post_meta( $post->ID, Video_Helper::META_PLAY, true ),
				'max_width'   => absint( get_post_meta( $post->ID, Video_Helper::META_WIDTH, true ) ),
				'raw'         => Video_Helper::get_gallery_raw_items( $post->ID ),
				'object'      => $post,
			),
			wp_parse_args( $args, array( 'context' => 'gallery' ) )
		);
	}

	/**
	 * Render from a normalized source.
	 *
	 * @param array $src  Source data.
	 * @param array $args Overrides.
	 * @return string
	 */
	public static function render_source( $src, $args = array() ) {
		$s    = Settings::get_all();
		$args = wp_parse_args( $args, self::default_args() );

		$image_size = $args['image_size'] ? sanitize_key( $args['image_size'] ) : $s['image_size'];
		$items      = Video_Helper::prepare_all( $src['raw'], $image_size );

		if ( 'term' === $src['type'] ) {
			/** This filter is documented for backward compatibility. */
			$items = (array) apply_filters( 'rcvs_category_videos', $items, (int) $src['id'] );
		}

		/**
		 * Filter prepared videos of any source.
		 *
		 * @param array $items Items.
		 * @param array $src   Source (type, id, name…).
		 */
		$items = array_values( (array) apply_filters( 'rcvs_items', $items, $src ) );
		if ( empty( $items ) ) {
			return '';
		}

		// Layout: arg → source setting → global.
		$layouts = Video_Helper::layouts();
		$layout  = in_array( $args['layout'], $layouts, true ) ? $args['layout'] : '';
		if ( '' === $layout ) {
			$layout = in_array( $src['layout'], $layouts, true ) ? $src['layout'] : $s['layout'];
		}

		// Play button visibility: arg → source → global.
		$show_play = self::to_bool( $args['show_play'], self::to_bool( $src['play'], $s['show_play'] ) );

		$heading_tag = $args['heading_tag'] ? $args['heading_tag'] : $s['heading_tag'];
		$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' ), true ) ? $heading_tag : 'h2';
		$play_mode   = $args['play_mode'] ? $args['play_mode'] : $s['play_mode'];
		$play_mode   = 'inline' === $play_mode ? 'inline' : 'lightbox';
		$autoplay    = self::to_bool( $args['autoplay'], $s['autoplay'] );
		$delay       = absint( $args['autoplay_delay'] ) ? absint( $args['autoplay_delay'] ) : absint( $s['autoplay_delay'] );
		$loop        = self::to_bool( $args['loop'], $s['loop'] );
		$show_arrows = 'grid' !== $layout && self::to_bool( $args['show_arrows'], $s['show_arrows'] );
		$show_dots   = 'grid' !== $layout && self::to_bool( $args['show_dots'], $s['show_dots'] );
		$show_titles = self::to_bool( $args['show_titles'], $s['show_titles'] );
		$schema      = self::to_bool( $args['schema'], $s['schema'] );

		// Heading: explicit arg → source → global default.
		$heading = $args['heading'];
		if ( null === $heading || '' === $heading ) {
			$heading = (string) $src['heading'];
		}
		if ( '' === $heading ) {
			$heading = (string) $s['default_heading'];
		}
		$heading = str_replace( '{category}', $src['name'], $heading );

		// Main video (featured layout).
		$main_index = 0;
		foreach ( $items as $i => $item ) {
			if ( ! empty( $item['is_main'] ) ) {
				$main_index = $i;
				break;
			}
		}
		$thumbs = array();
		foreach ( $items as $i => $item ) {
			if ( 'featured' === $layout && ! $s['featured_include_main'] && $i === $main_index ) {
				continue;
			}
			$thumbs[ $i ] = $item;
		}

		Frontend::enqueue_assets();
		++self::$counter;

		$thumb_action = ( 'featured' === $layout && 'main' === $s['featured_thumb_action'] ) ? 'main' : $play_mode;
		$options      = array(
			'autoplay'    => $autoplay && 'grid' !== $layout,
			'delay'       => max( 1000, $delay ),
			'loop'        => $loop,
			'playMode'    => $play_mode,
			'layout'      => $layout,
			'thumbAction' => $thumb_action,
			'mainRatio'   => ( 'auto' === $s['featured_ratio'] && ! $args['ratio'] ) ? 'auto' : 'fixed',
		);

		$shape_arg  = $args['play_shape'] ? $args['play_shape'] : $s['play_shape'];
		$pos_arg    = $args['play_position'] ? $args['play_position'] : $s['play_position'];
		$play_shape = in_array( $shape_arg, array( 'circle', 'rounded', 'square', 'none' ), true ) ? $shape_arg : 'circle';
		$play_pos   = in_array( $pos_arg, array( 'center', 'bottom-start', 'bottom-end', 'top-start', 'top-end' ), true ) ? $pos_arg : 'center';

		$classes = array(
			'rcvs',
			'rcvs--' . sanitize_html_class( $args['context'] ),
			'rcvs--layout-' . $layout,
			'rcvs--play-' . $play_mode,
			'rcvs--pshape-' . $play_shape,
			'rcvs--ppos-' . $play_pos,
		);
		if ( 'pulse' === $s['play_animation'] ) {
			$classes[] = 'rcvs--panim-pulse';
		}
		if ( ! $show_play ) {
			$classes[] = 'rcvs--no-play';
		}
		if ( ! $show_titles ) {
			$classes[] = 'rcvs--no-titles';
		}

		// Max width: arg → source → global.
		$max_width = absint( $args['max_width'] ) ? absint( $args['max_width'] ) : absint( isset( $src['max_width'] ) ? $src['max_width'] : 0 );
		if ( $max_width || absint( $s['wrapper_max_width'] ) ) {
			$classes[] = 'rcvs--boxed';
		}
		if ( $max_width ) {
			$args['_vars'][] = '--rcvs-max-w:' . $max_width . 'px';
		}
		if ( in_array( $args['align'], array( 'start', 'center', 'end' ), true ) ) {
			$args['_vars'][] = '--rcvs-ms:' . Settings::align_margin( $args['align'], 'start' );
			$args['_vars'][] = '--rcvs-me:' . Settings::align_margin( $args['align'], 'end' );
		}
		foreach ( preg_split( '/\s+/', (string) $args['class'] ) as $extra ) {
			if ( '' !== $extra ) {
				$classes[] = sanitize_html_class( $extra );
			}
		}

		$label = '' !== $heading
			? $heading
			/* translators: %s: category or gallery name */
			: sprintf( __( '%s videos', 'wc-category-video-slider' ), $src['name'] );

		$play_html  = $show_play ? self::play_icon_html( $s, $args ) : '';
		$arrow_icon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';
		$style      = self::inline_vars( $args );

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', array_unique( $classes ) ) ); ?>" id="rcvs-<?php echo esc_attr( self::$counter ); ?>" data-rcvs="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
			<?php if ( $args['show_heading'] && '' !== $heading ) : ?>
				<<?php echo tag_escape( $heading_tag ); ?> class="rcvs__heading"><?php echo esc_html( $heading ); ?></<?php echo tag_escape( $heading_tag ); ?>>
			<?php endif; ?>

			<?php
			if ( 'featured' === $layout ) :
				$main        = $items[ $main_index ];
				$stage_ratio = 'auto' === $options['mainRatio']
					? ( $main['ratio'] ? $main['ratio'] : '16/9' )
					: ( $args['ratio'] ? str_replace( array( ':', 'x' ), '/', $args['ratio'] ) : ( 'auto' === $s['featured_ratio'] ? '16/9' : $s['featured_ratio'] ) );
				if ( ! preg_match( '#^\d+/\d+$#', $stage_ratio ) ) {
					$stage_ratio = '16/9';
				}
				$stage_style = '--rcvs-stage-ratio:' . $stage_ratio . ';--rcvs-stage-n:' . Settings::ratio_number( $stage_ratio );
				?>
				<div class="rcvs__main" data-current="<?php echo esc_attr( $main_index ); ?>" style="<?php echo esc_attr( $stage_style ); ?>">
					<div class="rcvs__stage">
						<?php
						echo self::media_button( $main, $main_index, $play_html, Video_Helper::cover_html( $main, 'large', true ), 'rcvs__media--main' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
						?>
					</div>
					<?php if ( $s['featured_show_title'] ) : ?>
						<div class="rcvs__main-title"<?php echo '' === $main['title'] ? ' hidden' : ''; ?>><?php echo esc_html( $main['title'] ); ?></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $thumbs ) && ! ( 'featured' === $layout && 1 === count( $items ) ) ) : ?>
				<div class="rcvs__viewport">
					<?php if ( $show_arrows ) : ?>
						<button type="button" class="rcvs__arrow rcvs__arrow--prev" aria-label="<?php esc_attr_e( 'Previous videos', 'wc-category-video-slider' ); ?>"><?php echo $arrow_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
					<?php endif; ?>
					<div class="rcvs__track" role="list"<?php echo 'grid' !== $layout ? ' tabindex="0"' : ''; ?>>
						<?php foreach ( $thumbs as $index => $item ) : ?>
							<?php
							$current = ( 'featured' === $layout && $index === $main_index );
							?>
							<div class="rcvs__slide rcvs__slide--<?php echo esc_attr( $item['provider'] ); ?><?php echo $current ? ' is-current' : ''; ?>" role="listitem" data-index="<?php echo esc_attr( $index ); ?>">
								<figure class="rcvs__item">
									<?php echo self::media_button( $item, $index, $play_html, $item['cover_html'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside. ?>
									<?php if ( $show_titles && '' !== $item['title'] ) : ?>
										<figcaption class="rcvs__title"><?php echo esc_html( $item['title'] ); ?></figcaption>
									<?php endif; ?>
								</figure>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ( $show_arrows ) : ?>
						<button type="button" class="rcvs__arrow rcvs__arrow--next" aria-label="<?php esc_attr_e( 'Next videos', 'wc-category-video-slider' ); ?>"><?php echo $arrow_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
					<?php endif; ?>
				</div>
				<?php if ( $show_dots ) : ?>
					<div class="rcvs__dots"></div>
				<?php endif; ?>
			<?php endif; ?>
			<?php
			if ( $schema ) {
				echo self::schema( $src, $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
			}
			?>
		</section>
		<?php
		$html = (string) ob_get_clean();

		/**
		 * Filter the final HTML.
		 *
		 * @param string $html   HTML.
		 * @param mixed  $object WP_Term or WP_Post.
		 * @param array  $items  Prepared items.
		 * @param array  $args   Render args.
		 * @param array  $src    Source.
		 */
		return (string) apply_filters( 'rcvs_slider_html', $html, $src['object'], $items, $args, $src );
	}

	/**
	 * One cover/play button.
	 *
	 * @param array  $item       Prepared item.
	 * @param int    $index      Index.
	 * @param string $play_html  Play icon markup ('' = none).
	 * @param string $cover_html Cover markup.
	 * @param string $extra      Extra class.
	 * @return string
	 */
	private static function media_button( $item, $index, $play_html, $cover_html, $extra = '' ) {
		$title = '' !== $item['title'] ? $item['title'] : sprintf(
			/* translators: %d: video number */
			__( 'Video %d', 'wc-category-video-slider' ),
			$index + 1
		);
		$show  = '' !== $play_html && empty( $item['hide_play'] );

		$html  = sprintf(
			'<button type="button" class="rcvs__media%1$s%2$s" data-index="%3$d" data-rcvs-video="%4$s" data-rcvs-title="%5$s" data-rcvs-label="%6$s" aria-label="%7$s">',
			$extra ? ' ' . esc_attr( $extra ) : '',
			$show ? '' : ' rcvs__media--no-play',
			(int) $index,
			esc_attr( wp_json_encode( $item['player'] ) ),
			esc_attr( $title ),
			esc_attr( $item['title'] ),
			/* translators: %s: video title */
			esc_attr( sprintf( __( 'Play video: %s', 'wc-category-video-slider' ), $title ) )
		);
		$html .= $cover_html ? $cover_html : '<span class="rcvs__placeholder" aria-hidden="true"></span>';
		$html .= '<span class="rcvs__overlay" aria-hidden="true"></span>';
		if ( $show ) {
			$html .= '<span class="rcvs__play" aria-hidden="true">' . $play_html . '</span>';
		}
		$html .= '</button>';
		return $html;
	}

	/**
	 * Play icon markup.
	 *
	 * @param array $s    Settings.
	 * @param array $args Args.
	 * @return string
	 */
	public static function play_icon_html( $s, $args ) {
		if ( ! empty( $args['play_icon_html'] ) ) {
			return (string) $args['play_icon_html']; // Rendered by Elementor's Icons_Manager.
		}

		$icon = $args['play_icon'] ? $args['play_icon'] : $s['play_icon'];
		$html = '';

		if ( 'custom' === $icon && ! empty( $s['play_custom_id'] ) && wp_attachment_is_image( (int) $s['play_custom_id'] ) ) {
			$html = wp_get_attachment_image(
				(int) $s['play_custom_id'],
				'medium',
				false,
				array(
					'class'        => 'rcvs__play-img skip-lazy no-lazyload',
					'alt'          => '',
					'loading'      => 'eager',
					'decoding'     => 'async',
					'data-no-lazy' => '1',
					'data-skip-lazy' => '1',
				)
			);
		}
		if ( '' === $html ) {
			$html = Settings::icon_svg( 'custom' === $icon ? 'triangle' : $icon );
		}

		/**
		 * Filter the play icon markup.
		 *
		 * @param string $html Markup.
		 * @param string $icon Preset key.
		 */
		return (string) apply_filters( 'rcvs_play_icon_html', $html, $icon );
	}

	/**
	 * Inline CSS variables from args (shortcode overrides).
	 *
	 * @param array $args Args.
	 * @return string
	 */
	private static function inline_vars( $args ) {
		$vars = isset( $args['_vars'] ) ? (array) $args['_vars'] : array();
		$map  = array(
			'per_view'   => '--rcvs-pv-d',
			'per_view_t' => '--rcvs-pv-t',
			'per_view_m' => '--rcvs-pv-m',
		);
		foreach ( $map as $key => $var ) {
			if ( is_numeric( $args[ $key ] ) && (float) $args[ $key ] > 0 ) {
				$vars[] = $var . ':' . min( 10, (float) $args[ $key ] );
			}
		}
		if ( $args['ratio'] ) {
			$ratio = str_replace( array( ':', 'x' ), '/', (string) $args['ratio'] );
			if ( preg_match( '#^\d+/\d+$#', $ratio ) ) {
				$vars[] = '--rcvs-ratio:' . $ratio;
				$vars[] = '--rcvs-main-ratio:' . $ratio;
			}
		}
		return implode( ';', $vars );
	}

	/**
	 * VideoObject JSON-LD.
	 *
	 * @param array $src   Source.
	 * @param array $items Items.
	 * @return string
	 */
	private static function schema( $src, $items ) {
		$graph = array();
		$desc  = wp_trim_words( wp_strip_all_tags( (string) $src['description'] ), 30, '…' );

		foreach ( $items as $item ) {
			if ( empty( $item['cover_url'] ) ) {
				continue; // thumbnailUrl is required by Google.
			}
			$name   = '' !== $item['title'] ? $item['title'] : $src['name'];
			$object = array(
				'@type'        => 'VideoObject',
				'name'         => $name,
				'description'  => '' !== $desc ? $desc : $name,
				'thumbnailUrl' => $item['cover_url'],
			);
			if ( ! empty( $item['date'] ) ) {
				$object['uploadDate'] = mysql2date( 'c', $item['date'], false );
			}
			if ( $item['content_url'] ) {
				$object['contentUrl'] = $item['content_url'];
			}
			if ( $item['embed_url'] ) {
				$object['embedUrl'] = $item['embed_url'];
			}
			$graph[] = $object;
		}

		/**
		 * Filter the VideoObject graph.
		 *
		 * @param array $graph  Graph.
		 * @param mixed $object WP_Term or WP_Post.
		 */
		$graph = (array) apply_filters( 'rcvs_schema_graph', $graph, $src['object'] );
		if ( empty( $graph ) ) {
			return '';
		}

		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
		);

		return $json ? '<script type="application/ld+json">' . $json . '</script>' : '';
	}
}
