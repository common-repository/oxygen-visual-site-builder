<?php 

Class OXYGEN_VSB_Inner_Content extends OXYGEN_VSB_Component {

	var $shortcode_options;
	var $shortcode_atts;

	function __construct( $options ) {

		// run initialization
		$this->init( $options );
		
		// add shortcodes
		add_shortcode( $this->options['tag'], array( $this, 'add_shortcode' ) );

		for ( $i = 2; $i <= 16; $i++ ) {
			add_shortcode( $this->options['tag'] . "_" . $i, array( $this, 'add_shortcode' ) );
		}
	}


	/**
	 * Add a [ct_inner_content] shortcode to WordPress
	 *
	 * @since 1.2.0
	 */

	function add_shortcode( $atts, $content, $name ) {
		if ( ! $this->validate_shortcode( $atts, $content, $name ) ) {
			return '';
		}

		$options = $this->set_options( $atts );

		$post_id = get_the_ID();

		ob_start();

		echo "<div id='" . esc_attr( $options['selector'] ) . " class='" . esc_attr( $options['classes'] ) . ">";

		$ct_use_inner_content = get_post_meta( $post_id, 'ct_use_inner_content', true );
		if ( ! $ct_use_inner_content || $ct_use_inner_content == 'content' ) {
		    // Use WordPress post content as inner content
            while ( have_posts() ) {
                the_post();
                the_content();
            }
        } else {
		    // Use Oxygen designed inner content
            $content .= get_post_meta( $post_id, 'ct_builder_shortcodes', true );
        }

        if ( ! empty( $content ) ) {
	        echo do_shortcode( $content );
        }

        echo "</div>";

		return ob_get_clean();
	}

	/**
	 * Add a toolbar button
	 *
	 * @since 0.1
	 */
	function component_button() { 

		$post_type = get_post_type();
		
		if ( $post_type != "ct_template") {
			return;
		} ?>

		<div class="ct-add-component-button"
			ng-click="addComponent('<?php echo esc_attr( $this->options['tag'] ); ?>')">
			<div class="ct-add-component-icon">
				<span class="ct-icon <?php echo esc_attr( $this->options['tag'] ); ?>-icon"></span>
			</div>
			<?php echo esc_html($this->options['name']); ?>
		</div>


	<?php }
}




// Create instance
$html = new OXYGEN_VSB_Inner_Content( array( 
			'name' 		=> 'Inner Content',
			'tag' 		=> 'ct_inner_content',			
			'advanced' 	=> array(
			        'allow_shortcodes' => true,
                )
			)
		);