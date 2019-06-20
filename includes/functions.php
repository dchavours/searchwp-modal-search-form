<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load all available templates and their labels.
 */
function searchwp_modal_form_get_templates() {
	$templates    = array();
	$template_dir = apply_filters( 'searchwp_modal_form_template_dir', 'searchwp-modal-form' );

	// Scan all applicable directories for template files.
	$template_files = array_merge(
		glob( trailingslashit( SEARCHWP_MODAL_FORM_DIR ) . 'templates/*.[pP][hH][pP]' ), // Plugin.
		glob( trailingslashit( get_stylesheet_directory() ) . trailingslashit( $template_dir ) . '*.[pP][hH][pP]' ), // Child Theme.
		glob( trailingslashit( get_template_directory() ) . trailingslashit( $template_dir ) . '*.[pP][hH][pP]' ) // Parent Theme.
	);

	// Scan all files for required 'header' data.
	foreach ( $template_files as $key => $template_file ) {
		$data = searchwp_modal_form_get_template_data( $template_file );
		if ( ! empty( $data['template_label'] ) ) {
			$templates[] = array(
				'file'  => $template_file,
				'label' => $data['template_label'],
			);
		}
	}

	return $templates;
}

/**
 * Retrieve file data from file path.
 *
 * @return array
 */
function searchwp_modal_form_get_template_data( $template ) {
	include_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	if ( ! file_exists( $template ) ) {
		return array();
	}

	return get_file_data( $template, array(
		'template_label' => 'SearchWP Modal Form Name',
	) );
}

/**
 * Generates a map of available modals which are defined by all combinations
 * of search engines and modal templates (which are file-based for the time being)
 */
function searchwp_get_modal_forms() {
	// If SearchWP is NOT available, we've only got one engine to work with.
	// We're going to mimic SearchWP's settings structure and then override.
	$engines = array(
		'wp_native' => array(
			// We're again mimicking the SearchWP storage here.
			'searchwp_engine_label' => __( 'Native WordPress', 'searchwpmodalform' ),
		),
	);

	// Override if SearchWP is active.
	if ( function_exists( 'SWP' ) ) {
		$engines = SWP()->settings['engines'];
	}

	// Retrieve all available templates.
	$templates = searchwp_modal_form_get_templates();

	// Storage for our forms map.
	$forms = array();

	foreach ( $engines as $engine_name => $engine_settings ) {

		$engine_label = isset( $engine_settings['searchwp_engine_label'] )
				? $engine_settings['searchwp_engine_label']
				: __( 'Default', 'searchwp' );

		foreach ( $templates as $template ) {
			// Build form name based on combination of engine name and relative template path.
			$form_name = $engine_name . '-' . str_replace( ABSPATH, '', $template['file'] );

			$hash = md5( $form_name . $engine_name );

			$forms[ $hash ] = array(
				'name'           => $hash,
				'template_file'  => $template['file'],
				'template_label' => $template['label'],
				'engine_name'    => $engine_name,
				'engine_label'   => $engine_label,
			);
		}
	}

	$forms = apply_filters( 'searchwp_modal_search_form_refs', $forms );

	return $forms;
}

/**
 * Extracts the modal name from an existing URI.
 */
function searchwp_get_modal_name_from_uri( $uri ) {
	$name = '#searchwp-modal-' === substr( $uri, 0, 16 ) ? substr( $uri, 16 ) : false;

	return $name;
}

/**
 * Determine whether the provided menu item is one of ours.
 */
function searchwp_get_modal_name_from_menu_item( $menu_item ) {
	if ( 'custom' !== $menu_item->type ) {
		return '';
	}

	$modal_name = searchwp_get_modal_name_from_uri( $menu_item->url );

	if ( ! $modal_name ) {
		return '';
	}

	return $modal_name;
}
