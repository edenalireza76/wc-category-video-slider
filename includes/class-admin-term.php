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
		add_filter( "manage_edit-{$tax}_columns", array( $this, 'add_column' ) );
		add_filter( "manage_{$tax}_custom_column", array( $this, 'column_content' ), 10, 3 );
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
		$layout  = (string) get_term_meta( $term->term_id, Video_Helper::META_LAYOUT, true );
		$play    = (string) get_term_meta( $term->term_id, Video_Helper::META_PLAY, true );
		$width   = absint( get_term_meta( $term->term_id, Video_Helper::META_WIDTH, true ) );
		$img     = absint( get_term_meta( $term->term_id, Video_Helper::META_IMG, true ) );
		$img_pos = (string) get_term_meta( $term->term_id, Video_Helper::META_IMG_POS, true );
		$img_url = (string) get_term_meta( $term->term_id, Video_Helper::META_IMG_URL, true );
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
						<label class="rcvs-head-field">
							<span><?php esc_html_e( 'Heading (optional)', 'wc-category-video-slider' ); ?></span>
							<input type="text" name="rcvs_heading" value="<?php echo esc_attr( $heading ); ?>" placeholder="<?php esc_attr_e( 'Uses the default heading from settings', 'wc-category-video-slider' ); ?>">
						</label>
						<label class="rcvs-head-field rcvs-head-field--small">
							<span><?php esc_html_e( 'Layout', 'wc-category-video-slider' ); ?></span>
							<?php Admin_Repeater::layout_select( 'rcvs_layout', $layout ); ?>
						</label>
						<label class="rcvs-head-field rcvs-head-field--small">
							<span><?php esc_html_e( 'Play icon', 'wc-category-video-slider' ); ?></span>
							<?php Admin_Repeater::play_select( 'rcvs_play', $play ); ?>
						</label>
						<label class="rcvs-head-field rcvs-head-field--small">
							<span><?php esc_html_e( 'Max width (px)', 'wc-category-video-slider' ); ?></span>
							<input type="number" min="0" max="3000" step="10" name="rcvs_max_width" value="<?php echo esc_attr( $width ? (string) $width : '' ); ?>" placeholder="<?php esc_attr_e( 'Default', 'wc-category-video-slider' ); ?>">
						</label>
					</div>

					<div class="rcvs-catimg-box">
						<h4><?php esc_html_e( 'Category image', 'wc-category-video-slider' ); ?></h4>
						<div class="rcvs-catimg-box__row">
							<?php Admin_Repeater::image_field( 'rcvs_img', $img ); ?>
							<div class="rcvs-catimg-box__opts">
								<label class="rcvs-head-field">
									<span><?php esc_html_e( 'Position', 'wc-category-video-slider' ); ?></span>
									<select name="rcvs_img_pos">
										<option value=""><?php esc_html_e( 'Default (from settings)', 'wc-category-video-slider' ); ?></option>
										<option value="before" <?php selected( $img_pos, 'before' ); ?>><?php esc_html_e( 'Above the videos', 'wc-category-video-slider' ); ?></option>
										<option value="after" <?php selected( $img_pos, 'after' ); ?>><?php esc_html_e( 'Below the videos', 'wc-category-video-slider' ); ?></option>
									</select>
								</label>
								<label class="rcvs-head-field">
									<span><?php esc_html_e( 'Link (optional)', 'wc-category-video-slider' ); ?></span>
									<input type="url" dir="ltr" name="rcvs_img_link" value="<?php echo esc_attr( $img_url ); ?>" placeholder="https://">
								</label>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Shown with the category description, above or below the videos. Works even if the category has no videos.', 'wc-category-video-slider' ); ?></p>
					</div>

					<h4 class="rcvs-videos-title"><?php esc_html_e( 'Videos', 'wc-category-video-slider' ); ?></h4>

					<?php Admin_Repeater::render( $items, 'rcvs-term-repeater' ); ?>
					<p class="description"><?php esc_html_e( 'Changes are saved with the "Update" button.', 'wc-category-video-slider' ); ?></p>
				</div>
			</td>
		</tr>
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

		$items = Admin_Repeater::from_request();
		if ( $items ) {
			update_term_meta( $term_id, Video_Helper::META_VIDEOS, $items );
		} else {
			delete_term_meta( $term_id, Video_Helper::META_VIDEOS );
		}

		update_term_meta( $term_id, Video_Helper::META_ENABLED, isset( $_POST['rcvs_enabled'] ) ? 'yes' : 'no' );

		$fields = array(
			Video_Helper::META_HEADING => isset( $_POST['rcvs_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['rcvs_heading'] ) ) : '',
			Video_Helper::META_LAYOUT  => isset( $_POST['rcvs_layout'] ) ? Admin_Repeater::clean_layout( wp_unslash( $_POST['rcvs_layout'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			Video_Helper::META_PLAY    => isset( $_POST['rcvs_play'] ) ? Admin_Repeater::clean_play( sanitize_key( wp_unslash( $_POST['rcvs_play'] ) ) ) : '',
		);
		$img_id                            = isset( $_POST['rcvs_img'] ) ? absint( $_POST['rcvs_img'] ) : 0;
		$img_pos                           = isset( $_POST['rcvs_img_pos'] ) ? sanitize_key( wp_unslash( $_POST['rcvs_img_pos'] ) ) : '';
		$fields[ Video_Helper::META_WIDTH ]   = isset( $_POST['rcvs_max_width'] ) ? ( absint( $_POST['rcvs_max_width'] ) ? (string) min( 3000, absint( $_POST['rcvs_max_width'] ) ) : '' ) : '';
		$fields[ Video_Helper::META_IMG ]     = ( $img_id && wp_attachment_is_image( $img_id ) ) ? (string) $img_id : '';
		$fields[ Video_Helper::META_IMG_POS ] = in_array( $img_pos, array( 'before', 'after' ), true ) ? $img_pos : '';
		$fields[ Video_Helper::META_IMG_URL ] = isset( $_POST['rcvs_img_link'] ) ? esc_url_raw( trim( wp_unslash( $_POST['rcvs_img_link'] ) ), array( 'http', 'https' ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		foreach ( $fields as $key => $value ) {
			if ( '' !== $value ) {
				update_term_meta( $term_id, $key, $value );
			} else {
				delete_term_meta( $term_id, $key );
			}
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
