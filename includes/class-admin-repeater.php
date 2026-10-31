<?php
/**
 * Shared video-list editor (used by categories and standalone galleries) + admin assets.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Repeater UI.
 */
final class Admin_Repeater {

	/**
	 * Template printed?
	 *
	 * @var bool
	 */
	private static $template_printed = false;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Whether the current admin screen needs our assets.
	 *
	 * @param string $hook Hook suffix.
	 * @return string '' | 'term' | 'gallery' | 'settings'
	 */
	private static function screen_kind( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( false !== strpos( (string) $hook, Settings::PAGE_SLUG ) ) {
			return 'settings';
		}
		if ( ! $screen ) {
			return '';
		}
		if ( in_array( $screen->base, array( 'term', 'edit-tags' ), true ) && Video_Helper::TAXONOMY === $screen->taxonomy ) {
			return 'term';
		}
		if ( Video_Helper::POST_TYPE === $screen->post_type ) {
			return 'gallery';
		}
		return '';
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue( $hook ) {
		$kind = self::screen_kind( $hook );
		if ( '' === $kind ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'rcvs-admin', RCVS_URL . 'assets/css/admin.css', array(), RCVS_VERSION );
		wp_enqueue_script( 'rcvs-admin', RCVS_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), RCVS_VERSION, true );
		wp_localize_script(
			'rcvs-admin',
			'rcvsAdmin',
			array(
				'chooseVideo'   => __( 'Choose video', 'wc-category-video-slider' ),
				'chooseVideos'  => __( 'Choose videos', 'wc-category-video-slider' ),
				'chooseCover'   => __( 'Choose cover image', 'wc-category-video-slider' ),
				'chooseImage'   => __( 'Choose image', 'wc-category-video-slider' ),
				'useThis'       => __( 'Use this', 'wc-category-video-slider' ),
				'addSelected'   => __( 'Add selected videos', 'wc-category-video-slider' ),
				'confirmRemove' => __( 'Remove this video from the gallery?', 'wc-category-video-slider' ),
				'untitled'      => __( '(untitled video)', 'wc-category-video-slider' ),
				'copied'        => __( 'Copied!', 'wc-category-video-slider' ),
			)
		);
	}

	/**
	 * Print the editor.
	 *
	 * @param array  $items Saved items.
	 * @param string $id    Box element ID.
	 */
	public static function render( $items, $id ) {
		$has_main = false;
		foreach ( $items as $item ) {
			if ( ! empty( $item['is_main'] ) ) {
				$has_main = true;
				break;
			}
		}
		?>
		<div class="rcvs-repeater" id="<?php echo esc_attr( $id ); ?>" data-rcvs-repeater="1">
			<div class="rcvs-items">
				<?php
				foreach ( $items as $i => $item ) {
					$is_main = $has_main ? ! empty( $item['is_main'] ) : 0 === $i;
					self::item_html( 'i' . $i, $item, $is_main );
				}
				?>
			</div>

			<p class="rcvs-empty"<?php echo $items ? ' hidden' : ''; ?>><?php esc_html_e( 'No videos yet. Add your first video.', 'wc-category-video-slider' ); ?></p>

			<div class="rcvs-box__actions">
				<button type="button" class="button button-primary rcvs-add"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add video', 'wc-category-video-slider' ); ?></button>
				<button type="button" class="button rcvs-add-bulk"><span class="dashicons dashicons-format-video"></span> <?php esc_html_e( 'Add several from media library', 'wc-category-video-slider' ); ?></button>
				<a class="rcvs-settings-link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Settings::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Appearance settings', 'wc-category-video-slider' ); ?></a>
			</div>
			<p class="description"><?php esc_html_e( 'Drag items to reorder. External links supported: YouTube, Aparat, Vimeo and direct .mp4/.webm files. "Main video" is used by the "Main video + gallery below" layout.', 'wc-category-video-slider' ); ?></p>
		</div>
		<?php
		self::print_template();
	}

	/**
	 * JS template (once per page).
	 */
	private static function print_template() {
		if ( self::$template_printed ) {
			return;
		}
		self::$template_printed = true;
		echo '<script type="text/html" id="tmpl-rcvs-item">';
		self::item_html( '{{ data.key }}', array(), false, true );
		echo '</script>';
	}

