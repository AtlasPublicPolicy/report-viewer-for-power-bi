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
        'title'        => __( 'Report Configuration', 'atlas-report-viewer-for-power-bi' ),
        'object_types' => [ 'powerbi_report' ],
        'context'      => 'normal',
        'priority'     => 'high',
    ] );

    // Power BI IDs
    $cmb->add_field( [
        'name'       => __( 'Report ID', 'atlas-report-viewer-for-power-bi' ),
        'desc'       => __( 'The Power BI report GUID.', 'atlas-report-viewer-for-power-bi' ),
        'id'         => 'pbi_report_id',
        'type'       => 'text',
        'attributes' => [ 'required' => 'required' ],
    ] );

    $cmb->add_field( [
        'name'       => __( 'Group / Workspace ID', 'atlas-report-viewer-for-power-bi' ),
        'desc'       => __( 'The Power BI workspace (group) GUID.', 'atlas-report-viewer-for-power-bi' ),
        'id'         => 'pbi_group_id',
        'type'       => 'text',
        'attributes' => [ 'required' => 'required' ],
    ] );

    // Embed type
    $cmb->add_field( [
        'name'    => __( 'Embed Type', 'atlas-report-viewer-for-power-bi' ),
        'id'      => 'pbi_embed_type',
        'type'    => 'select',
        'default' => 'report',
        'options' => [
            'report'    => __( 'Report', 'atlas-report-viewer-for-power-bi' ),
            'dashboard' => __( 'Dashboard', 'atlas-report-viewer-for-power-bi' ),
        ],
    ] );

    // Optional page name (for paginated reports)
    $cmb->add_field( [
        'name' => __( 'Page Name', 'atlas-report-viewer-for-power-bi' ),
        'desc' => __( 'Optional. Opens a specific page/tab within the report.', 'atlas-report-viewer-for-power-bi' ),
        'id'   => 'pbi_page_name',
        'type' => 'text',
    ] );

    // Display dimensions
    $cmb->add_field( [
        'name'    => __( 'Width', 'atlas-report-viewer-for-power-bi' ),
        'desc'    => __( 'CSS width value, e.g. 100% or 800px.', 'atlas-report-viewer-for-power-bi' ),
        'id'      => 'pbi_width',
        'type'    => 'text_small',
        'default' => '100%',
    ] );

    $cmb->add_field( [
        'name'    => __( 'Min Height', 'atlas-report-viewer-for-power-bi' ),
        'desc'    => __( 'Initial container height before the report loads (e.g. 600px). Once the report renders, the height automatically adjusts to match the report\'s aspect ratio.', 'atlas-report-viewer-for-power-bi' ),
        'id'      => 'pbi_height',
        'type'    => 'text_small',
        'default' => '600px',
    ] );

    // Filter pane
    $cmb->add_field( [
        'name'    => __( 'Show Filter Pane', 'atlas-report-viewer-for-power-bi' ),
        'desc'    => __( 'Display the Power BI filter pane alongside the report.', 'atlas-report-viewer-for-power-bi' ),
        'id'      => 'pbi_filter_pane',
        'type'    => 'select',
        'default' => '1',
        'options' => [
            '1' => __( 'Yes', 'atlas-report-viewer-for-power-bi' ),
            '0' => __( 'No', 'atlas-report-viewer-for-power-bi' ),
        ],
    ] );

    // Content restriction
    $cmb->add_field( [
        'name'    => __( 'Content Restriction', 'atlas-report-viewer-for-power-bi' ),
        'desc'    => __( 'Who can view the embedded report on the front end.', 'atlas-report-viewer-for-power-bi' ),
        'id'      => 'pbi_restriction',
        'type'    => 'select',
        'default' => 'public',
        'options' => [
            'public'        => __( 'Public — anyone', 'atlas-report-viewer-for-power-bi' ),
            'logged_in'     => __( 'Logged-in users only', 'atlas-report-viewer-for-power-bi' ),
            'administrator' => __( 'Administrators only', 'atlas-report-viewer-for-power-bi' ),
        ],
    ] );
}
