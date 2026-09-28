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

    private const SENSITIVE_KEYS = [ 'pbi_client_secret', 'pbi_password' ];

    public function __construct() {
        add_action( 'cmb2_admin_init', [ $this, 'register' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_copy_prevention' ] );
        add_action( 'admin_init', [ $this, 'maybe_migrate_credentials' ] );

        // Flush the cached Azure AD token when credentials are changed.
        add_action( 'update_option_powerbi_settings', function () {
            delete_transient( 'rvpbi_access_token' );
        } );
    }

    public function register(): void {
        $cmb = new_cmb2_box( [
            'id'           => 'powerbi_settings_page',
            'title'        => __( 'Power BI Settings', 'report-viewer-for-power-bi' ),
            'object_types' => [ 'options-page' ],
            'option_key'   => 'powerbi_settings',
            'parent_slug'  => 'edit.php?post_type=powerbi_report',
            'capability'   => 'manage_options',
            'menu_title'   => __( 'Settings', 'report-viewer-for-power-bi' ),
        ] );

        $cmb->add_field( [
            'name' => __( 'Client ID', 'report-viewer-for-power-bi' ),
            'desc' => __( 'The application (client) ID of your Azure AD app registration.', 'report-viewer-for-power-bi' ),
            'id'   => 'pbi_client_id',
            'type' => 'text',
        ] );

        $cmb->add_field( [
            'name'            => __( 'Client Secret', 'report-viewer-for-power-bi' ),
            'desc'            => __( 'The client secret value from your Azure AD app registration.', 'report-viewer-for-power-bi' ),
            'id'              => 'pbi_client_secret',
            'type'            => 'text',
            'attributes'      => [
                'type'         => 'password',
                'autocomplete' => 'new-password',
            ],
            'sanitization_cb' => [ $this, 'encrypt_field' ],
        ] );

        $cmb->add_field( [
            'name' => __( 'Master User (UPN)', 'report-viewer-for-power-bi' ),
            'desc' => __( 'Service account email address (ROPC flow). Requires MFA disabled on this account.', 'report-viewer-for-power-bi' ),
            'id'   => 'pbi_username',
            'type' => 'text',
        ] );

        $cmb->add_field( [
            'name'            => __( 'Master User Password', 'report-viewer-for-power-bi' ),
            'desc'            => __( 'Service account password (ROPC flow).', 'report-viewer-for-power-bi' ),
            'id'              => 'pbi_password',
            'type'            => 'text',
            'attributes'      => [
                'type'         => 'password',
                'autocomplete' => 'new-password',
            ],
            'sanitization_cb' => [ $this, 'encrypt_field' ],
        ] );

        $cmb->add_field( [
            'name' => __( 'Display Status', 'report-viewer-for-power-bi' ),
            'desc' => __( 'Show loading and error messages to end users while the report is fetching.', 'report-viewer-for-power-bi' ),
            'id'   => 'pbi_display_status',
            'type' => 'checkbox',
        ] );

        $cmb->add_field( [
            'name'    => __( 'Loading Spinner', 'report-viewer-for-power-bi' ),
            'desc'    => __( 'Spinner style shown while the report is loading. <a href="https://www.davidhu.io/react-spinners/" target="_blank" rel="noopener">Preview all spinners</a>.', 'report-viewer-for-power-bi' ),
            'id'      => 'pbi_spinner_type',
            'type'    => 'select',
            'default' => 'clip',
            'options' => [
                'bar'       => __( 'Bar', 'report-viewer-for-power-bi' ),
                'beat'      => __( 'Beat', 'report-viewer-for-power-bi' ),
                'bounce'    => __( 'Bounce', 'report-viewer-for-power-bi' ),
                'circle'    => __( 'Circle', 'report-viewer-for-power-bi' ),
                'clip'      => __( 'Clip', 'report-viewer-for-power-bi' ),
                'clock'     => __( 'Clock', 'report-viewer-for-power-bi' ),
                'dot'       => __( 'Dot', 'report-viewer-for-power-bi' ),
                'fade'      => __( 'Fade', 'report-viewer-for-power-bi' ),
                'grid'      => __( 'Grid', 'report-viewer-for-power-bi' ),
                'hash'      => __( 'Hash', 'report-viewer-for-power-bi' ),
                'moon'      => __( 'Moon', 'report-viewer-for-power-bi' ),
                'pacman'    => __( 'Pacman', 'report-viewer-for-power-bi' ),
                'propagate' => __( 'Propagate', 'report-viewer-for-power-bi' ),
                'puff'      => __( 'Puff', 'report-viewer-for-power-bi' ),
                'pulse'     => __( 'Pulse', 'report-viewer-for-power-bi' ),
                'ring'      => __( 'Ring', 'report-viewer-for-power-bi' ),
                'rise'      => __( 'Rise', 'report-viewer-for-power-bi' ),
                'rotate'    => __( 'Rotate', 'report-viewer-for-power-bi' ),
                'scale'     => __( 'Scale', 'report-viewer-for-power-bi' ),
                'skew'      => __( 'Skew', 'report-viewer-for-power-bi' ),
                'square'    => __( 'Square', 'report-viewer-for-power-bi' ),
                'sync'      => __( 'Sync', 'report-viewer-for-power-bi' ),
            ],
        ] );

        $cmb->add_field( [
            'name'    => __( 'Spinner Color', 'report-viewer-for-power-bi' ),
            'desc'    => __( 'Color of the loading spinner.', 'report-viewer-for-power-bi' ),
            'id'      => 'pbi_spinner_color',
            'type'    => 'colorpicker',
            'default' => '#0078D4',
        ] );
    }

    public function enqueue_copy_prevention(): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'powerbi_report_page_powerbi_settings' ) {
            return;
        }

        $js = "( function () {
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
} )();";

        wp_register_script( 'rvpbi-settings', false, [], RVPBI_VERSION, true );
        wp_enqueue_script( 'rvpbi-settings' );
        wp_add_inline_script( 'rvpbi-settings', $js );
    }

    public function get( string $key, string $default = '' ): string {
        $options = get_option( 'powerbi_settings', [] );
        $value   = (string) ( $options[ $key ] ?? $default );

        if ( in_array( $key, self::SENSITIVE_KEYS, true ) && RVPBI_Crypto::is_encrypted( $value ) ) {
            $value = RVPBI_Crypto::decrypt( $value );
        }

        return $value;
    }

    /**
     * CMB2 sanitization callback — encrypts sensitive field values before storage.
     *
     * @param mixed $value      The field value.
     * @param array $field_args The field arguments.
     * @param \CMB2_Field $field The field object.
     * @return string
     */
    public function encrypt_field( $value, $field_args, $field ): string {
        $value = (string) $value;

        if ( $value === '' ) {
            return '';
        }

        // Don't double-encrypt on re-save without changes.
        if ( RVPBI_Crypto::is_encrypted( $value ) ) {
            return $value;
        }

        return RVPBI_Crypto::encrypt( $value );
    }

    /**
     * One-time migration: encrypts any existing plaintext credentials.
     */
    public function maybe_migrate_credentials(): void {
        $options = get_option( 'powerbi_settings', [] );

        if ( ! empty( $options['_credentials_encrypted'] ) ) {
            return;
        }

        // Don't set the flag if OpenSSL isn't available — allow retry after PHP upgrade.
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            return;
        }

        foreach ( self::SENSITIVE_KEYS as $key ) {
            if ( ! empty( $options[ $key ] ) && ! RVPBI_Crypto::is_encrypted( $options[ $key ] ) ) {
                $encrypted = RVPBI_Crypto::encrypt( $options[ $key ] );
                if ( RVPBI_Crypto::is_encrypted( $encrypted ) ) {
                    $options[ $key ] = $encrypted;
                }
            }
        }

        $options['_credentials_encrypted'] = true;
        update_option( 'powerbi_settings', $options );
    }
}

// Self-register on plugins_loaded via the hook in report-viewer-for-power-bi.php init(),
// but also instantiate here so the settings page registers regardless.
add_action( 'plugins_loaded', function () {
    new PowerBI_Settings();
}, 5 ); // priority 5 so it's available before init() at default 10
