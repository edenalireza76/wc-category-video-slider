<?php
/**
 * Slider markup renderer.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the slider HTML.
 */
final class Renderer {

	/**
	 * Term IDs already rendered on this request.
	 *
	 * @var array
	 */
	private static $rendered = array();

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
	private static function to_bool( $value, $fallback ) {
		if ( null === $value || '' === $value ) {
			return (bool) $fallback;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Render the slider for a category.
	 *
	 * @param int|\WP_Term $term Term or ID.
	 * @param array        $args Overrides (see defaults below).
	 * @return string HTML (escaped).
	 */
	public static function render( $term, $args = array() ) {
		$term = get_term( $term, Video_Helper::TAXONOMY );
		if ( ! $term instanceof \WP_Term ) {
			return '';
		}

		$s    = Settings::get_all();
		$args = wp_parse_args(
			$args,
			array(
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
				'schema'         => null,
				'image_size'     => '',
				'context'        => 'auto',
				'class'          => '',
			)
		);

		if ( ! Video_Helper::is_enabled( $term->term_id ) ) {
			return '';
		}

		$image_size = $args['image_size'] ? $args['image_size'] : $s['image_size'];
		$items      = Video_Helper::get_items( $term->term_id, $image_size );
		if ( empty( $items ) ) {
			return '';
		}

		$heading_tag = $args['heading_tag'] ? $args['heading_tag'] : $s['heading_tag'];
		$heading_tag = in_array( $heading_tag, array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' ), true ) ? $heading_tag : 'h2';
		$play_mode   = $args['play_mode'] ? $args['play_mode'] : $s['play_mode'];
		$play_mode   = 'inline' === $play_mode ? 'inline' : 'lightbox';
		$autoplay    = self::to_bool( $args['autoplay'], $s['autoplay'] );
		$delay       = absint( $args['autoplay_delay'] ) ? absint( $args['autoplay_delay'] ) : absint( $s['autoplay_delay'] );
		$loop        = self::to_bool( $args['loop'], $s['loop'] );
		$show_arrows = self::to_bool( $args['show_arrows'], $s['show_arrows'] );
		$show_dots   = self::to_bool( $args['show_dots'], $s['show_dots'] );
		$show_titles = self::to_bool( $args['show_titles'], $s['show_titles'] );
		$schema      = self::to_bool( $args['schema'], $s['schema'] );

		// Heading: explicit arg → category setting → global default.
		$heading = $args['heading'];
		if ( null === $heading || '' === $heading ) {
			$heading = (string) get_term_meta( $term->term_id, Video_Helper::META_HEADING, true );
		}
		if ( '' === $heading ) {
			$heading = (string) $s['default_heading'];
		}
		$heading = str_replace( '{category}', $term->name, $heading );

		Frontend::enqueue_assets();
		self::$rendered[ $term->term_id ] = true;

		$options = array(
			'autoplay' => $autoplay,
			'delay'    => max( 1000, $delay ),
			'loop'     => $loop,
			'playMode' => $play_mode,
		);

		$classes = array(
			'rcvs',
			'rcvs--' . sanitize_html_class( $args['context'] ),
			'rcvs--play-' . $play_mode,
		);
		if ( ! $show_titles ) {
			$classes[] = 'rcvs--no-titles';
		}
		if ( $args['class'] ) {
			$classes[] = sanitize_html_class( $args['class'] );
		}

		$label = '' !== $heading
			? $heading
			/* translators: %s: category name */
			: sprintf( __( '%s videos', 'wc-category-video-slider' ), $term->name );

		$play_icon  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l10.79-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z"/></svg>';
		$arrow_icon = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>';

		ob_start();
		?>
		<section class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-rcvs="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" aria-label="<?php echo esc_attr( $label ); ?>">
			<?php if ( $args['show_heading'] && '' !== $heading ) : ?>
				<<?php echo tag_escape( $heading_tag ); ?> class="rcvs__heading"><?php echo esc_html( $heading ); ?></<?php echo tag_escape( $heading_tag ); ?>>
			<?php endif; ?>
			<div class="rcvs__viewport">
				<?php if ( $show_arrows ) : ?>
					<button type="button" class="rcvs__arrow rcvs__arrow--prev" aria-label="<?php esc_attr_e( 'Previous videos', 'wc-category-video-slider' ); ?>"><?php echo $arrow_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></button>
				<?php endif; ?>
				<div class="rcvs__track" role="list" tabindex="0">
					<?php foreach ( $items as $index => $item ) : ?>
						<?php
						$item_title = '' !== $item['title'] ? $item['title'] : sprintf(
							/* translators: %d: video number */
							__( 'Video %d', 'wc-category-video-slider' ),
							$index + 1
						);
						?>
						<div class="rcvs__slide rcvs__slide--<?php echo esc_attr( $item['provider'] ); ?>" role="listitem">
							<figure class="rcvs__item">
								<button type="button" class="rcvs__media" data-rcvs-video="<?php echo esc_attr( wp_json_encode( $item['player'] ) ); ?>" data-rcvs-title="<?php echo esc_attr( $item_title ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: video title */ __( 'Play video: %s', 'wc-category-video-slider' ), $item_title ) ); ?>">
									<?php
									if ( $item['cover_html'] ) {
										echo $item['cover_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image() or escaped sprintf().
									} else {
										echo '<span class="rcvs__placeholder" aria-hidden="true"></span>';
									}
									?>
									<span class="rcvs__overlay" aria-hidden="true"></span>
									<span class="rcvs__play" aria-hidden="true"><?php echo $play_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
								</button>
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
			<?php
			if ( $schema ) {
				echo self::schema( $term, $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
			}
			?>
		</section>
		<?php
		$html = (string) ob_get_clean();

		/**
		 * Filter the final slider HTML.
		 *
		 * @param string   $html  HTML.
		 * @param \WP_Term $term  Category.
		 * @param array    $items Prepared items.
		 * @param array    $args  Render args.
		 */
		return (string) apply_filters( 'rcvs_slider_html', $html, $term, $items, $args );
	}

	/**
	 * VideoObject JSON-LD.
	 *
	 * @param \WP_Term $term  Term.
	 * @param array    $items Items.
	 * @return string
	 */
	private static function schema( $term, $items ) {
		$graph = array();
		$desc  = wp_trim_words( wp_strip_all_tags( (string) $term->description ), 30, '…' );

		foreach ( $items as $item ) {
			if ( empty( $item['cover_url'] ) ) {
				continue; // thumbnailUrl is required by Google.
			}
			$name   = '' !== $item['title'] ? $item['title'] : $term->name;
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
		 * @param array    $graph Graph.
		 * @param \WP_Term $term  Term.
		 */
		$graph = (array) apply_filters( 'rcvs_schema_graph', $graph, $term );
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
