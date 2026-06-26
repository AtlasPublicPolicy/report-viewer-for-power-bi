<?php
/**
 * Registers the powerbi_report custom post type and powerbi_category taxonomy.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'powerbi_register_post_type' );
add_action( 'init', 'powerbi_register_taxonomy' );

function powerbi_register_post_type(): void {
    register_post_type(
        'powerbi_report',
        [
            'labels' => [
                'name'               => __( 'Power BI Reports', 'report-viewer-for-power-bi' ),
                'singular_name'      => __( 'Power BI Report', 'report-viewer-for-power-bi' ),
                'add_new_item'       => __( 'Add New Report', 'report-viewer-for-power-bi' ),
                'edit_item'          => __( 'Edit Report', 'report-viewer-for-power-bi' ),
                'new_item'           => __( 'New Report', 'report-viewer-for-power-bi' ),
                'view_item'          => __( 'View Report', 'report-viewer-for-power-bi' ),
                'search_items'       => __( 'Search Reports', 'report-viewer-for-power-bi' ),
                'not_found'          => __( 'No reports found.', 'report-viewer-for-power-bi' ),
                'not_found_in_trash' => __( 'No reports found in trash.', 'report-viewer-for-power-bi' ),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-chart-bar',
            'supports'            => [ 'title' ],
            'taxonomies'          => [ 'powerbi_category' ],
            'show_in_rest'        => false, // managed via custom REST endpoint
            'rewrite'             => false,
        ]
    );
}

function powerbi_register_taxonomy(): void {
    register_taxonomy(
        'powerbi_category',
        'powerbi_report',
        [
            'labels' => [
                'name'          => __( 'Report Categories', 'report-viewer-for-power-bi' ),
                'singular_name' => __( 'Report Category', 'report-viewer-for-power-bi' ),
                'add_new_item'  => __( 'Add New Category', 'report-viewer-for-power-bi' ),
                'edit_item'     => __( 'Edit Category', 'report-viewer-for-power-bi' ),
                'search_items'  => __( 'Search Categories', 'report-viewer-for-power-bi' ),
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => false,
        ]
    );
}
