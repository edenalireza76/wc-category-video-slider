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

foreach ( array( '_rcvs_videos', '_rcvs_enabled', '_rcvs_heading' ) as $rcvs_meta_key ) {
	delete_metadata( 'term', 0, $rcvs_meta_key, '', true );
}
