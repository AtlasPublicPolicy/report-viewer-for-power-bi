<?php
/**
 * Export script: builds a minimal distributable ZIP.
 * Run via: composer run build
 *         or directly: php scripts/export.php
 *
 * The ZIP will contain only what WordPress needs (no node_modules, no src/).
 */

$root      = dirname( __DIR__ );
$dist_dir  = $root . '/react-app/dist';
$zip_path  = dirname( $root ) . '/report-viewer-for-power-bi.zip';  // one level above plugin root
$slug      = 'report-viewer-for-power-bi';

if ( ! is_dir( $dist_dir ) ) {
    echo "ERROR: react-app/dist not found. Run `composer run build:react` first.\n";
    exit( 1 );
}

// Files/dirs to include (relative to plugin root)
$include = [
    'report-viewer-for-power-bi.php',
    'uninstall.php',
    'readme.txt',
    'composer.json',
    'includes/',
    'vendor/',
    'react-app/dist/',
];

// Remove old zip
if ( file_exists( $zip_path ) ) {
    unlink( $zip_path );
}

$zip = new ZipArchive();
if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== true ) {
    echo "ERROR: Cannot create zip at {$zip_path}\n";
    exit( 1 );
}

foreach ( $include as $item ) {
    $full = $root . '/' . $item;

    if ( is_file( $full ) ) {
        // Force dev mode off in the main plugin file so the ZIP always ships production-ready.
        if ( $item === 'report-viewer-for-power-bi.php' ) {
            $contents = file_get_contents( $full );
            $contents = preg_replace(
                "/define\(\s*'RVPBI_DEV_MODE'\s*,\s*(true|false)\s*\)/",
                "define( 'RVPBI_DEV_MODE', false )",
                $contents
            );
            $zip->addFromString( $slug . '/' . $item, $contents );
        } else {
            $zip->addFile( $full, $slug . '/' . $item );
        }
        echo "  + {$item}\n";
    } elseif ( is_dir( $full ) ) {
        add_dir_to_zip( $zip, $full, $root, $slug );
    } else {
        echo "  WARNING: {$item} not found, skipping.\n";
    }
}

$zip->close();
echo "\nZIP created: {$zip_path}\n";

// ---------------------------------------------------------------------------

function add_dir_to_zip( ZipArchive $zip, string $dir, string $root, string $slug ): void {
    // Dirs to always skip (.vite removed — it contains manifest.json which production needs)
    $skip = [ 'node_modules', '.git' ];

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ( $iter as $file ) {
        // Normalize to forward slashes so ZIP entries are cross-platform
        $pathname       = str_replace( '\\', '/', $file->getPathname() );
        $root_norm      = str_replace( '\\', '/', $root );
        $relative       = ltrim( str_replace( $root_norm, '', $pathname ), '/' );

        // Skip unwanted directories
        foreach ( $skip as $s ) {
            if ( strpos( $relative, '/' . $s . '/' ) !== false
                 || substr( $relative, -( strlen( $s ) + 1 ) ) === '/' . $s ) {
                continue 2;
            }
        }

        if ( $file->isDir() ) {
            $zip->addEmptyDir( $slug . '/' . $relative );
        } else {
            $zip->addFile( $file->getPathname(), $slug . '/' . $relative );
            echo "  + {$relative}\n";
        }
    }
}
