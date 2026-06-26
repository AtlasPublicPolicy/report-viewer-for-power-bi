<?php
/**
 * Plugin Name:  Report Viewer for Power BI
 * Description:  Embed Power BI reports and dashboards in WordPress pages via shortcode, with Azure AD authentication managed through a settings page.
 * Version:      1.0.0
 * Author:       Atlas Public Policy
 * Text Domain:  report-viewer-for-pbi
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'RVPBI_VERSION', '1.0.0' );
define( 'RVPBI_PATH', plugin_dir_path( __FILE__ ) );
define( 'RVPBI_URL', plugin_dir_url( __FILE__ ) );

// Dev mode: set to true to load from Vite dev server instead of dist
define( 'RVPBI_DEV_MODE', false );
define( 'RVPBI_VITE_DEV_URL', 'http://localhost:5173' );

require_once RVPBI_PATH . 'vendor/autoload.php';
require_once RVPBI_PATH . 'vendor/cmb2/init.php';

require_once RVPBI_PATH . 'includes/assets.php';

// -- Power BI --
require_once RVPBI_PATH . 'includes/cpt.php';
require_once RVPBI_PATH . 'includes/report-metabox.php';
require_once RVPBI_PATH . 'includes/powerbi-settings.php';
require_once RVPBI_PATH . 'includes/powerbi-token.php';
require_once RVPBI_PATH . 'includes/powerbi-shortcode.php';
require_once RVPBI_PATH . 'includes/powerbi-rest.php';
require_once RVPBI_PATH . 'includes/admin-columns.php';

class RVPBI {

    /**
     * Instantiates all plugin modules and wires up their hooks.
     * Called on plugins_loaded to ensure all WP plugins are available first.
     */
    public static function init(): void {
        new RVPBI_Assets();
        new PowerBI_Shortcode();
        new PowerBI_Admin_Columns();
        // PowerBI_Settings and PowerBI_REST_Controller register themselves via their own hooks.
    }

    /**
     * Grants the powerbi_view capability to administrators on activation.
     */
    public static function activate(): void {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'powerbi_view' );
        }
    }

    /**
     * Removes the powerbi_view capability from all roles on deactivation.
     */
    public static function deactivate(): void {
        foreach ( wp_roles()->roles as $role_name => $_ ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->remove_cap( 'powerbi_view' );
            }
        }
    }
}

register_activation_hook( __FILE__, [ 'RVPBI', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'RVPBI', 'deactivate' ] );
add_action( 'plugins_loaded', [ 'RVPBI', 'init' ] );
