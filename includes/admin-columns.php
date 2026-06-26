<?php
/**
 * Adds a "Shortcode" column to the powerbi_report admin list table.
 *
 * Each row displays the ready-to-paste shortcode and a one-click Copy button.
 */

defined( 'ABSPATH' ) || exit;

class PowerBI_Admin_Columns {

    public function __construct() {
        add_filter( 'manage_powerbi_report_posts_columns',        [ $this, 'add_column' ] );
        add_action( 'manage_powerbi_report_posts_custom_column',  [ $this, 'render_column' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',                      [ $this, 'enqueue_assets' ] );
    }

    public function add_column( array $columns ): array {
        $columns['pbi_shortcode'] = __( 'Shortcode', 'report-viewer-for-power-bi' );
        return $columns;
    }

    public function render_column( string $column, int $post_id ): void {
        if ( $column !== 'pbi_shortcode' ) {
            return;
        }

        $shortcode = '[powerbi_report id="' . $post_id . '"]';

        printf(
            '<code>%s</code> <button type="button" class="button button-small pbi-copy-sc" data-sc="%s">%s</button>',
            esc_html( $shortcode ),
            esc_attr( $shortcode ),
            esc_html__( 'Copy', 'report-viewer-for-power-bi' )
        );
    }

    public function enqueue_assets(): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-powerbi_report' ) {
            return;
        }

        $css = '.column-pbi_shortcode { width: 260px; }
.column-pbi_shortcode code { display: inline-block; margin-right: 6px; }';

        wp_register_style( 'rvpbi-admin-columns', false, [], RVPBI_VERSION );
        wp_enqueue_style( 'rvpbi-admin-columns' );
        wp_add_inline_style( 'rvpbi-admin-columns', $css );

        $copied = esc_js( __( '🗸', 'report-viewer-for-power-bi' ) );
        $copy   = esc_js( __( 'Copy', 'report-viewer-for-power-bi' ) );

        $js = "document.addEventListener( 'click', function ( e ) {
    if ( ! e.target.classList.contains( 'pbi-copy-sc' ) ) return;
    var btn = e.target;
    navigator.clipboard.writeText( btn.dataset.sc ).then( function () {
        btn.textContent = '{$copied}';
        setTimeout( function () { btn.textContent = '{$copy}'; }, 1500 );
    } );
} );";

        wp_register_script( 'rvpbi-admin-columns', false, [], RVPBI_VERSION, true );
        wp_enqueue_script( 'rvpbi-admin-columns' );
        wp_add_inline_script( 'rvpbi-admin-columns', $js );
    }
}
