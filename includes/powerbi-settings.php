<?php
/**
 * CMB2 options page for Power BI Azure AD credentials.
 *
 * Option key: powerbi_settings
 * Fields:
 *   pbi_client_id      — Azure AD app client ID
 *   pbi_client_secret  — Azure AD app client secret
 *   pbi_username       — Master user UPN (ROPC flow)
 *   pbi_password       — Master user password (ROPC flow)
 *   pbi_display_status — Show loading/error UI to end users
 *   pbi_spinner_type   — react-spinners component to use while loading
 *   pbi_spinner_color  — CSS color for the spinner
 */

defined( 'ABSPATH' ) || exit;

class PowerBI_Settings {

    public function __construct() {
        add_action( 'cmb2_admin_init', [ $this, 'register' ] );
        add_action( 'admin_footer',    [ $this, 'copy_prevention_script' ] );
    }

    public function register(): void {
        $cmb = new_cmb2_box( [
            'id'           => 'powerbi_settings_page',
            'title'        => __( 'Power BI Settings', 'report-viewer-for-pbi' ),
            'object_types' => [ 'options-page' ],
            'option_key'   => 'powerbi_settings',
            'parent_slug'  => 'edit.php?post_type=powerbi_report',
            'capability'   => 'manage_options',
            'menu_title'   => __( 'Settings', 'report-viewer-for-pbi' ),
        ] );

        $cmb->add_field( [
            'name' => __( 'Client ID', 'report-viewer-for-pbi' ),
            'desc' => __( 'The application (client) ID of your Azure AD app registration.', 'report-viewer-for-pbi' ),
            'id'   => 'pbi_client_id',
            'type' => 'text',
        ] );

        $cmb->add_field( [
            'name'       => __( 'Client Secret', 'report-viewer-for-pbi' ),
            'desc'       => __( 'The client secret value from your Azure AD app registration.', 'report-viewer-for-pbi' ),
            'id'         => 'pbi_client_secret',
            'type'       => 'text',
            'attributes' => [
                'type'         => 'password',
                'autocomplete' => 'new-password',
            ],
        ] );

        $cmb->add_field( [
            'name' => __( 'Master User (UPN)', 'report-viewer-for-pbi' ),
            'desc' => __( 'Service account email address (ROPC flow). Requires MFA disabled on this account.', 'report-viewer-for-pbi' ),
            'id'   => 'pbi_username',
            'type' => 'text',
        ] );

        $cmb->add_field( [
            'name'       => __( 'Master User Password', 'report-viewer-for-pbi' ),
            'desc'       => __( 'Service account password (ROPC flow).', 'report-viewer-for-pbi' ),
            'id'         => 'pbi_password',
            'type'       => 'text',
            'attributes' => [
                'type'         => 'password',
                'autocomplete' => 'new-password',
            ],
        ] );

        $cmb->add_field( [
            'name' => __( 'Display Status', 'report-viewer-for-pbi' ),
            'desc' => __( 'Show loading and error messages to end users while the report is fetching.', 'report-viewer-for-pbi' ),
            'id'   => 'pbi_display_status',
            'type' => 'checkbox',
        ] );

        $cmb->add_field( [
            'name'    => __( 'Loading Spinner', 'report-viewer-for-pbi' ),
            'desc'    => __( 'Spinner style shown while the report is loading. <a href="https://www.davidhu.io/react-spinners/" target="_blank" rel="noopener">Preview all spinners</a>.', 'report-viewer-for-pbi' ),
            'id'      => 'pbi_spinner_type',
            'type'    => 'select',
            'default' => 'clip',
            'options' => [
                'bar'       => __( 'Bar', 'report-viewer-for-pbi' ),
                'beat'      => __( 'Beat', 'report-viewer-for-pbi' ),
                'bounce'    => __( 'Bounce', 'report-viewer-for-pbi' ),
                'circle'    => __( 'Circle', 'report-viewer-for-pbi' ),
                'clip'      => __( 'Clip', 'report-viewer-for-pbi' ),
                'clock'     => __( 'Clock', 'report-viewer-for-pbi' ),
                'dot'       => __( 'Dot', 'report-viewer-for-pbi' ),
                'fade'      => __( 'Fade', 'report-viewer-for-pbi' ),
                'grid'      => __( 'Grid', 'report-viewer-for-pbi' ),
                'hash'      => __( 'Hash', 'report-viewer-for-pbi' ),
                'moon'      => __( 'Moon', 'report-viewer-for-pbi' ),
                'pacman'    => __( 'Pacman', 'report-viewer-for-pbi' ),
                'propagate' => __( 'Propagate', 'report-viewer-for-pbi' ),
                'puff'      => __( 'Puff', 'report-viewer-for-pbi' ),
                'pulse'     => __( 'Pulse', 'report-viewer-for-pbi' ),
                'ring'      => __( 'Ring', 'report-viewer-for-pbi' ),
                'rise'      => __( 'Rise', 'report-viewer-for-pbi' ),
                'rotate'    => __( 'Rotate', 'report-viewer-for-pbi' ),
                'scale'     => __( 'Scale', 'report-viewer-for-pbi' ),
                'skew'      => __( 'Skew', 'report-viewer-for-pbi' ),
                'square'    => __( 'Square', 'report-viewer-for-pbi' ),
                'sync'      => __( 'Sync', 'report-viewer-for-pbi' ),
            ],
        ] );

        $cmb->add_field( [
            'name'    => __( 'Spinner Color', 'report-viewer-for-pbi' ),
            'desc'    => __( 'Color of the loading spinner.', 'report-viewer-for-pbi' ),
            'id'      => 'pbi_spinner_color',
            'type'    => 'colorpicker',
            'default' => '#0078D4',
        ] );
    }

    public function copy_prevention_script(): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'powerbi_report_page_powerbi_settings' ) {
            return;
        }
        ?>
        <script>
        ( function () {
            [ 'pbi_client_secret', 'pbi_password' ].forEach( function ( id ) {
                var input = document.getElementById( id );
                if ( ! input ) { return; }
                function block( e ) {
                    e.preventDefault();
                    if ( window.getSelection ) { window.getSelection().removeAllRanges(); }
                }
                input.addEventListener( 'copy', block );
                input.addEventListener( 'cut',  block );
            } );
        } )();
        </script>
        <?php
    }

    public function get( string $key, string $default = '' ): string {
        $options = get_option( 'powerbi_settings', [] );
        return (string) ( $options[ $key ] ?? $default );
    }
}

// Self-register on plugins_loaded via the hook in report-viewer-for-pbi.php init(),
// but also instantiate here so the settings page registers regardless.
add_action( 'plugins_loaded', function () {
    new PowerBI_Settings();
}, 5 ); // priority 5 so it's available before init() at default 10
