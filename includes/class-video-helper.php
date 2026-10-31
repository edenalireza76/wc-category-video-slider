<?php
/**
 * Video data helpers (storage, sanitizing, provider parsing).
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Static helpers.
 */
final class Video_Helper {

	const META_VIDEOS  = '_rcvs_videos';
	const META_ENABLED = '_rcvs_enabled';
	const META_HEADING = '_rcvs_heading';
	const META_LAYOUT  = '_rcvs_layout';
	const META_PLAY    = '_rcvs_play';
	const META_WIDTH   = '_rcvs_max_width';
	const META_IMG     = '_rcvs_img';
	const META_IMG_POS = '_rcvs_img_pos';
	const META_IMG_URL = '_rcvs_img_link';
	const TAXONOMY     = 'product_cat';
	const POST_TYPE    = 'rcvs_gallery';

	/**
	 * Valid layouts.
	 *
	 * @return array
	 */
	public static function layouts() {
		return array( 'slider', 'featured', 'grid' );
	}

	/**
	 * Allowed per-video orientation values.
	 *
	 * @return array
	 */
	public static function item_ratios() {
		return array(
			''     => __( 'Auto', 'wc-category-video-slider' ),
			'16/9' => __( 'Horizontal 16:9', 'wc-category-video-slider' ),
			'9/16' => __( 'Vertical 9:16', 'wc-category-video-slider' ),
			'1/1'  => __( 'Square 1:1', 'wc-category-video-slider' ),
			'4/3'  => '4:3',
			'4/5'  => '4:5',
		);
	}

	/**
	 * Raw saved items for a category.
	 *
	 * @param int $term_id Term ID.
	 * @return array
	 */
	public static function get_raw_items( $term_id ) {
		$items = get_term_meta( (int) $term_id, self::META_VIDEOS, true );
		return is_array( $items ) ? array_values( $items ) : array();
	}

	/**
	 * Raw saved items for a standalone gallery.
	 *
	 * @param int $post_id Gallery post ID.
	 * @return array
	 */
	public static function get_gallery_raw_items( $post_id ) {
		$items = get_post_meta( (int) $post_id, self::META_VIDEOS, true );
		return is_array( $items ) ? array_values( $items ) : array();
	}

	/**
	 * Whether the slider is enabled for a term (default: yes).
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public static function is_enabled( $term_id ) {
		return 'no' !== get_term_meta( (int) $term_id, self::META_ENABLED, true );
	}

	/**
	 * Whether a term has at least one displayable video.
	 *
	 * @param int $term_id Term ID.
	 * @return bool
	 */
	public static function has_videos( $term_id ) {
		return self::is_enabled( $term_id ) && ! empty( self::get_raw_items( $term_id ) );
	}

	/**
	 * Prepared, render-ready items for a category (kept for backward compatibility).
	 *
	 * @param int    $term_id    Term ID.
	 * @param string $image_size Cover image size.
	 * @return array
	 */
	public static function get_items( $term_id, $image_size = 'medium_large' ) {
		return self::prepare_all( self::get_raw_items( $term_id ), $image_size );
	}

	/**
	 * Prepare a list of raw items.
	 *
	 * @param array  $raw        Raw items.
	 * @param string $image_size Image size.
	 * @return array
	 */
	public static function prepare_all( $raw, $image_size = 'medium_large' ) {
		$out = array();
		foreach ( (array) $raw as $item ) {
			$prepared = self::prepare( $item, $image_size );
			if ( $prepared ) {
				$out[] = $prepared;
			}
		}
		return $out;
	}

	/**
	 * Sanitize the submitted repeater (keeps order, applies the "main video" key).
	 *
	 * @param array  $raw      Raw items keyed by repeater key.
	 * @param string $main_key Key of the item chosen as main video.
	 * @return array
	 */
	public static function sanitize_items( $raw, $main_key = '' ) {
		$items    = array();
		$has_main = false;
		foreach ( (array) $raw as $key => $item ) {
			$clean = self::sanitize_item( $item );
			if ( ! $clean ) {
				continue;
			}
			if ( ! $has_main && '' !== $main_key && (string) $key === (string) $main_key ) {
				$clean['is_main'] = 1;
				$has_main         = true;
			}
			$items[] = $clean;
		}
		return $items;
	}