	/**
	 * One repeater item.
	 *
	 * @param string $key      Field key.
	 * @param array  $item     Item data.
	 * @param bool   $is_main  Main video.
	 * @param bool   $template Rendering the JS template.
	 */
	private static function item_html( $key, $item, $is_main, $template = false ) {
		$item     = wp_parse_args(
			$item,
			array(
				'type'      => 'upload',
				'video_id'  => 0,
				'url'       => '',
				'cover_id'  => 0,
				'title'     => '',
				'hide_play' => 0,
				'ratio'     => '',
				'date'      => '',
			)
		);
		$type     = 'external' === $item['type'] ? 'external' : 'upload';
		$name     = 'rcvs_videos[' . $key . ']';
		$video_id = absint( $item['video_id'] );
		$cover_id = absint( $item['cover_id'] );

		$video_label = '';
		if ( $video_id ) {
			$file        = get_attached_file( $video_id );
			$video_label = $file ? wp_basename( $file ) : get_the_title( $video_id );
		}
		$cover_html = $cover_id ? wp_get_attachment_image( $cover_id, 'thumbnail' ) : '';
		$label      = '' !== $item['title'] ? $item['title'] : __( '(untitled video)', 'wc-category-video-slider' );
		?>
		<div class="rcvs-item rcvs-item--<?php echo esc_attr( $type ); ?><?php echo $is_main ? ' is-main' : ''; ?>">
			<div class="rcvs-item__bar">
				<span class="rcvs-item__handle dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'wc-category-video-slider' ); ?>"></span>
				<span class="rcvs-item__index"></span>
				<span class="rcvs-item__label"><?php echo esc_html( $label ); ?></span>
				<span class="rcvs-item__badge"><?php esc_html_e( 'Main video', 'wc-category-video-slider' ); ?></span>
				<button type="button" class="button-link rcvs-item__toggle" aria-expanded="true" aria-label="<?php esc_attr_e( 'Collapse / expand', 'wc-category-video-slider' ); ?>"><span class="dashicons dashicons-arrow-up-alt2"></span></button>
				<button type="button" class="button-link button-link-delete rcvs-item__remove"><?php esc_html_e( 'Remove', 'wc-category-video-slider' ); ?></button>
			</div>

			<div class="rcvs-item__body">
				<div class="rcvs-item__col">
					<p class="rcvs-field">
						<label><?php esc_html_e( 'Video title', 'wc-category-video-slider' ); ?></label>
						<input type="text" class="rcvs-title-input" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $item['title'] ); ?>">
					</p>

					<div class="rcvs-field">
						<label><?php esc_html_e( 'Video source', 'wc-category-video-slider' ); ?></label>
						<div class="rcvs-segment">
							<label><input type="radio" class="rcvs-type" name="<?php echo esc_attr( $name ); ?>[type]" value="upload" <?php checked( 'upload', $type ); ?>> <span><?php esc_html_e( 'Upload / media library', 'wc-category-video-slider' ); ?></span></label>
							<label><input type="radio" class="rcvs-type" name="<?php echo esc_attr( $name ); ?>[type]" value="external" <?php checked( 'external', $type ); ?>> <span><?php esc_html_e( 'External link', 'wc-category-video-slider' ); ?></span></label>
						</div>
					</div>

					<div class="rcvs-field rcvs-only-upload">
						<input type="hidden" class="rcvs-video-id" name="<?php echo esc_attr( $name ); ?>[video_id]" value="<?php echo esc_attr( $video_id ? $video_id : '' ); ?>">
						<button type="button" class="button rcvs-pick-video"><span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload / choose video', 'wc-category-video-slider' ); ?></button>
						<span class="rcvs-video-name"><?php echo esc_html( $video_label ); ?></span>
					</div>

