<?php

/**
 * Re-usable Component Class
 * 
 * @since 0.2.3
 */

Class OXYGEN_VSB_Reusable extends OXYGEN_VSB_Component {

	var $options;

	function __construct( $options ) {

		// run initialization
		$this->init( $options );
		
		// Add shortcodes
		add_shortcode( $this->options['tag'], array( $this, 'add_shortcode' ) );

		// remove component button
		remove_action("oxygen_vsb_toolbar_fundamentals_list", array( $this, "component_button" ) );
	}


	/**
	 * Add a [ct_reusable] shortcode to WordPress
	 *
	 * @since 0.1
	 */

	function add_shortcode( $atts, $content, $name ) {
		if ( ! $this->validate_shortcode( $atts, $content, $name ) ) {
			return '';
		}

		ob_start();	

		$options 	= json_decode( $atts["ct_options"], true ); 
		
		$view_id 	= $options["view_id"];
		$view 		= get_post( $view_id );
		
		/* New Way */
		$shortcodes = get_post_meta( $view->ID, "ct_builder_shortcodes", true );
		$content 	= do_shortcode( $shortcodes );
		
		echo $content;

		return ob_get_clean();
	}
}


// Create toolbar inctances
$button = new OXYGEN_VSB_Reusable ( 

		array( 
			'name' 		=> 'Reusable',
			'tag' 		=> 'ct_reusable',
			'advanced'  => array(
				'allow_shortcodes'  => true,
			)
		)
);

?>