	/**
	 * Sanitize a submitted item. Returns null when invalid.
	 *
	 * @param mixed $item Raw item.
	 * @return array|null
	 */
	public static function sanitize_item( $item ) {
		if ( ! is_array( $item ) ) {
			return null;
		}

		$type = ( isset( $item['type'] ) && 'external' === $item['type'] ) ? 'external' : 'upload';

		$clean = array(
			'type'      => $type,
			'video_id'  => isset( $item['video_id'] ) ? absint( $item['video_id'] ) : 0,
			'url'       => isset( $item['url'] ) ? esc_url_raw( trim( (string) $item['url'] ), array( 'http', 'https' ) ) : '',
			'cover_id'  => isset( $item['cover_id'] ) ? absint( $item['cover_id'] ) : 0,
			'title'     => isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '',
			'hide_play' => empty( $item['hide_play'] ) ? 0 : 1,
			'ratio'     => ( isset( $item['ratio'] ) && '' !== $item['ratio'] && array_key_exists( (string) $item['ratio'], self::item_ratios() ) ) ? (string) $item['ratio'] : '',
			'is_main'   => 0,
			'date'      => '',
		);

		if ( 'upload' === $type ) {
			$mime = $clean['video_id'] ? (string) get_post_mime_type( $clean['video_id'] ) : '';
			if ( 0 !== strpos( $mime, 'video/' ) ) {
				return null;
			}
			$clean['url'] = '';
		} else {
			if ( '' === $clean['url'] || ! self::parse_external( $clean['url'] ) ) {
				return null;
			}
			$clean['video_id'] = 0;
		}

		if ( $clean['cover_id'] && ! wp_attachment_is_image( $clean['cover_id'] ) ) {
			$clean['cover_id'] = 0;
		}

		$date          = isset( $item['date'] ) ? sanitize_text_field( (string) $item['date'] ) : '';
		$clean['date'] = preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date ) ? $date : current_time( 'mysql' );

