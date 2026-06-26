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
        add_action( 'admin_head',                                 [ $this, 'inline_assets' ] );
    }

    public function add_column( array $columns ): array {
        $columns['pbi_shortcode'] = __( 'Shortcode', 'report-viewer-for-pbi' );
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
            esc_html__( 'Copy', 'report-viewer-for-pbi' )
        );
    }

    public function inline_assets(): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-powerbi_report' ) {
            return;
        }
        ?>
        <style>
            .column-pbi_shortcode { width: 260px; }
            .column-pbi_shortcode code { display: inline-block; margin-right: 6px; }
        </style>
        <script>
        document.addEventListener( 'click', function ( e ) {
            if ( ! e.target.classList.contains( 'pbi-copy-sc' ) ) return;
            var btn = e.target;
            navigator.clipboard.writeText( btn.dataset.sc ).then( function () {
                btn.textContent = '<?php echo esc_js( __( '🗸', 'report-viewer-for-pbi' ) ); ?>';
                setTimeout( function () {
                    btn.textContent = '<?php echo esc_js( __( 'Copy', 'report-viewer-for-pbi' ) ); ?>';
                }, 1500 );
            } );
        } );
        </script>
        <?php
    }
}
