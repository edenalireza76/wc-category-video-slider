<?php
/**
 * Standalone video galleries (custom post type) + shortcode helpers.
 *
 * @package WC_Category_Video_Slider
 */

namespace RCVS;

defined( 'ABSPATH' ) || exit;

/**
 * Gallery post type.
 */
final class Gallery {

	/**
	 * Singleton.
	 *
	 * @var Gallery|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return Gallery
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
		add_action( 'init', array( __CLASS__, 'register' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . Video_Helper::POST_TYPE, array( $this, 'meta_boxes' ) );
			add_action( 'save_post_' . Video_Helper::POST_TYPE, array( $this, 'save' ), 10, 2 );
			add_filter( 'manage_' . Video_Helper::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
			add_action( 'manage_' . Video_Helper::POST_TYPE . '_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
			add_filter( 'post_updated_messages', array( $this, 'messages' ) );
			add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
		}
	}

	/**
	 * Register the post type.
	 */
	public static function register() {
		register_post_type(
			Video_Helper::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Video galleries', 'wc-category-video-slider' ),
					'singular_name'      => __( 'Video gallery', 'wc-category-video-slider' ),
					'menu_name'          => __( 'Video galleries', 'wc-category-video-slider' ),
					'add_new'            => __( 'Add gallery', 'wc-category-video-slider' ),
					'add_new_item'       => __( 'Add new video gallery', 'wc-category-video-slider' ),
					'edit_item'          => __( 'Edit video gallery', 'wc-category-video-slider' ),
					'new_item'           => __( 'New video gallery', 'wc-category-video-slider' ),
					'search_items'       => __( 'Search galleries', 'wc-category-video-slider' ),
					'not_found'          => __( 'No galleries found.', 'wc-category-video-slider' ),
					'not_found_in_trash' => __( 'No galleries found in Trash.', 'wc-category-video-slider' ),
					'all_items'          => __( 'All galleries', 'wc-category-video-slider' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'menu_position'       => 58,
				'menu_icon'           => 'dashicons-video-alt3',
				'supports'            => array( 'title' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => true,
			)
		);
	}

	/**
	 * Shortcode for a gallery.
	 *
	 * @param int $id Post ID.
	 * @return string
	 */
	public static function shortcode( $id ) {
		return '[rcvs_gallery id="' . absint( $id ) . '"]';
	}

	/**
	 * Meta boxes.
	 */
	public function meta_boxes() {
		add_meta_box( 'rcvs_gallery_videos', __( 'Videos', 'wc-category-video-slider' ), array( $this, 'box_videos' ), null, 'normal', 'high' );
		add_meta_box( 'rcvs_gallery_shortcode', __( 'Shortcode', 'wc-category-video-slider' ), array( $this, 'box_shortcode' ), null, 'side', 'high' );
		add_meta_box( 'rcvs_gallery_display', __( 'Display', 'wc-category-video-slider' ), array( $this, 'box_display' ), null, 'side', 'default' );
	}

	/**
	 * Videos box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function box_videos( $post ) {
		wp_nonce_field( 'rcvs_save_gallery', 'rcvs_gallery_nonce' );
		echo '<div class="rcvs-box rcvs-box--gallery">';
		Admin_Repeater::render( Video_Helper::get_gallery_raw_items( $post->ID ), 'rcvs-gallery-repeater' );
		echo '</div>';
	}

	/**
	 * Shortcode box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function box_shortcode( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p class="description">' . esc_html__( 'Publish the gallery to get its shortcode.', 'wc-category-video-slider' ) . '</p>';
			return;
		}
		$code = self::shortcode( $post->ID );
		?>
		<div class="rcvs-shortcode">
			<input type="text" readonly dir="ltr" class="widefat code rcvs-shortcode__input" value="<?php echo esc_attr( $code ); ?>" onfocus="this.select();">
			<button type="button" class="button rcvs-copy" data-copy="<?php echo esc_attr( $code ); ?>"><span class="dashicons dashicons-admin-page"></span> <?php esc_html_e( 'Copy', 'wc-category-video-slider' ); ?></button>
		</div>
		<p class="description"><?php esc_html_e( 'Paste it in any page, post, product description, Elementor "Shortcode" widget or text widget. You can also use the Elementor "Category Video Slider" widget and choose this gallery.', 'wc-category-video-slider' ); ?></p>
		<details class="rcvs-attrs">
			<summary><?php esc_html_e( 'Optional attributes', 'wc-category-video-slider' ); ?></summary>
			<ul dir="ltr">
				<li><code>layout="slider|featured|grid"</code></li>
				<li><code>columns="3" columns_tablet="2" columns_mobile="1.2"</code></li>
				<li><code>ratio="16:9"</code></li>
				<li><code>play_icon="yes|no"</code></li>
				<li><code>heading="…" show_heading="no"</code></li>
				<li><code>play_mode="lightbox|inline"</code></li>
				<li><code>autoplay="yes" loop="no" arrows="no" dots="no" titles="no"</code></li>
			</ul>
		</details>
		<?php
	}

	/**
	 * Display box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function box_display( $post ) {
		$layout  = (string) get_post_meta( $post->ID, Video_Helper::META_LAYOUT, true );
		$play    = (string) get_post_meta( $post->ID, Video_Helper::META_PLAY, true );
		$heading = (string) get_post_meta( $post->ID, Video_Helper::META_HEADING, true );
		$width   = absint( get_post_meta( $post->ID, Video_Helper::META_WIDTH, true ) );
		?>
		<p>
			<label for="rcvs_heading"><strong><?php esc_html_e( 'Heading (optional)', 'wc-category-video-slider' ); ?></strong></label>
			<input type="text" class="widefat" id="rcvs_heading" name="rcvs_heading" value="<?php echo esc_attr( $heading ); ?>">
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Layout', 'wc-category-video-slider' ); ?></strong></label><br>
			<?php Admin_Repeater::layout_select( 'rcvs_layout', $layout ); ?>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Play icon', 'wc-category-video-slider' ); ?></strong></label><br>
			<?php Admin_Repeater::play_select( 'rcvs_play', $play ); ?>
		</p>
		<p>
			<label for="rcvs_max_width"><strong><?php esc_html_e( 'Max width (px)', 'wc-category-video-slider' ); ?></strong></label>
			<input type="number" class="small-text" min="0" max="3000" step="10" id="rcvs_max_width" name="rcvs_max_width" value="<?php echo esc_attr( $width ? (string) $width : '' ); ?>" placeholder="<?php esc_attr_e( 'Default', 'wc-category-video-slider' ); ?>">
		</p>
		<?php
	}

	/**
	 * Save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public function save( $post_id, $post ) {
		unset( $post );
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['rcvs_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rcvs_gallery_nonce'] ) ), 'rcvs_save_gallery' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$items = Admin_Repeater::from_request();
		if ( $items ) {
			update_post_meta( $post_id, Video_Helper::META_VIDEOS, $items );
		} else {
			delete_post_meta( $post_id, Video_Helper::META_VIDEOS );
		}

		$fields = array(
			Video_Helper::META_HEADING => isset( $_POST['rcvs_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['rcvs_heading'] ) ) : '',
			Video_Helper::META_LAYOUT  => isset( $_POST['rcvs_layout'] ) ? Admin_Repeater::clean_layout( sanitize_key( wp_unslash( $_POST['rcvs_layout'] ) ) ) : '',
			Video_Helper::META_PLAY    => isset( $_POST['rcvs_play'] ) ? Admin_Repeater::clean_play( sanitize_key( wp_unslash( $_POST['rcvs_play'] ) ) ) : '',
			Video_Helper::META_WIDTH   => ( isset( $_POST['rcvs_max_width'] ) && absint( $_POST['rcvs_max_width'] ) ) ? (string) min( 3000, absint( $_POST['rcvs_max_width'] ) ) : '',
		);
		foreach ( $fields as $key => $value ) {
			if ( '' !== $value ) {
				update_post_meta( $post_id, $key, $value );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}

		do_action( 'rcvs_gallery_saved', $post_id, $items );
	}

	/**
	 * List columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );
		$columns['rcvs_shortcode'] = __( 'Shortcode', 'wc-category-video-slider' );
		$columns['rcvs_count']     = __( 'Videos', 'wc-category-video-slider' );
		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Column content.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public function column_content( $column, $post_id ) {
		if ( 'rcvs_shortcode' === $column ) {
			$code = self::shortcode( $post_id );
			printf(
				'<span class="rcvs-shortcode rcvs-shortcode--inline"><code dir="ltr">%1$s</code> <button type="button" class="button-link rcvs-copy" data-copy="%2$s" aria-label="%3$s"><span class="dashicons dashicons-admin-page"></span></button></span>',
				esc_html( $code ),
				esc_attr( $code ),
				esc_attr__( 'Copy', 'wc-category-video-slider' )
			);
		} elseif ( 'rcvs_count' === $column ) {
			echo esc_html( (string) count( Video_Helper::get_gallery_raw_items( $post_id ) ) );
		}
	}

	/**
	 * Updated messages.
	 *
	 * @param array $messages Messages.
	 * @return array
	 */
	public function messages( $messages ) {
		$saved                                   = __( 'Gallery saved.', 'wc-category-video-slider' );
		$messages[ Video_Helper::POST_TYPE ]     = array_fill( 0, 11, $saved );
		$messages[ Video_Helper::POST_TYPE ][0]  = '';
		$messages[ Video_Helper::POST_TYPE ][2]  = '';
		$messages[ Video_Helper::POST_TYPE ][3]  = '';
		$messages[ Video_Helper::POST_TYPE ][5]  = false;
		return $messages;
	}

	/**
	 * Title placeholder.
	 *
	 * @param string   $text Text.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public function title_placeholder( $text, $post ) {
		return Video_Helper::POST_TYPE === $post->post_type ? __( 'Gallery name (only visible to you)', 'wc-category-video-slider' ) : $text;
	}

	/**
	 * Gallery options for selects.
	 *
	 * @return array
	 */
	public static function options() {
		$posts = get_posts(
			array(
				'post_type'      => Video_Helper::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		$out   = array();
		foreach ( $posts as $id ) {
			$title      = get_the_title( $id );
			$out[ $id ] = ( '' !== $title ? $title : '#' . $id ) . ' (' . count( Video_Helper::get_gallery_raw_items( $id ) ) . ')';
		}
		return $out;
	}
}