					<p class="rcvs-field rcvs-only-external">
						<input type="url" dir="ltr" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $item['url'] ); ?>" placeholder="https://www.aparat.com/v/xxxxx  |  https://youtu.be/xxxxx">
					</p>

					<div class="rcvs-field rcvs-flags">
						<label class="rcvs-inline">
							<input type="radio" class="rcvs-main-radio" name="rcvs_main" value="<?php echo esc_attr( $key ); ?>" <?php checked( $is_main ); ?>>
							<?php esc_html_e( 'Main video', 'wc-category-video-slider' ); ?>
						</label>
						<label class="rcvs-inline">
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[hide_play]" value="1" <?php checked( ! empty( $item['hide_play'] ) ); ?>>
							<?php esc_html_e( 'Hide play icon on this video', 'wc-category-video-slider' ); ?>
						</label>
						<label class="rcvs-inline">
							<?php esc_html_e( 'Orientation', 'wc-category-video-slider' ); ?>
							<select name="<?php echo esc_attr( $name ); ?>[ratio]">
								<?php foreach ( Video_Helper::item_ratios() as $rv => $rl ) : ?>
									<option value="<?php echo esc_attr( $rv ); ?>" <?php selected( (string) $item['ratio'], (string) $rv ); ?>><?php echo esc_html( $rl ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					</div>

					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[date]" value="<?php echo esc_attr( $item['date'] ); ?>">
				</div>

				<div class="rcvs-item__cover">
					<label><?php esc_html_e( 'Cover image', 'wc-category-video-slider' ); ?></label>
					<input type="hidden" class="rcvs-cover-id" name="<?php echo esc_attr( $name ); ?>[cover_id]" value="<?php echo esc_attr( $cover_id ? $cover_id : '' ); ?>">
					<button type="button" class="rcvs-cover-preview rcvs-pick-cover<?php echo $cover_html ? ' has-image' : ''; ?>" aria-label="<?php esc_attr_e( 'Choose cover image', 'wc-category-video-slider' ); ?>">
						<?php echo $cover_html ? wp_kses_post( $cover_html ) : '<span class="dashicons dashicons-format-image"></span>'; ?>
					</button>
					<button type="button" class="button-link button-link-delete rcvs-remove-cover"<?php echo $cover_html ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove cover', 'wc-category-video-slider' ); ?></button>
					<small><?php esc_html_e( 'Optional. YouTube thumbnails are used automatically.', 'wc-category-video-slider' ); ?></small>
				</div>
			</div>
		</div>
		<?php
		unset( $template );
	}

	/**
	 * Read + sanitize the submitted list (caller verifies nonce & capability).
	 *
	 * @return array
	 */
	public static function from_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by caller.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per item.
		$raw  = ( isset( $_POST['rcvs_videos'] ) && is_array( $_POST['rcvs_videos'] ) ) ? wp_unslash( $_POST['rcvs_videos'] ) : array();
		$main = isset( $_POST['rcvs_main'] ) ? sanitize_text_field( wp_unslash( $_POST['rcvs_main'] ) ) : '';
		// phpcs:enable
		return Video_Helper::sanitize_items( $raw, $main );
	}

	/**
	 * Layout select HTML.
	 *
	 * @param string $name    Field name.
	 * @param string $current Current.
	 */
	public static function layout_select( $name, $current ) {
		echo '<select name="' . esc_attr( $name ) . '">';
		echo '<option value="">' . esc_html__( 'Default (from settings)', 'wc-category-video-slider' ) . '</option>';
		foreach ( Settings::layout_options() as $value => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Play-icon visibility select HTML.
	 *
	 * @param string $name    Field name.
	 * @param string $current Current.
	 */
	public static function play_select( $name, $current ) {
		$opts = array(
			''    => __( 'Default (from settings)', 'wc-category-video-slider' ),
			'yes' => __( 'Show', 'wc-category-video-slider' ),
			'no'  => __( 'Hide', 'wc-category-video-slider' ),
		);
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( $opts as $value => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Image picker field (attachment ID).
	 *
	 * @param string $name  Field name.
	 * @param int    $value Attachment ID.
	 */
	public static function image_field( $name, $value ) {
		$value = absint( $value );
		$img   = $value ? wp_get_attachment_image( $value, 'medium', false, array( 'alt' => '' ) ) : '';
		?>
		<div class="rcvs-image-field rcvs-image-field--wide">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ? (string) $value : '' ); ?>">
			<span class="rcvs-image-field__preview"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup. ?></span>
			<button type="button" class="button rcvs-image-field__pick"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Choose image', 'wc-category-video-slider' ); ?></button>
			<button type="button" class="button-link button-link-delete rcvs-image-field__remove"<?php echo $img ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'wc-category-video-slider' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Sanitize layout value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function clean_layout( $value ) {
		$value = is_string( $value ) ? sanitize_key( $value ) : '';
		return in_array( $value, Video_Helper::layouts(), true ) ? $value : '';
	}

	/**
	 * Sanitize play visibility value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function clean_play( $value ) {
		return in_array( $value, array( 'yes', 'no' ), true ) ? $value : '';
	}
}
