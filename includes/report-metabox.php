<?php
/**
 * CMB2 meta boxes for the powerbi_report post type.
 *
 * Meta keys (all prefixed pbi_):
 *   pbi_report_id      — Power BI report GUID
 *   pbi_group_id       — Power BI workspace/group GUID
 *   pbi_embed_type     — 'report' | 'dashboard'
 *   pbi_page_name      — optional paginated report page name
 *   pbi_width          — CSS width (default: 100%)
 *   pbi_height         — CSS height (default: 600px)
 *   pbi_restriction    — 'public' | 'logged_in' | 'administrator'
 */

defined( 'ABSPATH' ) || exit;

add_action( 'cmb2_admin_init', 'powerbi_register_report_metabox' );

function powerbi_register_report_metabox(): void {
    $cmb = new_cmb2_box( [
        'id'           => 'powerbi_report_meta',
        'title'        => __( 'Report Configuration', 'report-viewer-for-pbi' ),
        'object_types' => [ 'powerbi_report' ],
        'context'      => 'normal',
        'priority'     => 'high',
    ] );

    // Power BI IDs
    $cmb->add_field( [
        'name'       => __( 'Report ID', 'report-viewer-for-pbi' ),
        'desc'       => __( 'The Power BI report GUID.', 'report-viewer-for-pbi' ),
        'id'         => 'pbi_report_id',
        'type'       => 'text',
        'attributes' => [ 'required' => 'required' ],
    ] );

    $cmb->add_field( [
        'name'       => __( 'Group / Workspace ID', 'report-viewer-for-pbi' ),
        'desc'       => __( 'The Power BI workspace (group) GUID.', 'report-viewer-for-pbi' ),
        'id'         => 'pbi_group_id',
        'type'       => 'text',
        'attributes' => [ 'required' => 'required' ],
    ] );

    // Embed type
    $cmb->add_field( [
        'name'    => __( 'Embed Type', 'report-viewer-for-pbi' ),
        'id'      => 'pbi_embed_type',
        'type'    => 'select',
        'default' => 'report',
        'options' => [
            'report'    => __( 'Report', 'report-viewer-for-pbi' ),
            'dashboard' => __( 'Dashboard', 'report-viewer-for-pbi' ),
        ],
    ] );

    // Optional page name (for paginated reports)
    $cmb->add_field( [
        'name' => __( 'Page Name', 'report-viewer-for-pbi' ),
        'desc' => __( 'Optional. Opens a specific page/tab within the report.', 'report-viewer-for-pbi' ),
        'id'   => 'pbi_page_name',
        'type' => 'text',
    ] );

    // Display dimensions
    $cmb->add_field( [
        'name'    => __( 'Width', 'report-viewer-for-pbi' ),
        'desc'    => __( 'CSS width value, e.g. 100% or 800px.', 'report-viewer-for-pbi' ),
        'id'      => 'pbi_width',
        'type'    => 'text_small',
        'default' => '100%',
    ] );

    $cmb->add_field( [
        'name'    => __( 'Min Height', 'report-viewer-for-pbi' ),
        'desc'    => __( 'Initial container height before the report loads (e.g. 600px). Once the report renders, the height automatically adjusts to match the report\'s aspect ratio.', 'report-viewer-for-pbi' ),
        'id'      => 'pbi_height',
        'type'    => 'text_small',
        'default' => '600px',
    ] );

    // Content restriction
    $cmb->add_field( [
        'name'    => __( 'Content Restriction', 'report-viewer-for-pbi' ),
        'desc'    => __( 'Who can view the embedded report on the front end.', 'report-viewer-for-pbi' ),
        'id'      => 'pbi_restriction',
        'type'    => 'select',
        'default' => 'public',
        'options' => [
            'public'        => __( 'Public — anyone', 'report-viewer-for-pbi' ),
            'logged_in'     => __( 'Logged-in users only', 'report-viewer-for-pbi' ),
            'administrator' => __( 'Administrators only', 'report-viewer-for-pbi' ),
        ],
    ] );
}