		return $clean;
	}

	/**
	 * Build render data for an item.
	 *
	 * @param array  $item       Saved item.
	 * @param string $image_size Image size.
	 * @return array|null
	 */
	public static function prepare( $item, $image_size = 'medium_large' ) {
		if ( ! is_array( $item ) || empty( $item['type'] ) ) {
			return null;
		}

		$data     = array(
			'title'       => isset( $item['title'] ) ? (string) $item['title'] : '',
			'date'        => isset( $item['date'] ) ? (string) $item['date'] : '',
			'hide_play'   => ! empty( $item['hide_play'] ),
			'is_main'     => ! empty( $item['is_main'] ),
			'ratio'       => '',
			'player'      => array(),
			'content_url' => '',
			'embed_url'   => '',
			'cover_html'  => '',
			'cover_url'   => '',
			'provider'    => '',
		);
		$thumb_id = 0;
		$thumb    = '';

		if ( 'upload' === $item['type'] ) {
			$id  = isset( $item['video_id'] ) ? absint( $item['video_id'] ) : 0;
			$url = $id ? wp_get_attachment_url( $id ) : '';
			if ( ! $url ) {
				return null;
			}
			$data['provider']    = 'file';
			$data['player']      = array(
				'type' => 'file',
				'src'  => $url,
				'mime' => (string) get_post_mime_type( $id ),
			);
			$data['content_url'] = $url;
			$thumb_id            = (int) get_post_thumbnail_id( $id );
			$meta                = wp_get_attachment_metadata( $id );
			if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
				$data['ratio'] = absint( $meta['width'] ) . '/' . absint( $meta['height'] );
			}
			if ( '' === $data['title'] ) {
				$data['title'] = get_the_title( $id );
			}
			if ( '' === $data['date'] ) {
				$data['date'] = (string) get_post_field( 'post_date', $id );
			}
		} else {
			$parsed = self::parse_external( isset( $item['url'] ) ? (string) $item['url'] : '' );
			if ( ! $parsed ) {
				return null;
			}
			$data['provider'] = $parsed['provider'];
			$thumb            = $parsed['thumb'];
			if ( ! empty( $parsed['ratio'] ) ) {
				$data['ratio'] = $parsed['ratio'];
			}

			if ( 'file' === $parsed['provider'] ) {
				$data['player']      = array(
					'type' => 'file',
					'src'  => $parsed['src'],
					'mime' => $parsed['mime'],
				);
				$data['content_url'] = $parsed['src'];
			} else {
				$data['player']    = array(
					'type' => 'iframe',
					'src'  => $parsed['autoplay'],
				);
				$data['embed_url'] = $parsed['embed'];
			}
		}

		// Manual orientation wins over detection.
		if ( ! empty( $item['ratio'] ) && array_key_exists( (string) $item['ratio'], self::item_ratios() ) ) {
			$data['ratio'] = (string) $item['ratio'];
		}
		$data['player']['ratio'] = $data['ratio'];

		$cover_id = ! empty( $item['cover_id'] ) ? absint( $item['cover_id'] ) : $thumb_id;
		$alt      = '' !== $data['title'] ? $data['title'] : __( 'Video', 'wc-category-video-slider' );

		if ( $cover_id && wp_attachment_is_image( $cover_id ) ) {
			$data['cover_id']  = $cover_id;
			$data['cover_url'] = (string) wp_get_attachment_image_url( $cover_id, 'full' );
			$data['cover_alt'] = $alt;
		} elseif ( $thumb ) {
			$data['cover_url'] = $thumb;
			$data['cover_alt'] = $alt;
		}

		$data['cover_html'] = self::cover_html( $data, $image_size );

		if ( $data['cover_url'] ) {
			$data['player']['poster'] = $data['cover_url'];
		}

		return $data;
	}

	/**
	 * Cover <img> markup for a prepared item at a given size.
	 *
	 * @param array  $data       Prepared item.
	 * @param string $image_size Size.
	 * @param bool   $eager      Load eagerly (above-the-fold main video).
	 * @return string
	 */
	public static function cover_html( $data, $image_size, $eager = false ) {
		$alt = isset( $data['cover_alt'] ) ? $data['cover_alt'] : '';
		if ( ! empty( $data['cover_id'] ) ) {
			$attr = array(
				'class'    => 'rcvs__cover',
				'alt'      => $alt,
				'decoding' => 'async',
				'loading'  => $eager ? 'eager' : 'lazy',
			);
			if ( $eager ) {
				$attr['fetchpriority'] = 'high';
			}
			return wp_get_attachment_image( $data['cover_id'], $image_size, false, $attr );
		}
		if ( ! empty( $data['cover_url'] ) ) {
			return sprintf(
				'<img class="rcvs__cover" src="%1$s" alt="%2$s" loading="%3$s" decoding="async">',
				esc_url( $data['cover_url'] ),
				esc_attr( $alt ),
				$eager ? 'eager' : 'lazy'
			);
		}
		return '';
	}

	/**
	 * Parse an external URL into provider data.
	 *
	 * @param string $url URL.
	 * @return array|null { provider, embed, autoplay, thumb, src, mime }
	 */
	public static function parse_external( $url ) {
		$url   = trim( (string) $url );
		$parts = '' !== $url ? wp_parse_url( $url ) : false;
		if ( ! $parts || empty( $parts['host'] ) || empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return null;
		}

		$base = array(
			'provider' => '',
			'embed'    => '',
			'autoplay' => '',
			'thumb'    => '',
			'src'      => '',
			'mime'     => '',
			'ratio'    => '',
		);

		// YouTube (watch, youtu.be, shorts, live, embed).
		if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m ) ) {
			$embed = 'https://www.youtube-nocookie.com/embed/' . $m[1];
			return array_merge(
				$base,
				array(
					'provider' => 'youtube',
					'embed'    => $embed,
					'autoplay' => add_query_arg(
						array(
							'autoplay'    => 1,
							'rel'         => 0,
							'playsinline' => 1,
						),
						$embed
					),
					'thumb'    => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg',
					'ratio'    => false !== stripos( $url, '/shorts/' ) ? '9/16' : '',
				)
			);
		}

		// Aparat.
		if ( preg_match( '~aparat\.com/(?:v/|video/video/embed/videohash/)([A-Za-z0-9]+)~i', $url, $m ) ) {
			$embed = 'https://www.aparat.com/video/video/embed/videohash/' . $m[1] . '/vt/frame';
			return array_merge(
				$base,
				array(
					'provider' => 'aparat',
					'embed'    => $embed,
					'autoplay' => add_query_arg( 'autoplay', 'true', $embed ),
				)
			);
		}

		// Vimeo.
		if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/|showcase/\d+/video/)?(\d+)~i', $url, $m ) ) {
			$embed = 'https://player.vimeo.com/video/' . $m[1];
			return array_merge(
				$base,
				array(
					'provider' => 'vimeo',
					'embed'    => $embed,
					'autoplay' => add_query_arg( 'autoplay', 1, $embed ),
				)
			);
		}

		// Direct video file.
		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$map  = array(
			'mp4'  => 'video/mp4',
			'm4v'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogv'  => 'video/ogg',
			'ogg'  => 'video/ogg',
			'mov'  => 'video/quicktime',
		);
		if ( isset( $map[ $ext ] ) ) {
			return array_merge(
				$base,
				array(
					'provider' => 'file',
					'src'      => $url,
					'mime'     => $map[ $ext ],
				)
			);
		}

		// Any other HTTPS embeddable URL (used as iframe src).
		if ( 0 === stripos( $url, 'https://' ) ) {
			return array_merge(
				$base,
				array(
					'provider' => 'iframe',
					'embed'    => $url,
					'autoplay' => $url,
				)
			);
		}

		return null;
	}

	/**
	 * First category that has videos (used for editor previews).
	 *
	 * @return int
	 */
	public static function get_preview_term_id() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'number'     => 1,
				'fields'     => 'ids',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::META_VIDEOS,
						'compare' => 'EXISTS',
					),
				),
			)
		);
		return ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? (int) $terms[0] : 0;
	}
}
