<?php 

Class OXYGEN_VSB_Selector extends OXYGEN_VSB_Component {

	function __construct( $options ) {

		// run initialization
		$this->init( $options );

		// remove component button
		remove_action("oxygen_vsb_toolbar_fundamentals_list", array( $this, "component_button" ) );
	}
}


// Create inctance
$selector = new OXYGEN_VSB_Selector( array( 
			'name' 		=> 'Selector',
			'tag' 		=> 'ct_selector'
			)
		);