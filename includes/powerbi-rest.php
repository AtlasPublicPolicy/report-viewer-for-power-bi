<?php
/**
 * REST endpoint: GET /wp-json/report-viewer-for-pbi/v1/powerbi/embed?post_id={id}
 *
 * Returns the embed config (embedUrl, accessToken, reportId, embedType)
 * needed by powerbi-client-react. Token generation is performed server-side
 * so credentials never reach the client.
 *
 * Content restriction is enforced in the permission callback using the
 * pbi_restriction meta value of the requested post.
 *
 * Cache-Control headers are set aggressively to prevent caching plugins
 * from serving a stale or expired embed token.
 */

defined( 'ABSPATH' ) || exit;

class PowerBI_REST_Controller extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'report-viewer-for-pbi/v1';
        $this->rest_base = 'powerbi/embed';
    }

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_embed_config' ],
                    'permission_callback' => [ $this, 'check_permissions' ],
                    'args'                => [
                        'post_id' => [
                            'required'          => true,
                            'type'              => 'integer',
                            'minimum'           => 1,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => 'rest_validate_request_arg',
                            'description'       => __( 'WP post ID of the powerbi_report post.', 'report-viewer-for-pbi' ),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Enforces content restriction based on the post's pbi_restriction meta.
     */
    public function check_permissions( WP_REST_Request $request ): bool|WP_Error {
        $post_id = absint( $request->get_param( 'post_id' ) );

        if ( get_post_type( $post_id ) !== 'powerbi_report' ) {
            return new WP_Error(
                'rest_invalid_param',
                __( 'Invalid Power BI report ID.', 'report-viewer-for-pbi' ),
                [ 'status' => 400 ]
            );
        }

        $restriction = get_post_meta( $post_id, 'pbi_restriction', true ) ?: 'public';

        if ( $restriction === 'logged_in' && ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You must be logged in to view this report.', 'report-viewer-for-pbi' ),
                [ 'status' => 401 ]
            );
        }

        if ( $restriction === 'administrator' && ! current_user_can( 'manage_options' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to view this report.', 'report-viewer-for-pbi' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Generates and returns the Power BI embed config.
     */
    public function get_embed_config( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post_id    = absint( $request->get_param( 'post_id' ) );
        $report_id  = get_post_meta( $post_id, 'pbi_report_id', true );
        $group_id   = get_post_meta( $post_id, 'pbi_group_id', true );
        $embed_type = get_post_meta( $post_id, 'pbi_embed_type', true ) ?: 'report';
        $page_name  = get_post_meta( $post_id, 'pbi_page_name', true );

        if ( ! $report_id || ! $group_id ) {
            return new WP_Error(
                'powerbi_misconfigured',
                __( 'This report is not fully configured. Report ID and Group ID are required.', 'report-viewer-for-pbi' ),
                [ 'status' => 500 ]
            );
        }

        $settings = new PowerBI_Settings();
        $provider = new PowerBI_Token_Provider( $settings );
        $config   = $provider->get_embed_config( $group_id, $report_id, $embed_type );

        if ( is_wp_error( $config ) ) {
            return $config;
        }

        if ( $page_name ) {
            $config['pageName'] = $page_name;
        }

        $response = rest_ensure_response( $config );

        // Prevent caching plugins from storing the embed token.
        $response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
        $response->header( 'Pragma', 'no-cache' );

        return $response;
    }
}

add_action( 'rest_api_init', function () {
    $controller = new PowerBI_REST_Controller();
    $controller->register_routes();
} );
