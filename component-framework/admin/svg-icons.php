<?php

/**
 * Callback to show "SVG Sets" on settings page
 *
 * @since 0.2.1
 */

function oxygen_vsb_svg_sets_callback() {
	
	$svg_sets = get_option("ct_svg_sets", array() );

	if ( empty( $svg_sets ) ) {
		Oxygen_VSB_Base::oxygen_vsb_load_default_svg_sets();
	}

	require('views/svg-sets-page.php');
}