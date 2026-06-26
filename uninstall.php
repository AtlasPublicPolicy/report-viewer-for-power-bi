<?php
/**
 * Uninstall routine for Report Viewer for Power BI.
 *
 * Runs when the plugin is deleted via Plugins > Installed Plugins.
 * The main plugin file is NOT loaded here — only WP core functions available.
 *
 * Removed:  powerbi_settings option (Azure AD credentials + display preferences)
 * Preserved: powerbi_report posts and post meta, powerbi_category terms
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	$rvpbi_site_ids = get_sites( [ 'fields' => 'ids', 'number' => 0 ] );
	foreach ( $rvpbi_site_ids as $rvpbi_site_id ) {
		switch_to_blog( $rvpbi_site_id );
		delete_option( 'powerbi_settings' );
		restore_current_blog();
	}
} else {
	delete_option( 'powerbi_settings' );
}
