<?php
/**
 * Uninstall: remove plugin settings and category video data.
 *
 * @package WC_Category_Video_Slider
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Keep data on uninstall by defining RCVS_KEEP_DATA as true in wp-config.php.
 */
if ( defined( 'RCVS_KEEP_DATA' ) && RCVS_KEEP_DATA ) {
	return;
}

delete_option( 'rcvs_settings' );
delete_option( 'rcvs_version' );

foreach ( array( '_rcvs_videos', '_rcvs_enabled', '_rcvs_heading', '_rcvs_layout', '_rcvs_play', '_rcvs_max_width', '_rcvs_img', '_rcvs_img_pos', '_rcvs_img_link' ) as $rcvs_meta_key ) {
	delete_metadata( 'term', 0, $rcvs_meta_key, '', true );
}

$rcvs_galleries = get_posts(
	array(
		'post_type'      => 'rcvs_gallery',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $rcvs_galleries as $rcvs_gallery_id ) {
	wp_delete_post( $rcvs_gallery_id, true );
}
