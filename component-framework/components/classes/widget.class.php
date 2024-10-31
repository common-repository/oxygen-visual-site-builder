<?php 

Class OXYGEN_VSB_Widget extends OXYGEN_VSB_Component {

	function __construct( $options ) {

		// run initialization
		$this->init( $options );

		// remove component button
		remove_action("oxygen_vsb_toolbar_fundamentals_list", array( $this, "component_button" ) );
		
		// add toolbar
		add_action("oxygen_vsb_toolbar_widgets_folder", 	array( $this, "widgets_list") );

	}


	/**
	 * Display all widgets
	 *
	 * @since  0.2.3
	 */

	function widgets_list() {
		
		foreach ( $GLOBALS['wp_widget_factory']->widgets as $class => $widget ) {

			?>

			<div class="ct-add-component-button" title="<?php echo esc_html( $widget->widget_options['description'] ); ?>"
				ng-click="addWidget('<?php echo esc_attr( $class ); ?>','<?php echo esc_attr( $widget->id_base ); ?>', '<?php echo esc_attr( $widget->name ); ?>')">
				<div class="ct-add-component-icon">
					<span class="ct-icon <?php echo esc_attr( $this->options['tag'] ); ?>-icon"></span>
				</div>
				<?php echo esc_html( $widget->name ); ?>
			</div>

			<?php 
		}
	}
}


// Create inctance
$widget = new OXYGEN_VSB_Widget( array( 
			'name' 		=> 'Widget',
			'tag' 		=> 'ct_widget',
			'params' 	=> array(
					array(
						"type" 			=> "textfield",
						"param_name" 	=> "class_name",
						"hidden" 		=> true,
						"css" 			=> false,
					),
					array(
						"type" 			=> "textfield",
						"param_name" 	=> "id_base",
						"hidden" 		=> true,
						"css" 			=> false,
					),
					array(
						"type" 			=> "textfield",
						"param_name" 	=> "instance",
						"hidden" 		=> true,
						"css" 			=> false,
					),
				),
			'advanced' => false
			)
		); 