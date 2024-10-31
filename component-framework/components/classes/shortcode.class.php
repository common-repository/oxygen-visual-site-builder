<?php 

/**
 * oEmbed Class
 *
 * @since 0.1.7
 */

Class OXYGEN_VSB_Shortcode extends OXYGEN_VSB_Component {

	function __construct( $options ) {

		// run initialization
		$this->init( $options );
		
		// change button place
		remove_action("oxygen_vsb_toolbar_fundamentals_list", array( $this, "component_button" ) );
		add_action("oxygen_vsb_folder_component_shortcode", array( $this, "component_button" ) );

	}

	/**
	 * Add WordPress folder button
	 *
	 * @since 0.4.0
	 * @author Ilya K.
	 */
	
	function component_button() { ?>

		<div class="ct-add-component-button"
			ng-click="addComponent('<?php echo esc_attr($this->options['tag']); ?>','shortcode')">
			<div class="ct-add-component-icon">
				<span class="ct-icon <?php echo esc_attr($this->options['tag']); ?>-icon"></span>
			</div>
			<?php echo esc_html($this->options['name']); ?>
		</div>

	<?php }

}

$button = new OXYGEN_VSB_Shortcode ( array( 
		'name' 		=> 'Shortcode',
		'tag' 		=> 'ct_shortcode',
		'shortcode'	=> true,
		'params' 	=> array(
							/*array(
								"param_name" 	=> "shortcode_tag",
								"value" 		=> "shortcode",
								"type" 			=> "textfield",
								"heading" 		=> __("Tag","component-theme"),
								"class" 		=> "ct-textbox-big",
								"css" 			=> false,
							),
							array(
								"param_name" 	=> "id",
								"value" 		=> "",
								"type" 			=> "textfield",
								"heading" 		=> __("ID","component-theme"),
								"class" 		=> "ct-textbox-small",
								"css" 			=> false,
							),*/
							array(
								"param_name" 	=> "full_shortcode",
								"value" 		=> "",
								"type" 			=> "textfield",
								"heading" 		=> __("Full shortcode","component-theme"),
								"class" 		=> "ct-textbox-huge",
								"css" 			=> false,
							),
							array(
								"type" 			=> "tag",
								"heading" 		=> __("Tag"),
								"param_name" 	=> "tag",
								"value" 		=> array (
													"div" => "DIV",
													"p" => "P",
													"h1" => "H1",
													"h2" => "H2",
													"h3" => "H3",
													"h4" => "H4",
													"h5" => "H5",
													"h6" => "H6",
												),
								"css" 			=> false,
							)
						),
		'advanced' => false
		)
	);
