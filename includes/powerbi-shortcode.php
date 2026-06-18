<?php
/**
 * Shortcode [powerbi_report] — renders the React mount point for a specific report.
 *
 * Usage:
 *   [powerbi_report id="123"]
 *   [powerbi_report id="123" width="100%" height="800px"]
 *
 * Attributes:
 *   id     (required) — WP post ID of the powerbi_report post.
 *   width  (optional) — overrides the post's pbi_width meta value.
 *   height (optional) — overrides the post's pbi_height meta value.
 */

defined( 'ABSPATH' ) || exit;

class PowerBI_Shortcode {

    public function __construct() {
        add_shortcode( 'powerbi_report', [ $this, 'render' ] );
    }

    public function render( $atts ): string {
        $atts = shortcode_atts(
            [
                'id'     => '',
                'width'  => '',
                'height' => '',
            ],
            $atts,
            'powerbi_report'
        );

        $post_id = absint( $atts['id'] );

        if ( ! $post_id || get_post_type( $post_id ) !== 'powerbi_report' ) {
            return '';
        }

        // Shortcode atts override post meta; fall back to post meta, then defaults.
        $width  = $atts['width']  ?: ( get_post_meta( $post_id, 'pbi_width', true )  ?: '100%' );
        $height = $atts['height'] ?: ( get_post_meta( $post_id, 'pbi_height', true ) ?: '600px' );

        return sprintf(
            '<div id="powerbi-report-root" data-post-id="%d" data-width="%s" data-height="%s"></div>',
            $post_id,
            esc_attr( $width ),
            esc_attr( $height )
        );
    }
}
