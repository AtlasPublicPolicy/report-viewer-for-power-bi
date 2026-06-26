<?php
/**
 * Power BI token provider — ROPC (Master User) auth flow, AAD token type.
 *
 * Flow:
 *   1. Exchange credentials for an Azure AD access token via ROPC.
 *   2. Construct the embed URL from the report/group IDs.
 *   3. Return the AAD token + embed URL directly (TokenType.Aad on the client).
 *
 * This approach uses the master user's Pro license for rendering, which avoids
 * the need for Premium/Embedded capacity and the associated "free trial" banner
 * that appears when using TokenType.Embed without Premium.
 */

defined( 'ABSPATH' ) || exit;

class PowerBI_Token_Provider {

    private string $client_id;
    private string $client_secret;
    private string $username;
    private string $password;

    public function __construct( PowerBI_Settings $settings ) {
        $this->client_id     = $settings->get( 'pbi_client_id' );
        $this->client_secret = $settings->get( 'pbi_client_secret' );
        $this->username      = $settings->get( 'pbi_username' );
        $this->password      = $settings->get( 'pbi_password' );
    }

    /**
     * Returns the embed config needed by powerbi-client-react.
     *
     * @param string $group_id   Power BI workspace GUID.
     * @param string $report_id  Power BI report GUID.
     * @param string $embed_type 'report' | 'dashboard'.
     * @return array|WP_Error
     */
    public function get_embed_config( string $group_id, string $report_id, string $embed_type = 'report' ): array|WP_Error {
        $access_token = $this->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        // Construct the embed URL directly — no additional Power BI REST API call
        // needed when using TokenType.Aad on the client.
        if ( $embed_type === 'dashboard' ) {
            $embed_url = 'https://app.powerbi.com/dashboardEmbed?dashboardId=' . rawurlencode( $report_id ) . '&groupId=' . rawurlencode( $group_id );
        } else {
            $embed_url = 'https://app.powerbi.com/reportEmbed?reportId=' . rawurlencode( $report_id ) . '&groupId=' . rawurlencode( $group_id );
        }

        return [
            'embedUrl'    => $embed_url,
            'accessToken' => $access_token,
            'reportId'    => $report_id,
            'embedType'   => $embed_type,
        ];
    }

    /**
     * Gets an Azure AD access token via the ROPC (password) grant.
     *
     * @return string|WP_Error
     */
    private function get_access_token(): string|WP_Error {
        // /common/ endpoint resolves the tenant from the username (UPN),
        // so no tenant ID is needed for the ROPC flow.
        $response = wp_remote_post(
            'https://login.microsoftonline.com/common/oauth2/token',
            [
                'body' => [
                    'grant_type'    => 'password',
                    'client_id'     => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'username'      => $this->username,
                    'password'      => $this->password,
                    'resource'      => 'https://analysis.windows.net/powerbi/api',
                ],
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['access_token'] ) ) {
            $message = $body['error_description'] ?? __( 'Failed to retrieve Azure AD access token.', 'report-viewer-for-power-bi' );
            return new WP_Error( 'powerbi_auth_failed', $message );
        }

        return $body['access_token'];
    }
}
