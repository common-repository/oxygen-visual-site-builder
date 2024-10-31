<?php

/**
 * Callback to show "Import/Export" on settings page
 *
 * @since 0.2.3
 */

function oxygen_vsb_export_import_callback() {

	// get saved options
	$classes 			= get_option("ct_components_classes", array() );
	$custom_selectors 	= get_option("ct_custom_selectors", array() );
	$style_sheets 	= get_option("ct_style_sheets", array() );

	if (!is_array($classes)) {
		$classes = array();
	}

	if (!is_array($custom_selectors)) {
		$custom_selectors = array();
	}

	if (!is_array($style_sheets)) {
		$style_sheets = array();
	}

	// generate export JSON
	$export_json['classes'] 			= $classes;
  	$export_json['custom_selectors'] 	= $custom_selectors;
  	$export_json['style_sheets'] 	= $style_sheets;

  	// generate JSON object
	$export_json = json_encode( $export_json );	

	require('views/import-export-page.php');
}
