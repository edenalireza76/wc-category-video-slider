<?php
/**
 * Category edit screen: video gallery fields.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Admin term meta UI.
 */
final class Admin_Term {

	/**
	 * Singleton.
	 *
	 * @var Admin_Term|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Admin_Term
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
		$tax = Video_Helper::TAXONOMY;
		add_action( "{$tax}_edit_form_fields", array( $this, 'edit_fields' ), 5 );
		add_action( "{$tax}_add_form_fields", array( $this, 'add_fields_notice' ), 5 );
		add_action( "edited_{$tax}", array( $this, 'save' ) );
		add_action( "created_{$tax}", array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( "manage_edit-{$tax}_columns", array( $this, 'add_column' ) );
		add_filter( "manage_{$tax}_custom_column", array( $this, 'column_content' ), 10, 3 );
	}

	/**
	 * Enqueue on category screens and on the settings page.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue( $hook ) {
		$screen      = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_term     = $screen && in_array( $screen->base, array( 'term', 'edit-tags' ), true ) && Video_Helper::TAXONOMY === $screen->taxonomy;
		$is_settings = false !== strpos( (string) $hook, Settings::PAGE_SLUG );

		if ( ! $is_term && ! $is_settings ) {
			return;
		}

		$deps = array( 'jquery' );
		if ( $is_term ) {
			wp_enqueue_media();
			$deps = array( 'jquery', 'jquery-ui-sortable', 'wp-util' );
		}

		wp_enqueue_style( 'rcvs-admin', RCVS_URL . 'assets/css/admin.css', array(), RCVS_VERSION );
		wp_enqueue_script( 'rcvs-admin', RCVS_URL . 'assets/js/admin.js', $deps, RCVS_VERSION, true );
		wp_localize_script(
			'rcvs-admin',
			'rcvsAdmin',
			array(
				'chooseVideo'   => __( 'Choose video', 'wc-category-video-slider' ),
				'chooseVideos'  => __( 'Choose videos', 'wc-category-video-slider' ),
				'chooseCover'   => __( 'Choose cover image', 'wc-category-video-slider' ),
				'useThis'       => __( 'Use this', 'wc-category-video-slider' ),
				'addSelected'   => __( 'Add selected videos', 'wc-category-video-slider' ),
				'confirmRemove' => __( 'Remove this video from the gallery?', 'wc-category-video-slider' ),
				'untitled'      => __( '(untitled video)', 'wc-category-video-slider' ),
			)
		);
	}

	/**
	 * Notice on the "Add new category" form.
	 */
	public function add_fields_notice() {
		?>
		<div class="form-field rcvs-add-notice">
			<label><?php esc_html_e( 'Category videos', 'wc-category-video-slider' ); ?></label>
			<p class="description"><?php esc_html_e( 'Save the category first, then edit it to add a video gallery.', 'wc-category-video-slider' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Fields on the edit category screen.
	 *
	 * @param \WP_Term $term Term.
	 */
	public function edit_fields( $term ) {
		$items   = Video_Helper::get_raw_items( $term->term_id );
		$enabled = Video_Helper::is_enabled( $term->term_id );
		$heading = (string) get_term_meta( $term->term_id, Video_Helper::META_HEADING, true );
		?>
		<tr class="form-field rcvs-term-row">
			<th scope="row"><label><?php esc_html_e( 'Category video gallery', 'wc-category-video-slider' ); ?></label></th>
			<td>
				<div id="rcvs-term-box" class="rcvs-box">
					<?php wp_nonce_field( 'rcvs_save_term', 'rcvs_term_nonce' ); ?>

					<div class="rcvs-box__head">
						<label class="rcvs-inline">
							<input type="checkbox" name="rcvs_enabled" value="1" <?php checked( $enabled ); ?>>
							<?php esc_html_e( 'Show the video slider on this category page', 'wc-category-video-slider' ); ?>
						</label>
						<label class="rcvs-heading-field">
							<span><?php esc_html_e( 'Slider heading (optional)', 'wc-category-video-slider' ); ?></span>
							<input type="text" name="rcvs_heading" value="<?php echo esc_attr( $heading ); ?>" placeholder="<?php esc_attr_e( 'Uses the default heading from settings', 'wc-category-video-slider' ); ?>">
						</label>
					</div>

					<div class="rcvs-items">
						<?php
						foreach ( $items as $i => $item ) {
							$this->item_html( 'i' . $i, $item );
						}
						?>
					</div>

					<p class="rcvs-empty"<?php echo $items ? ' hidden' : ''; ?>><?php esc_html_e( 'No videos yet. Add your first video.', 'wc-category-video-slider' ); ?></p>

					<div class="rcvs-box__actions">
						<button type="button" class="button button-primary rcvs-add"><span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add video', 'wc-category-video-slider' ); ?></button>
						<button type="button" class="button rcvs-add-bulk"><span class="dashicons dashicons-format-video"></span> <?php esc_html_e( 'Add several from media library', 'wc-category-video-slider' ); ?></button>
						<a class="rcvs-settings-link" href="<?php echo esc_url( admin_url( 'admin.php?page=' . Settings::PAGE_SLUG ) ); ?>"><?php esc_html_e( 'Slider appearance settings', 'wc-category-video-slider' ); ?></a>
					</div>
					<p class="description"><?php esc_html_e( 'Drag items to reorder. External links supported: YouTube, Aparat, Vimeo and direct .mp4/.webm files. Changes are saved with the "Update" button.', 'wc-category-video-slider' ); ?></p>
				</div>

				<script type="text/html" id="tmpl-rcvs-item">
					<?php $this->item_html( '{{ data.key }}', array( 'type' => '{{ data.type }}' ), true ); ?>
				</script>
			</td>
		</tr>
		<?php
	}

	/**
	 * One repeater item.
	 *
	 * @param string $key      Field key.
	 * @param array  $item     Item data.
	 * @param bool   $template Whether rendering the JS template.
	 */
	private function item_html( $key, $item, $template = false ) {
		$item     = wp_parse_args(
			$item,
			array(
				'type'     => 'upload',
				'video_id' => 0,
				'url'      => '',
				'cover_id' => 0,
				'title'    => '',
				'date'     => '',
			)
		);
		$type     = $template ? 'upload' : ( 'external' === $item['type'] ? 'external' : 'upload' );
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
		<div class="rcvs-item rcvs-item--<?php echo esc_attr( $type ); ?>">
			<div class="rcvs-item__bar">
				<span class="rcvs-item__handle dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'wc-category-video-slider' ); ?>"></span>
				<span class="rcvs-item__index"></span>
				<span class="rcvs-item__label"><?php echo esc_html( $label ); ?></span>
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
	}

	/**
	 * Save term meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save( $term_id ) {
		if ( ! isset( $_POST['rcvs_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcvs_term_nonce'] ) ), 'rcvs_save_term' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per item below.
		$raw   = ( isset( $_POST['rcvs_videos'] ) && is_array( $_POST['rcvs_videos'] ) ) ? wp_unslash( $_POST['rcvs_videos'] ) : array();
		$items = array();
		foreach ( $raw as $item ) {
			$clean = Video_Helper::sanitize_item( $item );
			if ( $clean ) {
				$items[] = $clean;
			}
		}

		if ( $items ) {
			update_term_meta( $term_id, Video_Helper::META_VIDEOS, $items );
		} else {
			delete_term_meta( $term_id, Video_Helper::META_VIDEOS );
		}

		update_term_meta( $term_id, Video_Helper::META_ENABLED, isset( $_POST['rcvs_enabled'] ) ? 'yes' : 'no' );

		$heading = isset( $_POST['rcvs_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['rcvs_heading'] ) ) : '';
		if ( '' !== $heading ) {
			update_term_meta( $term_id, Video_Helper::META_HEADING, $heading );
		} else {
			delete_term_meta( $term_id, Video_Helper::META_HEADING );
		}

		do_action( 'rcvs_term_saved', $term_id, $items );
	}

	/**
	 * Category list column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns['rcvs_videos'] = '<span class="dashicons dashicons-format-video" title="' . esc_attr__( 'Videos', 'wc-category-video-slider' ) . '"></span>';
		return $columns;
	}

	/**
	 * Column content.
	 *
	 * @param string $content Content.
	 * @param string $column  Column.
	 * @param int    $term_id Term ID.
	 * @return string
	 */
	public function column_content( $content, $column, $term_id ) {
		if ( 'rcvs_videos' === $column ) {
			$count   = count( Video_Helper::get_raw_items( $term_id ) );
			$content = $count ? (string) absint( $count ) : '–';
		}
		return $content;
	}
}
