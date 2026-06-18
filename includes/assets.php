<?php
/**
 * Asset registration — handles both Vite dev server and production dist.
 */

defined( 'ABSPATH' ) || exit;

class RVPBI_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        add_filter( 'script_loader_tag', [ $this, 'add_module_type' ], 10, 2 );
        // Strip any ?ver= query string WordPress adds to Vite dev URLs — esbuild
        // uses the file extension to detect the loader, and a query string breaks that.
        add_filter( 'script_loader_src', [ $this, 'strip_ver_from_vite_dev_urls' ], 10, 2 );
    }

    public function enqueue(): void {
        if ( RVPBI_DEV_MODE ) {
            $dev = RVPBI_VITE_DEV_URL;
            // The strip_ver_from_vite_dev_urls filter removes ?ver= before the URL is output,
            // so passing RVPBI_VERSION here satisfies the plugin checker without breaking esbuild.
            wp_enqueue_script( 'rvpbi-vite-client', $dev . '/@vite/client', [], RVPBI_VERSION, false );
            wp_enqueue_script( 'rvpbi-app', $dev . '/src/main.tsx', [ 'rvpbi-vite-client' ], RVPBI_VERSION, false );
        } else {
            // Production: load manifest-driven assets from /react-app/dist/
            $manifest_path = RVPBI_PATH . 'react-app/dist/.vite/manifest.json';

            if ( ! file_exists( $manifest_path ) ) {
                return; // dist not built yet
            }

            $manifest = json_decode( file_get_contents( $manifest_path ), true );
            $entry    = $manifest['index.html'] ?? $manifest['src/main.tsx'] ?? null;

            if ( ! $entry ) {
                return;
            }

            $dist_url = RVPBI_URL . 'react-app/dist/';

            wp_enqueue_script(
                'rvpbi-app',
                $dist_url . $entry['file'],
                [],
                RVPBI_VERSION,
                true
            );

            // Enqueue CSS chunks if present.
            if ( ! empty( $entry['css'] ) ) {
                foreach ( $entry['css'] as $i => $css_file ) {
                    wp_enqueue_style(
                        'rvpbi-style-' . $i,
                        $dist_url . $css_file,
                        [],
                        RVPBI_VERSION
                    );
                }
            }
        }

        $this->localize();
    }

    public function strip_ver_from_vite_dev_urls( string $src, string $handle ): string {
        if ( RVPBI_DEV_MODE && in_array( $handle, [ 'rvpbi-app', 'rvpbi-vite-client' ], true ) ) {
            return remove_query_arg( 'ver', $src );
        }
        return $src;
    }

    // Vite builds ES modules — WordPress doesn't add type="module" by default.
    public function add_module_type( string $tag, string $handle ): string {
        if ( in_array( $handle, [ 'rvpbi-app', 'rvpbi-vite-client' ], true ) ) {
            return str_replace( '<script ', '<script type="module" ', $tag );
        }
        return $tag;
    }

    /**
     * Pass PHP data to the React app via wp_localize_script.
     */
    private function localize(): void {
        wp_localize_script( 'rvpbi-app', 'ReportViewerPBI', $this->js_data() );
    }

    private function js_data(): array {
        $pbi_options = get_option( 'powerbi_settings', [] );

        return [
            'restUrl'              => rest_url(),
            'nonce'                => wp_create_nonce( 'wp_rest' ),
            'powerbiDisplayStatus' => ! empty( $pbi_options['pbi_display_status'] ),
            'spinnerType'          => $pbi_options['pbi_spinner_type']  ?? 'clip',
            'spinnerColor'         => $pbi_options['pbi_spinner_color'] ?? '#0078D4',
        ];
    }
}
