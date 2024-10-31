<?php
if(!class_exists('Oxygen_VSB_Base')) {

	require_once("admin/cpt-templates.php");
	require_once("admin/admin.php");
	require_once("admin/pages.php");
	require_once("admin/svg-icons.php");
	require_once("admin/import-export.php");

	require_once("includes/ajax.php");
	require_once("includes/tree-shortcodes.php");
	require_once("includes/templates.php");

	// init media queries sizes
	global $media_queries_list;
	
	$media_queries_list = array (
		"default" 	=> array (
						"maxSize" 	=> "100%",
						"title" 	=> "All devices"
					),
		"tablet" 	=> array (
						"maxSize" 	=> '992px', 
						"title" 	=> "Less than 992px"
					),
		"phone-landscape" 
					=> array (
						"maxSize" 	=> '768px', 
						"title" 	=> "Less than 768px"
					),
		"phone-portrait"
					=> array (
						"maxSize" 	=> '480px', 
						"title" 	=> "Less than 480px"
					),
	);

	// Include Signature Class
	require_once("signature.class.php");

	// Include Component Class
	require_once("components/component.class.php");

	// Add components in certain order
	include_once("components/classes/section.class.php");
	include_once("components/classes/columns.class.php");
	include_once("components/classes/column.class.php");
	include_once("components/classes/div-block.class.php");
	include_once("components/classes/headline.class.php");
	include_once("components/classes/text-block.class.php");
	include_once("components/classes/paragraph.class.php");
	include_once("components/classes/link-text.class.php");
	include_once("components/classes/link-wrapper.class.php");
	include_once("components/classes/image.class.php");
	include_once("components/classes/svg-icon.class.php");
	include_once("components/classes/ul.class.php");
	include_once("components/classes/li.class.php");
	include_once("components/classes/code-block.class.php");
	include_once("components/classes/inner-content.class.php");

	// not shown in fundamentals
	include_once("components/classes/reusable.class.php");
	include_once("components/classes/selector.class.php");
	include_once("components/classes/separator.class.php");
	include_once("components/classes/shortcode.class.php");
	include_once("components/classes/span.class.php");
	include_once("components/classes/widget.class.php");


	do_action("oxygen_vsb_after_add_components");



	class Oxygen_VSB_Base {

		/**
		 * Run plugin setup
		 * 
		 * @since 0.3.3
		 * @author Ilya K.
		 */
		function oxygen_vsb_plugin_setup() {

			/**
			 * Setup default SVG Set
			 * 
			 */
			
			$svg_sets = get_option("ct_svg_sets", array() );

			if ( empty( $svg_sets ) ) {
				Oxygen_VSB_Base::oxygen_vsb_load_default_svg_sets();
			}
		}

		function oxygen_vsb_load_default_svg_sets() {
			$sets = array(
				"fontawesome" => "Font Awesome"
			);
			
			foreach ($sets as $key => $name) {
				
				// import default file	
				$file_content = file_get_contents( OXYGEN_VSB_FW_PATH . "/admin/includes/$key/symbol-defs.svg" );

				$xml = simplexml_load_string($file_content);

				foreach($xml->children() as $def) {
					if($def->getName() == 'defs') {

						foreach($def->children() as $symbol) {
							
							if($symbol->getName() == 'symbol') {
								$symbol['id'] = str_replace(' ', '', $name).$symbol['id'];
								
							}
						}
					}
					
				}
				$file_content = $xml->asXML();

				$svg_sets[$name] = $file_content;
			}

			// save SVG sets to DB
			update_option("ct_svg_sets", $svg_sets );
		}

		/**
		 * Echo all components styles in one <style>
		 * 
		 * @since 0.1.6
		 */

		function oxygen_vsb_footer_styles_hook() {
			
			ob_start();
			do_action("oxygen_vsb_footer_styles");
			$ct_footer_css = ob_get_clean();

			if ( defined("OXYGEN_VSB_SHOW_BUILDER") ) { 
				echo "<style type=\"text/css\" id=\"ct-footer-css\">\r\n";
				echo $ct_footer_css;
				echo "</style>\r\n";
			}
		}


		function oxygen_vsb_wp_link_dialog() {
		    require_once ABSPATH . "wp-includes/class-wp-editor.php";
			_WP_Editors::wp_link_dialog();
		}

		/**
		 * Check if we are in builder mode
		 * 
		 * @since 0.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_is_show_builder() {

			// check if builder activated
		    if ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] ) {

				if ( !is_user_logged_in() || !current_user_can( 'manage_options' )) { 
				   auth_redirect();
				}
				
				if ( !current_user_can('edit_pages') ) {
					wp_die(__('You do not have sufficient permissions to edit the layout', 'component-theme'));
				}

		    	define("OXYGEN_VSB_SHOW_BUILDER", true);

		    	add_action("wp_footer", array('Oxygen_VSB_Base', "oxygen_vsb_wp_link_dialog"));
				add_action("wp_head", array('Oxygen_VSB_Base', "oxygen_vsb_footer_styles_hook"));
				
				add_filter("document_title_parts", array('Oxygen_VSB_Base', "oxygen_vsb_builder_wp_title"), 10, 1);
		    }
		}



		/**
		 * Callback for 'document_title_parts' filter
		 *
		 * @since ?
		 * @author ?
		 */

		function oxygen_vsb_builder_wp_title( $title = array() ) {
		 	$title['title'] = __( 'Oxygen Visual Editor', 'component-theme' ).(isset($title['title'])?' - '.$title['title']:'');
		    return $title;
		}

		/**
		 * Check if user has rights to open this post/page in builder
		 * 
		 * @since 1.0.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_check_user_caps() {

			// check if builder activated
		    if ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] ) {

		    	// check if user is logged in
		    	if ( !is_user_logged_in() ) {
					auth_redirect();
				}
				
				global $post;

				// if user can edit this post
				if ( $post !== null && ! current_user_can( 'edit_post', $post->ID ) ) {
					auth_redirect();
				}
		    }
		}



		function oxygen_vsb_oxygen_admin_menu() {

			if(is_admin() || !current_user_can( 'manage_options' ))
				return;

			global $wp_admin_bar, $post;

			$wp_admin_bar->add_menu( array( 'id' => 'oxygen_admin_bar_menu', 'title' => __( 'Oxygen', 'component-theme' ), 'href' => FALSE ) );

			$post_id = false;
			$template = false;

			// get archive template
			if ( is_archive() || is_search() || is_404() || is_home() || is_front_page() ) {
				
				if ( is_front_page() ) {
					$post_id 	= get_option('page_on_front');

					// NOTE check if other_template or custom_template is applied, if not, then get the default template

					//$shortcodes = get_post_meta( $post_id, "ct_builder_shortcodes", true );
				}
				else if ( is_home() ) {
					$post_id 	= get_option('page_for_posts');

					//$shortcodes = get_post_meta( $post_id, "ct_builder_shortcodes", true );
				}
				else {
					$template 	= oxygen_vsb_get_archives_template();

					//$shortcodes = $template?get_post_meta( $template->ID, "ct_builder_shortcodes", true ):false;
					$wp_admin_bar->add_menu( array( 'id' => 'edit_post_template', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit '.$template->post_title.' Template', 'component-theme' ), 'href' => esc_url(oxygen_vsb_get_post_builder_link( $template->ID )) ) );

					$wp_admin_bar->add_menu( array( 'id' => 'edit_template', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit Template Settings', 'component-theme' ), 'href' => get_edit_post_link($template->ID) ) );
				}

			} 

			if($post_id || (!$template && is_singular())) {

				if($post_id == false)
					$post_id = $post->ID;

				// look for default template that can apply to the given post
				if(is_front_page() || is_home())
					$generic_view = oxygen_vsb_get_archives_template( $post_id );
				else
					$generic_view = oxygen_vsb_get_posts_template($post_id);

				$custom_view = get_post_meta( $post_id, 'ct_builder_shortcodes', true );

				$ct_render_post_using = get_post_meta( $post_id, 'ct_render_post_using', true );

				$ct_other_template = get_post_meta( $post_id, 'ct_other_template', true );

				if(!$custom_view && !$ct_render_post_using && !$generic_view) {
					$custom_view = ' ';
					$ct_render_post_using = 'custom_template';
				}

				if($ct_render_post_using == 'custom_template' || (!$ct_render_post_using && $custom_view)) {
					$wp_admin_bar->add_menu( array( 'id' => 'edit_in_visual_editor', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit in Visual Editor', 'component-theme' ), 'href' => esc_url(oxygen_vsb_get_post_builder_link( $post_id )) ) );
				}
				elseif($ct_render_post_using == 'other_template' || $generic_view) {
					
					global $wpdb;

					$template = $wpdb->get_results(
					    "SELECT id, post_title
					    FROM $wpdb->posts as post
					    WHERE post_type = 'ct_template'
					    AND id = $ct_other_template
					    AND post.post_status IN ('publish')"
					);

					if(is_array($template) && sizeof($template) > 0 ) { // select a default template, if none assigned
						$wp_admin_bar->add_menu( array( 'id' => 'edit_post_template', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit '.$template[0]->post_title.' Template', 'component-theme' ), 'href' => esc_url(oxygen_vsb_get_post_builder_link( $ct_other_template )) ) );

					} elseif($generic_view) {
						$ct_other_template = $generic_view->ID;
						$wp_admin_bar->add_menu( array( 'id' => 'edit_generic_template', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit '.$generic_view->post_title.' Template', 'component-theme' ), 'href' => esc_url(oxygen_vsb_get_post_builder_link( $ct_other_template )) ) );
					}

					
					// check if the template uses the ct_inner_content module
											
					$shortcodes = get_post_meta( $ct_other_template, 'ct_builder_shortcodes', true );
					
					if(strpos($shortcodes, '[ct_inner_content') !== false) {
						
						$ct_use_inner_content = get_post_meta($post_id, 'ct_use_inner_content', true);

						if($ct_use_inner_content && $ct_use_inner_content == 'layout')
							$wp_admin_bar->add_menu( array( 'id' => 'edit_inner_content', 'parent' => 'oxygen_admin_bar_menu', 'title' => __( 'Edit Inner Content', 'component-theme' ), 'href' => esc_url(oxygen_vsb_get_post_builder_link( $post_id )).'&ct_inner=true' ) );
					}

				}
				
			}
		}



		/**
		 * Set CT parameters to recognize on fronted and builder
		 * 
		 * @since 0.2.0
		 * @author Ilya K.
		 */

		function oxygen_vsb_editing_template() {

		    if ( get_post_type() == "ct_template" ) {

		    	$template_type = get_post_meta( get_the_ID(), 'ct_template_type', true );

		    	if ( $template_type == "archive" || $template_type == "single_post" ) {
		    		define("OXYGEN_VSB_TEMPLATE_EDIT", true);	
		    	}

		    	if ( $template_type == "archive" ) {
		    		define("OXYGEN_VSB_TEMPLATE_ARCHIVE_EDIT", true);	
		    	}

		    	if ( $template_type == "single_post" ) {
		    		define("OXYGEN_VSB_TEMPLATE_SINGLE_EDIT", true);	
		    	}
		    }
		}



		/**
		 * Get current request URL
		 * 
		 * @since ?
		 * @author gagan goraya
		 */

		function oxygen_vsb_get_current_url($more_query) {

			$request_uri = '';

			$request = explode('?', $_SERVER["REQUEST_URI"]);

			if(isset($request[1])) {
				$request_uri = $_SERVER["REQUEST_URI"].'&'.$more_query;
			}
			else {
				$request_uri = $_SERVER["REQUEST_URI"].'?'.$more_query;	
			}

			$pageURL = 'http';
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
			$pageURL .= "://";
			
			$pageURL .= $_SERVER["HTTP_HOST"].$request_uri;
			
			return $pageURL;
		}


		/**
		 * Include Scripts and Styles for frontend and builder
		 * 
		 * @since 0.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_enqueue_scripts() {

			// include normalize.css
			wp_enqueue_style("normalize", OXYGEN_VSB_FW_URI . "/vendor/normalize.css");

			wp_enqueue_style("oxygen", OXYGEN_VSB_FW_URI. "/style.css", array(), OXYGEN_VSB_VERSION );

			wp_enqueue_script("jquery");

			/**
			 * Add-on hook for scripts that should be displayed both frontend and builder
			 *
			 * @since 1.4
			 */
			do_action("oxygen_vsb_enqueue_scripts");

			// only for frontend
			if ( ! defined("OXYGEN_VSB_SHOW_BUILDER") ) {

				/**
				 * Add-on hook
				 *
				 * @since 1.4
				 */
				do_action("oxygen_vsb_enqueue_frontend_scripts");

				wp_enqueue_style("oxygen-styles", Oxygen_VSB_Base::oxygen_vsb_get_current_url( 'xlink=css' ) );
				// anything beyond this is for builder
				return;
			}

			// Font Loader
			wp_enqueue_script("font-loader", "//ajax.googleapis.com/ajax/libs/webfont/1/webfont.js");

			// jQuery UI
			wp_enqueue_script("jquery-ui", "//code.jquery.com/ui/1.11.3/jquery-ui.js", array(), '1.11.3');
			wp_enqueue_style("jquery-ui-css", "//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css", array());

			// WordPress Media
			wp_enqueue_media();

			// link manager
			wp_enqueue_script( 'wplink' );
			wp_enqueue_style( 'editor-buttons' );

			// FontAwesome
			wp_enqueue_style("font-awesome", "//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css", array(), '4.3.0');

			// AngularJS
			wp_enqueue_script("angular", 			"//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular.js", array(), '1.4.2');
			wp_enqueue_script("angular-animate", 	"//ajax.googleapis.com/ajax/libs/angularjs/1.3.5/angular-animate.js", array(), '1.4.2');

			// Colorpicker
			wp_enqueue_script("bootstrap-colorpicker-module", 	OXYGEN_VSB_FW_URI . "/vendor/colorpicker/js/bootstrap-colorpicker-module.js");
			wp_enqueue_style ("bootstrap-colorpicker-module", 	OXYGEN_VSB_FW_URI . "/vendor/colorpicker/css/colorpicker.min.css");

			// Dragula
		 	wp_enqueue_script("dragula", 						OXYGEN_VSB_FW_URI . "/vendor/dragula/angular-dragula.js");
			wp_enqueue_style ("dragula", 						OXYGEN_VSB_FW_URI . "/vendor/dragula/dragula.min.css");

			// nuSelectable
			//wp_enqueue_script("nu-selectable", 					OXYGEN_VSB_FW_URI . "/vendor/nuSelectable/jquery.nu-selectable.js");

			// Codemirror
			wp_enqueue_script("ct-codemirror", 					OXYGEN_VSB_FW_URI . "/vendor/codemirror/codemirror.js");
			wp_enqueue_style ("ct-codemirror", 					OXYGEN_VSB_FW_URI . "/vendor/codemirror/codemirror.css");

			wp_enqueue_script("ui-codemirror", 					OXYGEN_VSB_FW_URI . "/vendor/ui-codemirror/ui-codemirror.js");

			wp_enqueue_script("ct-codemirror-html",				OXYGEN_VSB_FW_URI . "/vendor/codemirror/htmlmixed/htmlmixed.js");
			wp_enqueue_script("ct-codemirror-xml",				OXYGEN_VSB_FW_URI . "/vendor/codemirror/xml/xml.js");
			wp_enqueue_script("ct-codemirror-js", 				OXYGEN_VSB_FW_URI . "/vendor/codemirror/javascript/javascript.js");
			wp_enqueue_script("ct-codemirror-css",				OXYGEN_VSB_FW_URI . "/vendor/codemirror/css/css.js");
			wp_enqueue_script("ct-codemirror-clike",			OXYGEN_VSB_FW_URI . "/vendor/codemirror/clike/clike.js");
			wp_enqueue_script("ct-codemirror-php",				OXYGEN_VSB_FW_URI . "/vendor/codemirror/php/php.js");

			// Builder files
			wp_enqueue_script("ct-angular-main", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.main.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-tree", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.tree.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-states", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.states.js", 		array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-navigation", 			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.navigation.js", 	array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-columns", 			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.columns.js", 		array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-ajax", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.ajax.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-ui", 					OXYGEN_VSB_FW_URI . "/angular/controllers/controller.ui.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-classes", 			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.classes.js", 		array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-options", 			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.options.js", 		array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-fonts", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.fonts.js", 		array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-svg", 				OXYGEN_VSB_FW_URI . "/angular/controllers/controller.svg.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-css",					OXYGEN_VSB_FW_URI . "/angular/controllers/controller.css.js", 			array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-templates",			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.templates.js", 	array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-drag-n-drop",			OXYGEN_VSB_FW_URI . "/angular/controllers/controller.drag-n-drop.js", 	array(), OXYGEN_VSB_VERSION);
			wp_enqueue_script("ct-angular-media-queries",		OXYGEN_VSB_FW_URI . "/angular/controllers/controller.media-queries.js", array(), OXYGEN_VSB_VERSION);

			/**
			 * Add-on hook
			 *
			 * @since 1.4
			 */
			do_action("oxygen_vsb_enqueue_builder_scripts");

			wp_enqueue_script("ct-angular-directives",			OXYGEN_VSB_FW_URI . "/angular/builder.directives.js", array(), OXYGEN_VSB_VERSION);

			// Add some variables needed for AJAX requests
			global $post;
			global $wp_query;

			$postid = $post->ID;

			if (is_front_page()) {
				$postid 		= get_option('page_on_front');
			}
			else if(is_home()) {
				$postid 		= get_option('page_for_posts');
			}

			$nonce = wp_create_nonce( 'oxygen-nonce-' . $postid );

			$options = array ( 
				'ajaxUrl' 	=> admin_url( 'admin-ajax.php' ),
				'permalink' => get_permalink(),
				'postId' 	=> $postid,
				'query' 	=> $wp_query->query,
				'nonce' 	=> $nonce
			);

			if ( defined("OXYGEN_VSB_TEMPLATE_EDIT") ) {
				$options["ctTemplate"] = true;
			}

			if ( defined("OXYGEN_VSB_TEMPLATE_ARCHIVE_EDIT") ) {
				$options["ctTemplateArchive"] = true;
			}

			if ( defined("OXYGEN_VSB_TEMPLATE_SINGLE_EDIT") ) {
				$options["ctTemplateSingle"] = true;
			}
			
			wp_localize_script( "ct-angular-main", 'CtBuilderAjax', $options);
		}



		/**
		 * Init
		 * 
		 * @since 0.2.5
		 */

		function oxygen_vsb_init() {

			// check if builder activated
		    if ( defined("OXYGEN_VSB_SHOW_BUILDER") ) {
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_default_options"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_not_css_options"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_nice_names"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_settings"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_components_classses"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_custom_selectors"));
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_init_style_sheets"));
		    	
		    	add_action("oxygen_vsb_builder_ng_init", array('Oxygen_VSB_Base', "oxygen_vsb_components_tree_init"), 100 );

		    	// Include Toolbar
				require_once("toolbar/toolbar.class.php");
		    }
		}


		/**
		 * Get categories, pages, components
		 *
		 * @since 1.0.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_get_base() {

			if ( ! defined("OXYGEN_VSB_SHOW_BUILDER") ) {
				return;
			}
			
			global $oxygen_add_plus;
			global $api_components;
			global $api_pages;
			global $api_design_sets;

			if(file_exists(dirname(__FILE__).'/get_base.json')) {
				$response = json_decode(file_get_contents(dirname(__FILE__).'/get_base.json'), true);
			}
			else {
				return;
			}

			if ( $response["status"] == "ok" ) {
				$api_pages 			= $response["pages"];
				$api_components 	= $response["components"];
				$categories 		= $response["categories"];
				$page_categories 	= $response["page_categories"];
				$api_design_sets 	= $response["design_sets"];
			} 
			else {
				$api_pages 			= array();
				$api_components 	= array();
				$categories 		= array();
				$page_categories 	= array();
				$api_design_sets 	= array();
			}

			// build Add+ section
			$components = array();
			$components["id"] 			= "components";
			$components["name"] 		= "Components";
			
			$design_sets = array();
			$design_sets["id"] 	 		= "design_sets";
			$design_sets["name"] 		= "Design Sets";
			$design_sets["children"] 	= array();
			
			// Components
			if(is_array($api_components)) {
				foreach ($api_components as $key => $component) {
					$component = (array) $component;
					
					if ( !isset( $design_sets["children"][$component["design_set_id"]] ) ) {
						$design_sets["children"][$component["design_set_id"]] = array(
							"id" => $component["design_set_id"],
							"name" => $component["design_set_name"],
							"children" => array()
						);
					}

					// create components array first time
					if ( !isset   ( $design_sets["children"][$component["design_set_id"]]["children"]["component"] ) ||
						 !is_array( $design_sets["children"][$component["design_set_id"]]["children"]["component"] ) ) {
						$design_sets["children"][$component["design_set_id"]]["children"]["component"] = array(
							"name" 	=> "Components",
							"type" 	=> "component",
							"id" 	=> $component["design_set_id"],
							"items" => array()
						);
					}

					// push component to certain design set
					$design_sets["children"][$component["design_set_id"]]["children"]["component"]["items"][] = $component;

					// add to categories array
					if(is_array($categories)) {
						foreach ($categories as $key => $category) {
							if ( !isset( $categories[$key]["items"] ) ) {
								$categories[$key]["items"] = array();
							}

							// add component
							if ( $category["id"] == $component["category_id"] ) {
								$categories[$key]["items"][] = $component;
							}
						}
					}
				}
			}
			
			// build categories tree
			$new = array();

			if(is_array($categories)) {
				foreach ($categories as $category){
				    $new[$category['parent_id']][] = $category;
				}
			}

			function oxygen_vsb_build_categories_tree(&$list, $parent){
				$tree = array();
				if(is_array($parent)) {
					foreach ($parent as $k=>$l){
						if(isset($list[$l['id']])){
							$l['children'] = oxygen_vsb_build_categories_tree($list, $list[$l['id']]);
						}
						$tree[] = $l;
					}
				}
				return $tree;
			}

			if(isset($new[0])) {
				$tree = oxygen_vsb_build_categories_tree($new, $new[0]);
				$components["children"] = $tree;
			}

			//if (isset($api_pages["status"]) && $api_pages["status"] != "error") {
				// Pages
				if(is_array($api_pages)) {
					foreach ($api_pages as $key => $page) {

						$page = (array) $page;

						// create pages array first time
						if (!isset   ( $design_sets["children"][$page["design_set_id"]]["children"]["page"] ) || 
							!is_array( $design_sets["children"][$page["design_set_id"]]["children"]["page"] ) ) {
							$design_sets["children"][$page["design_set_id"]]["children"]["page"] = array(
								"name" 	=> "Pages",
								"type" 	=> "page",
								"id" 	=> $page["design_set_id"],
								"items" => array()
							);
						}

						// check pages category
						if ( $page["category_id"] ) {

							// create category folder for the first time
							if (!isset   ($design_sets["children"][$page["design_set_id"]]["children"]["page"]["children"][$page["category_id"]]) || 
								!is_array($design_sets["children"][$page["design_set_id"]]["children"]["page"]["children"][$page["category_id"]]) ) {
								// add to categories array
								if(is_array($page_categories)) {
									foreach ($page_categories as $key => $category) {
										// add component
										if ( $category["id"] == $page["category_id"] ) {
											$name = $category["name"];
										}
									}
								}
								
								$design_sets["children"][$page["design_set_id"]]["children"]["page"]["children"][$page["category_id"]] 
								= array( 	
									"id" 	=> $page["category_id"]."_".$page["design_set_id"],
									"type" 	=> "page",
									"name" 	=> $name );
							}

							$design_sets["children"][$page["design_set_id"]]["children"]["page"]["children"][$page["category_id"]]["items"][] = $page;
						}
						else {
							// push page to certain design set
							$design_sets["children"][$page["design_set_id"]]["children"]["page"]["items"][] = $page;
						}
					}
				}
			//}
			
			$oxygen_add_plus = array(
					"status" 		=> $response["status"],
					"components" 	=> $components,
					"design_sets" 	=> $design_sets,
				);

			if ( $response["status"] == "error" && is_array($response["error"]["errors"])) {
				$oxygen_add_plus["errors"] = reset($response["error"]["errors"]);
			}

			// make design sets ids to be keys
			foreach ( $api_design_sets as $design_set ) {
			    $new_design_sets[$design_set['id']] = $design_set;
			}
			if ( isset($new_design_sets) && is_array( $new_design_sets ) ) {
				$api_design_sets = $new_design_sets;
			}

			if ( $response["status"] == "error" && isset($response["message"]) ) {
				$oxygen_add_plus["message"] = $response["message"];
			}
		}



		/**
		 * Output all Components (shortcodes) default params to ng-init directive
		 *
		 * @since 0.1
		 */

		function oxygen_vsb_init_default_options() {

			$components = apply_filters( "oxygen_vsb_component_default_params", array() );

			$all_defaults = call_user_func_array('array_merge', $components);

			$components["all"] = $all_defaults;

			$output = json_encode($components);
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "defaultOptions = $output;";
		}


		/**
		 * Output array of all not CSS options for each component
		 *
		 * @since 0.3.2
		 */

		function oxygen_vsb_init_not_css_options() {

			$components = apply_filters( "oxygen_vsb_not_css_options", array() );

			$output = json_encode($components);
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "notCSSOptions = $output;";
		}


		/**
		 * Pass Components Tree JSON to ng-init directive
		 *
		 * @since 0.1
		 */

		function oxygen_vsb_components_tree_init() {

			echo "init();";
		}


		/**
		 * Output Components nice names
		 *
		 * @since 0.1.2
		 */

		function oxygen_vsb_init_nice_names() {

			$names = apply_filters( "oxygen_vsb_components_nice_names", array() );

			$names['root'] = "Root";

			$output = json_encode($names);
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "niceNames = $output;";
		}


		/**
		 * Output Page and Global Settings
		 *
		 * @since 0.1.3
		 */

		function oxygen_vsb_init_settings() { 

			// Page settings
			$output = json_encode( Oxygen_VSB_Base::oxygen_vsb_get_page_settings( get_the_ID() ) );
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "pageSettings = $output;";

			// Global settings
			$output = json_encode(Oxygen_VSB_Base::oxygen_vsb_get_global_settings());
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "globalSettings = $output;";
		}


		/**
		 * Output CSS Classes
		 *
		 * @since 0.1.7
		 */

		function oxygen_vsb_init_components_classses() { 
			
			$classes = Oxygen_VSB_Base::oxygen_vsb_get_components_classes();

			$output = json_encode( $classes, JSON_FORCE_OBJECT );
			$output = htmlspecialchars( $output, ENT_QUOTES );

			echo "classes = $output;";
		}

		function oxygen_vsb_get_components_classes($return_js = false) {
			//update_option("ct_components_classes");
			$classes = get_option("ct_components_classes", array());

			if ( ! is_array( $classes ) )
				return array();
			
			// base64_decode the custom-css and custom-js
			$classes = Oxygen_VSB_Base::oxygen_vsb_base64_decode_selectors($classes, $return_js);

			return $classes;
		}


		/**
		 * base64 decode classes and custom selectors custom ccs/js
		 *
		 * @since 1.3
		 * @author Ilya/Gagan
		 */

		function oxygen_vsb_base64_decode_selectors($selectors, $return_js = false) {

			$selecotrs_js = array();

			foreach($selectors as $key => $class) {
				foreach($class as $statekey => $state) {
					if($statekey == 'media') {
						foreach($state as $bpkey => $bp) {
							foreach($bp as $bpstatekey => $bpstate) {
								if(isset($bpstate['custom-css']) && !strpos($bpstate['custom-css'], ' '))
				  					$selectors[$key][$statekey][$bpkey][$bpstatekey]['custom-css'] = base64_decode($bpstate['custom-css']);
				  				if(isset($bpstate['custom-js'])) {
				  					if(!strpos($bpstate['custom-js'], ' '))
				  						$selectors[$key][$statekey][$bpkey][$bpstatekey]['custom-js'] = base64_decode($bpstate['custom-js']);
				  					// output js to the footer
				  					$classes_js[implode("_", array($key, $statekey, $bpkey, $bpstatekey))] = $states[$key][$mediakey][$mediastatekey]['custom-js'];	
				  				}
							}
						}
					}
					else {
				  		if(isset($class[$statekey]['custom-css']) && !strpos($class[$statekey]['custom-css'], ' '))
				  			$selectors[$key][$statekey]['custom-css'] = base64_decode($class[$statekey]['custom-css']);
				  		if(isset($class[$statekey]['custom-js'])) {
				  			if(!strpos($class[$statekey]['custom-js'], ' '))
								$selectors[$key][$statekey]['custom-js'] = base64_decode($class[$statekey]['custom-js']);
				  			
				  			// output js to the footer
				  			$selecotrs_js[implode("_", array($key, $statekey))] = $selectors[$key][$statekey]['custom-js'];
				  		}
				  	}
			  	}
		  	}

		  	if($return_js)
		  		return $selecotrs_js;
		  	else
		  		return $selectors;
		}


		/**
		 * Init custom selectors styles
		 *
		 * @since 1.3
		 */

		function oxygen_vsb_init_custom_selectors() {
			
			//update_option( "ct_custom_selectors", array() );
			$selectors = get_option( "ct_custom_selectors", array() );

			// make sure this is an array if we have empty string saved somehow
			if ($selectors == "") {
				$selectors = array();
			}

			$selectors = Oxygen_VSB_Base::oxygen_vsb_base64_decode_selectors($selectors);

			$selectors = json_encode( $selectors, JSON_FORCE_OBJECT );
			$selectors = htmlspecialchars( $selectors, ENT_QUOTES );
			
			echo "customSelectors = $selectors;";

			$style_sets = get_option( "ct_style_sets", array() );

			// make sure this is an array if we have empty string saved somehow
			if ($style_sets == "") {
				$style_sets = array();
			}

			$style_sets = json_encode( $style_sets );
			$style_sets = htmlspecialchars( $style_sets, ENT_QUOTES );

			echo "styleSets=$style_sets;";
		}

		/**
		 * retreive shortcodes
		 *
		 * @since 1.3
		 */

		function oxygen_vsb_template_shortcodes($execute_inner_content = false) {
			$post_id = false;
			$template = false;
			// get archive template
			if ( is_archive() || is_search() || is_404() || is_home() || is_front_page() ) {

				if ( is_front_page() ) {
					$post_id 	= get_option('page_on_front');
				}
				else if ( is_home() ) {
					$post_id 	= get_option('page_for_posts');
				}
				else 
				{
					$template 	= oxygen_vsb_get_archives_template();
					$shortcodes = $template?get_post_meta( $template->ID, "ct_builder_shortcodes", true ):false;
				}
			} 
			//else
			// get single template
			if($post_id || (!$template && is_singular())) {

				// get post type
				if($post_id == false)
					$post_id = get_the_ID();

				$ct_render_post_using = get_post_meta( $post_id, "ct_render_post_using", true );
				
				if($ct_render_post_using != 'other_template')
					$shortcodes = get_post_meta( $post_id, "ct_builder_shortcodes", true );
				else
					$shortcodes = false;

				if ( !$shortcodes ) { // it is not a custom view

					$template_id = get_post_meta( $post_id, 'ct_other_template', true );

					if($template_id) {
						$template = get_post($template_id);
					}
					else {

						if(is_front_page() || is_home())
							$template = oxygen_vsb_get_archives_template( $post_id );
						else
							$template = oxygen_vsb_get_posts_template( $post_id );
					}

					// get template shortcodes
					$shortcodes = get_post_meta( $template->ID, "ct_builder_shortcodes", true );
					
					// if the template uses inner content module, populate it from the shortcodes found in the custom view
					if(strpos($shortcodes, '[ct_inner_content') !== false && $execute_inner_content === false) {
						
						$ct_use_inner_content = get_post_meta($post_id, 'ct_use_inner_content', true);

						if(!$ct_use_inner_content || $ct_use_inner_content == 'content') {
							$singular_shortcodes = '[ct_code_block ct_options=\'{"ct_id":2,"ct_parent":0,"selector":"ct_code_block_2_post_7","original":{"code-php":"PD9waHAKCXdoaWxlKGhhdmVfcG9zdHMoKSkgewogICAgCXRoZV9wb3N0KCk7CgkJdGhlX2NvbnRlbnQoKTsKICAgIH0KPz4="},"activeselector":false}\'][/ct_code_block]';					
						}
						else {
							$singular_shortcodes = get_post_meta( $post_id, "ct_builder_shortcodes", true );
						}
						// temp replace ID's and parent references of the singular_shortcodes
						$pattern = '/(\"ct_id\"\:)([^,}]*)/i';

						$shortcodes = preg_replace_callback($pattern, array('Oxygen_VSB_Base', 'obfuscate_ids'), $shortcodes);
						
						$pattern = '/(\"ct_parent\"\:)([^,}]*)/i';
						$shortcodes = preg_replace_callback($pattern, array('Oxygen_VSB_Base', 'obfuscate_ids'), $shortcodes);

						$pattern = '/(\"selector\"\:)(\"ct_[^,"}]*)_post_/i';
						$shortcodes = preg_replace_callback($pattern, array('Oxygen_VSB_Base', 'obfuscate_selectors'), $shortcodes);

						// find the id of the inner_content module from the above
						$matches = array();
						$pattern = '/\[ct_inner_content[^\]]*ct_id\"\:([\d]*)/i';
						preg_match($pattern, $shortcodes, $matches);
						$container_id = $matches[1];

						// set the parent_id of all modules having parent_id=0 to the id found above
						$singular_shortcodes = str_replace('"ct_parent":0', '"ct_parent":'.$container_id, $singular_shortcodes);
						$singular_shortcodes = str_replace('"ct_parent":"0"', '"ct_parent":'.$container_id, $singular_shortcodes);

						// re-index the depths of the components inside the singular_shortcodes on the basis
						// of the inner most nesting of the same type of component in the outer template

						preg_match_all("/(\[ct_inner_content[^\]]*ct_parent[\"|\']:([^,]*),([^\]]*)\]?)(\[\/ct_inner_content]?)/", $shortcodes, $matches);

						$parent_id = intval($matches[2][0]);
						
						Oxygen_VSB_Base::set_oxygen_vsb_offsetDepths_source($parent_id, $shortcodes);

						$singular_shortcodes = preg_replace_callback("/([\[|\/])(ct_[^\s\[\]\d]*)[_]?([0-9]?)/", array('Oxygen_VSB_Base', 'oxygen_vsb_offsetDepths'), $singular_shortcodes);

						// insert the page contents into the inner_content module of the parent template 
						$pattern = '/(\[ct_inner_content([^\]]*)\]?)(\[\/ct_inner_content]?)/i';
						$replacement = '${1}'.$singular_shortcodes.'${3}';
						
						$shortcodes = preg_replace($pattern, $replacement, $shortcodes);
					}
					
				}
			} else {

				$template 	= oxygen_vsb_get_archives_template();
				$shortcodes = $template?get_post_meta( $template->ID, "ct_builder_shortcodes", true ):false;
			}

			if($shortcodes)
				return $shortcodes;
			else
				return false;
		}

		/**
		 * Init style sheets
		 *
		 * @since 0.3.4
		 * @author gagan goraya
		 */

		function oxygen_vsb_init_style_sheets() {
			
			
			$style_sheets = get_option( "ct_style_sheets", array() ); 

			// it was returning 'string (0) ""' first time, don't know why
			if ( !is_array( $style_sheets ) )
				$style_sheets = array();
			
			//base 64 decode
			foreach($style_sheets as $key => $value) {
				$style_sheets[$key] = base64_decode($style_sheets[$key]);
			}

			$output = json_encode( $style_sheets, JSON_FORCE_OBJECT );
			$output = htmlspecialchars( $output, ENT_QUOTES );
			
			echo "styleSheets = $output;";
		}

		/**
		 * Output all saved CSS styles to frontend
		 *
		 * @since 0.1.3
		 */

		function oxygen_vsb_css_styles() {
			// Global settings
			$global_settings = Oxygen_VSB_Base::oxygen_vsb_get_global_settings();

			$components_defaults = apply_filters("oxygen_vsb_component_default_params", array() );

			global $fake_properties;

			$fake_properties = array( 
					'overlay-color', 
					'background-position-left', 
					'background-position-top',
					'background-size-width',
					'background-size-height',
					'ct_content',
					'tag',
					'url',
					'src',
					'alt',
					'target',
					'icon-id',
					"section-width",
					"custom-width",
					"container-padding-top",
					"container-padding-right",
					"container-padding-bottom",
					"container-padding-left",
					"custom-css",
					"custom-js",
					"code-css",
					"code-js",
					"code-php",
					"gutter",
					'border-all-color',
					'border-all-style',
					'border-all-width',
					'function_name',
					'friendly_name',
					'shortcode_tag',
					'id'
				);

			// Output all components default styles
			foreach ( $components_defaults as $component_name => $values ) {
				
				$component_name = str_replace( "_", "-", $component_name );
				
				if ( $component_name == "ct-paragraph" ) {
					echo ".$component_name p {\r\n";
				}
				else {
					echo ".$component_name {\r\n";
				}
				if(is_array($values)) {
					foreach ( $values as $name => $value ) {

						// skip uints
						if ( strpos( $name, "-unit") ) {
							continue;
						}

						// skip empty values
						if ( $value === "" ) {
							continue;
						}

						// skip fake properties
						if ( in_array( $name, $fake_properties ) ) {
							continue;
						}

						// handle global fonts
						if ( $name == "font-family" && is_array( $value ) ) {
							$value = $global_settings['fonts'][$value[1]];

							if ( strpos($value, ",") === false && strtolower($value) != "inherit" ) {
								$value = "'$value'";
							}
						}

						// handle unit options
						if ( isset($values[$name.'-unit']) && $values[$name.'-unit'] ) {
							// set to auto
							if ( $values[$name.'-unit'] == 'auto' ) {
								$value = 'auto';
							}
							// or add unit
							else {
								$value .= $values[$name.'-unit'];
							}
						}

						if ( $value !== "" ) {
							echo "  $name:$value;\r\n";
						}
					}
				}

				echo "}\r\n";
			}

			// Below is only for frontend
			if ( defined("OXYGEN_VSB_SHOW_BUILDER") )
				return;
			
			$css = "";

			$page_settings = false;
			$post_id = false;
			$template = false;

			// get archive template
			if ( is_archive() || is_search() || is_404() || is_home() || is_front_page() ) {

				if (is_front_page()) {
					$post_id 		= get_option('page_on_front');
				}
				else if(is_home()) {
					$post_id 		= get_option('page_for_posts');
				}

				// do not apply any templates if there is a custom view
				else {

					$template = oxygen_vsb_get_archives_template();

					$page_settings = Oxygen_VSB_Base::oxygen_vsb_get_page_settings( $template->ID );
				}
			} 
			
			// get single template
			if($post_id || (!$template && is_singular())) {

				if($post_id == false)
					$post_id = get_the_ID();

				$ct_render_post_using = get_post_meta( $post_id, "ct_render_post_using", true );

				if($ct_render_post_using != 'other_template'){
					$custom_view = get_post_meta( $post_id, "ct_builder_shortcodes", true );
				}
				else {
					$custom_view = false;
				}

				// do not apply any templates if there is a custom view
				if ( $custom_view ) {
					$page_settings = Oxygen_VSB_Base::oxygen_vsb_get_page_settings( $post_id );
				}
				else {

					$template_id = get_post_meta( $post_id, 'ct_other_template', true );

					if($template_id) {
						$template = get_post($template_id);
					}
					else {

						if(is_front_page() || is_home())
							$template = oxygen_vsb_get_archives_template( $post_id );
						else
							$template = oxygen_vsb_get_posts_template( $post_id );
					}

					// get template shortcodes
					$page_settings = Oxygen_VSB_Base::oxygen_vsb_get_page_settings( $template->ID );
				}

			}

			// if no page settings so far, check if we are using a header/footer template
			
			if(!$page_settings) {

				global $oxygen_vsb_template_header_footer_id;

				if(isset($oxygen_vsb_template_header_footer_id)) {

					$page_settings = Oxygen_VSB_Base::oxygen_vsb_get_page_settings( $oxygen_vsb_template_header_footer_id );

				}
			}

			$css .= ".ct-section-inner-wrap{\r\n  max-width: ".str_replace('px', '', $page_settings['max-width'])."px;\r\n}\r\n";

			global $media_queries_list;

			// CSS Classes
			$classes = get_option( "ct_components_classes" );

			if ( is_array( $classes ) ) {
				foreach ( $classes as $class => $states ) {
					$style = "";
					foreach ( $states as $state => $options ) {
						
						if ( $state == 'media' ) {

							foreach ( $media_queries_list as $media_name => $media ) {
								$max_width = $media_queries_list[$media_name]['maxSize'];

								if ( isset($options[$media_name]) && $media_name != "default") {

									$style .= "@media (max-width: $max_width) {\n";
										foreach ( $options[$media_name] as $media_state => $media_options ) {
											$style .= Oxygen_VSB_Base::oxygen_vsb_generate_class_states_css($class, $media_state, $media_options, true);
										}
									$style .= "}\n\n";
								}
							}
						}
						else {
							$style = Oxygen_VSB_Base::oxygen_vsb_generate_class_states_css($class, $state, $options).$style;
						}
					}

					$css .= $style;
				}
			}

			$text_font = $global_settings['fonts']['Text'];

			if ( !is_array($value) && strpos($text_font, ",") === false && strtolower($value) != "inherit" ) {
				$text_font = "'$text_font'";
			}

			$css .= "body{
					font-family: $text_font;
				}\r\n";

			// make columns fullwidth on mobile
			$css .= "@media (max-width: 992px) {
						.ct-columns-inner-wrap {
							display: block !important;
						}
						.ct-columns-inner-wrap:after {
							display: table;
							clear: both;
							content: \"\";
						}
						.ct-column {
							width: 100% !important;
							margin: 0 !important;
						}
						.ct-columns-inner-wrap {
							margin: 0 !important;
						}
					}\r\n";
			
			// output CSS
			echo $css;
		}


		function oxygen_vsb_generate_class_states_css( $class, $state, $options, $is_media = false, $is_selector = false ) {
			
			global $fake_properties;
			//global $font_families_list;
			$css = "";
			$class = sanitize_html_class( $class );
			$state = sanitize_html_class( $state );

			$components_defaults = apply_filters("oxygen_vsb_component_default_params", array() );
			$defaults = call_user_func_array('array_merge', $components_defaults);

			if ( !$is_selector ) {
				if ( $state != 'original' ) {
					$css .= ".$class:not(.ct-paragraph):$state,\r\n";
					if ( Oxygen_VSB_Base::is_pseudo_element($state) ) {
						$css .= ".$class.ct-paragraph p:$state{\r\n";
					}
					else {
						$css .= ".$class.ct-paragraph:$state p{\r\n";
					}
				}
				else {
					$css .= ".$class:not(.ct-paragraph),\r\n";
					$css .= ".$class.ct-paragraph p{\r\n";
				}
			}
			else {
				if ( $state != 'original' ) {
					$css .= "$class:$state{\r\n";
				}
				else {
					$css .= "$class{\r\n";	
				}
			}

			$content_included = false;
			
			// handle units
			if(is_array($options)) {
				foreach ( $options as $name => $value ) {
					// handle unit options
					if ( isset($defaults[$name.'-unit']) && $defaults[$name.'-unit'] ) {

						if ( isset($options[$name.'-unit']) && $options[$name.'-unit'] ) {
							// set to auto
							if ( $options[$name.'-unit'] == 'auto' ) {
								$options[$name] = 'auto';
							}
							// or add unit
							else {
								$options[$name] .= $options[$name.'-unit'];
							}
						}
						else {
							$options[$name] .= $defaults[$name.'-unit'];
						}
					}
					else {
			            if ( $options[$name] == 'auto' ) {
			            	$name = str_replace("-unit", "", $name);
			                $options[$name] = 'auto';
			            }
					}
				}
			}

			// handle background-position option
			if ( (isset($options['background-position-left']) && $options['background-position-left']) || (isset($options['background-position-top']) && $options['background-position-top']) ) {

				$left = $options['background-position-left'] ? $options['background-position-left'] : "0%";
				$top  = $options['background-position-top'] ? $options['background-position-top'] : "0%";
				$options['background-position'] = $left . " " . $top;
			}

			// handle background-size option
			if ( isset($options['background-size']) && $options['background-size'] == "manual" ) {

				$width = $options['background-size-width'] ? $options['background-size-width'] : "auto";
				$height = $options['background-size-height'] ? $options['background-size-height'] : "auto";
				$options['background-size'] = $width . " " . $height;
			}
			
			// loop all other options
			if(is_array($options)) {
				foreach ( $options as $name => $value ) {

					// skip units
					if ( strpos( $name, "-unit") ) {
						continue;
					}

					// skip empty values
					if ( $value === "" ) {
						continue;
					}

					if ( $name == "font-family") {

						if ( $value[0] == 'global' ) {
								$settings 	= get_option("ct_global_settings");
								$value 		= isset($settings['fonts'][$value[1]]) ? $settings['fonts'][$value[1]]: '';
							}

						//$font_families_list[] = $value;

						if ( strpos($value, ",") === false && strtolower($value) != "inherit") {
							$value = "'$value'";
						}
					}

					// update options array values if there was modifications
					$options[$name] = $value;

					// skip fake properties
					if ( in_array( $name, $fake_properties ) ) {
						continue;
					}

					// handle image urls
					if ( $name == "background-image") {
						
						$value = "url(".$value.")";
						// trick for overlay color
			            if ( isset( $options['overlay-color'] ) ) {
			                $value = "linear-gradient(" . $options['overlay-color'] . "," . $options['overlay-color'] . "), " . $value;
			            }
					}
					
					// add quotes for content for :before and :after
					if ( $name == "content" ) {
						//$value = addslashes( $value );
						$value = str_replace('"', '\"', $value);
						$value = "\"$value\"";
						$content_included = true;
					}

					// finally add to CSS
					$css .= " $name:$value;\r\n";
				}
			}
			
			if ( !$content_included && ( $state == "before" || $state == "after" ) && !$is_media ) {
				$css .= "  content:\"\";\r\n";
			}

			// add custom CSS to the end
			if ( isset($options["custom-css"]) && $options["custom-css"] ) {
				$css .= base64_decode( $options["custom-css"] ) . "\r\n";
			}

			$css .= "}\r\n";

			// handle container padding for classes
			if ( (isset($options['container-padding-top']) && $options['container-padding-top']) 	 ||
				 (isset($options['container-padding-right']) && $options['container-padding-right'])  ||
				 (isset($options['container-padding-bottom']) && $options['container-padding-bottom']) ||
				 (isset($options['container-padding-left']) && $options['container-padding-left']) ) {

				$css .= ".$class .ct-section-inner-wrap {\r\n";
				
				if ( isset($options['container-padding-top']) && $options['container-padding-top'] ) {
					$css .= "padding-top: " . $options['container-padding-top'] . ";\r\n";
				}
				if ( isset($options['container-padding-right']) && $options['container-padding-right'] ) {
					$css .= "padding-right: " . $options['container-padding-right'] . ";\r\n";
				}
				if ( isset($options['container-padding-bottom']) && $options['container-padding-bottom'] ) {
					$css .= "padding-bottom: " . $options['container-padding-bottom'] . ";\r\n";
				}
				if ( isset($options['container-padding-left']) && $options['container-padding-left'] ) {
					$css .= "padding-left: " . $options['container-padding-left'] . ";\r\n";
				}

				$css .= "}\r\n";
			}

			return $css;
		}


		/**
		 * Check if state is pseudo-element by it's name
		 *
		 * @since 0.4.0
		 * @author Ilya K.
		 */

		function is_pseudo_element( $name ) {
			
			if ( 
		            strpos($name, "before")       === false &&
		            strpos($name, "after")        === false &&
		            strpos($name, "first-letter") === false &&
		            strpos($name, "first-line")   === false &&
		            strpos($name, "selection")    === false
		        ) 
		    {
		        return false;
		    }
		    else {
		        return true;
		    }
		}

		/**
		 * Generate font familes list to load
		 *
		 * @since  0.2.3
		 */

		function oxygen_vsb_get_font_families_string( $font_families ){

			if ( ! $font_families ) {
				return "";
			}

			$web_safe_fonts = array(
					'inherit',
					'Inherit',
					'Georgia, serif',
					'Times New Roman, Times, serif',
					'Arial, Helvetica, sans-serif',
					'Arial Black, Gadget, sans-serif',
					'Tahoma, Geneva, sans-serif',
					'Verdana, Geneva, sans-serif',
					'Courier New, Courier, monospace'
				);

			// don't load web safe fonts
			$font_families = array_diff( $font_families, $web_safe_fonts );

			// filter array for empty values
			$font_families = array_filter( $font_families, function( $font ) {
								return $font !== '';
							});

			// filter array for duplicate values
			$font_families = array_unique( $font_families );

			// add font weights
			$font_families = array_map( function( $font ) {
								return $font . ':100,200,300,400,500,600,700,800,900';
							}, $font_families );

			// add "" quotes
			$font_families = array_map( function( $font ) {
								return '"' . $font . '"';
							}, $font_families );		

			// create fonts string to pass into JS
			$font_families = implode(",", $font_families);

			return $font_families;
		}


		/**
		 * Echo all stylesheets
		 * 
		 * @since 0.3.4
		 * @author gagan goraya
		 */

		function oxygen_vsb_footer_stylesheets_hook() {

			if ( ! defined("OXYGEN_VSB_SHOW_BUILDER") )
				return;

			$style_sheets = get_option( "ct_style_sheets", array() );

			// it was returning 'string (0) ""' first time, don't know why
			if ( !is_array( $style_sheets ) )
				$style_sheets = array();

			foreach($style_sheets as $key => $value) {
				echo "\n<style type=\"text/css\" id=\"ct-style-sheet-$key\" class=\"ct-css-location\">";
				echo "\n".base64_decode($style_sheets[$key])."\n";
				echo "</style>\n";
			}

		}



		/**
		 * Echo all components JS like web fonts etc
		 * 
		 * @since 0.1.9
		 */

		function oxygen_vsb_footer_script_hook() {
			echo "<script type=\"text/javascript\" id=\"ct-footer-js\">";
				do_action("oxygen_vsb_footer_js");
			echo "</script>";


			$footer_js = Oxygen_VSB_Base::oxygen_vsb_get_components_classes(true);
			if(is_array($footer_js)) {
				foreach($footer_js as $key => $val) {
					echo "<script type=\"text/javascript\" id=\"$key\">";
						echo $val;
					echo "</script>";		
				}
			}

		}



		/**
		 * Displays a warning for non-chrome browsers in the builder
		 * 
		 * @since 0.3.4
		 * @author gagan goraya
		 */

		function oxygen_vsb_chrome_modal() {

			if ( defined("OXYGEN_VSB_SHOW_BUILDER") )  {
				$dismissed = get_option("ct_chrome_modal", false );

				$warningMessage = __("<h2><span class='ct-icon-warning'></span> Warning: we recommend Google Chrome when designing pages</h2><p>The designs you create using Oxygen will work properly in all modern browsers including but not limited to Chrome, Firefox, Safari, and Internet Explorer/Edge.</p><p>But for the best, most stable experience when using Oxygen to design pages, we recommend using Google Chrome.</p><p>We've done most of our testing with Chrome and expect that you will encounter minor bugs in the builder when using Firefox or Safari. Please report those to us by e-mailing at support@oxygenapp.com.</p><p>We have no intention of making the builder work well in Internet Explorer.</p><p>Again, this message only applies to the builder itself. The pages you create with Oxygen will render correctly in all modern browsers.</p><p>Best Regards,<br />The Oxygen Team</p>", 'component-theme' );

				$hideMessage = __("hide this notice", 'component-theme' );

				if(!$dismissed) {


					echo "<div ng-click=\"removeChromeModal(\$event)\" class=\"ct-chrome-modal-bg\"><div class=\"ct-chrome-modal\"><a href=\"#\" class=\"ct-chrome-modal-hide\">".$hideMessage."</a>"."</div></div>";

				?>
					<script type="text/javascript">
					
						jQuery(document).ready(function(){
							var warningMessage = "<?php echo $warningMessage; ?>";
							
					        var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
					        
					        var chromeModalWrap = jQuery('.ct-chrome-modal-bg');

					        if(isChrome) {
					        	chromeModalWrap.remove();
							}
					        else {
								chromeModalWrap.css('display', 'block');
					           	var chromeModal = jQuery('.ct-chrome-modal');
					            chromeModal.append(warningMessage);
					        }

					    });
					
					</script>

					<?php
				}
			}

		}

		/**
		 * Add support for certain WordPress features
		 * 
		 * @since 0.2.3
		 */

		function oxygen_vsb_theme_support() {

			add_theme_support("menus"); 
			add_theme_support("post-thumbnails");
			add_theme_support("title-tag");
			add_theme_support("woocommerce");
		}



		/**
		 * Uses a dedicated template to render CSS only that can be loaded from external links
		 * or Oxygen main template to show builder or builder designed page
		 *
		 * @author gagan goraya
		 * @since 0.3.4
		 */

		function oxygen_vsb_css_output( $template ) {
			
			$new_template = '';
			
			if ( $template != get_page_template() && $template != get_index_template() ) {
				global $oxygen_vsb_replace_render_template;
				$oxygen_vsb_replace_render_template = $template;
			}

			if ( isset( $_REQUEST['xlink'] ) && stripslashes( $_REQUEST['xlink'] ) == 'css' ) {
				if ( file_exists( dirname( __FILE__) . '/csslink.php' ) ) {
					$new_template = dirname( __FILE__ ) . '/csslink.php';
				}
			}
			else {
				// if there is saved template or if we are in builder mode
				if ( file_exists(plugin_dir_path( __FILE__ ) . "/oxygen-main-template.php") ) {
					$new_template =  plugin_dir_path( __FILE__ ) . "/oxygen-main-template.php";
				}
			}
			
			if ( '' != $new_template ) {
				return $new_template;
			}
				
			return $template;
		}


		function oxygen_vsb_determine_render_template( $template ) {
			
			$new_template = '';

			if ( defined( "OXYGEN_VSB_SHOW_BUILDER" ) ) {
				return get_index_template();
			}

			$post_id 	 = get_the_ID();
			$custom_view = false;

			if ( !is_archive() ) {
				$custom_view = get_post_meta( $post_id, "ct_builder_shortcodes", true );
			}
			
			if ( $custom_view || oxygen_vsb_template_output( true ) ) {
				return get_page_template();
			}
			
			return $template;
		}



		/**
		 * Try to get CSS styles before WP run to speed up page load
		 * 
		 * @since 1.1.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_css_link( $template ) {

			if ( isset( $_REQUEST['action'] ) && stripslashes( $_REQUEST['action'] ) == 'save-css' ) {
				return;
			}

			if ( ! isset( $_GET['ct_builder'] ) || ! $_GET['ct_builder'] ) {
				if ( isset( $_REQUEST['xlink'] ) && stripslashes( $_REQUEST['xlink'] ) == 'css' ) {
					ob_start();
					include 'csslink.php';
					ob_end_clean();
				}
			}
		}


		/**
		 * Get template as soon as possible
		 * 
		 * @since 1.1.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_pre_template_output( $template ) {

			// support for elementor plugin
			if ( isset( $_REQUEST['elementor-preview'] ) ) {
				return;
			}

			global $template_content;
			$template_content = oxygen_vsb_template_output();
		}

		/**
		 * Add Cache-Control headers to force page refresh 
		 * on browser's back button click
		 *
		 * @since 0.4.0
		 * @author Ilya K.
		 */

		function oxygen_vsb_add_headers() {

			if ( defined("OXYGEN_VSB_SHOW_BUILDER") ) {
				header_remove("Cache-Control");
				header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0"); // HTTP 1.1.
			}
		}



		/**
		 * Add 'oxygen-body' class for frontend only
		 *
		 * @since 0.4.0
		 * @author Ilya K.
		 */

		function oxygen_vsb_body_class($classes) {

			if ( ! defined("OXYGEN_VSB_SHOW_BUILDER") ) {
				$classes[] = 'oxygen-body';
			}
			else {
				$classes[] = 'oxygen-builder-body';	
			}

			return $classes;
		}



		/**
		 * Loading webfonts for the front end, in the <head> section
		 *
		 * @since 0.3.4
		 * @author gagan goraya
		 */

		function add_web_font() {

			if ( defined("OXYGEN_VSB_SHOW_BUILDER") ) {
				return;
			}

			global $header_font_families;
			$header_font_families = array();

			$global_settings = Oxygen_VSB_Base::oxygen_vsb_get_global_settings();
			$shortcodes = false;
			// add default globals
			foreach ( $global_settings['fonts'] as $key => $value ) {
				$header_font_families[] = $value;
			}
			
			$shortcodes = Oxygen_VSB_Base::oxygen_vsb_template_shortcodes();

			global $shortcode_tags;

			// Find all registered tag names in $content.
			preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $shortcodes, $matches );
			$tagnames = array_intersect( array_keys( $shortcode_tags ), $matches[1] );

			$pattern = get_shortcode_regex( $tagnames );

			$i = 0;
			while(strpos($shortcodes, '[') !== false) {
				$i++;
				$new_shortcodes = preg_replace_callback( "/$pattern/", array('Oxygen_VSB_Base', 'get_shortcode_font'), $shortcodes );
				// content will stop to change when all shortcodes parsed
				if ($new_shortcodes!==$shortcodes) {
					// update content and continue parsing
					$shortcodes = $new_shortcodes;
				}
				else {
					// all parsed, stop the loop
					break;
				}
				// bulletproof way to stop the loop, I doubt anyone will have 100000+ shortcodes on one page 
				if ($i > 100000) break;
			}
			
			// class based fonts
			$classes = get_option( "ct_components_classes", array() );

			// and also custom selectors fonts
			$selectors = get_option( "ct_custom_selectors", array() );
			$classes = array_merge($classes,$selectors);

			if(is_array($classes)) {
				foreach($classes as $key => $class) {
					foreach($class as $statekey => $state) {
						if($statekey == 'media') {
							foreach($state as $bpkey => $bp) {
								foreach($bp as $bpstatekey => $bpstate) {
									if(isset($bpstate['font-family'])) {
										$value = $bpstate['font-family'];
										if ( is_array( $value ) ) {
											// handle global fonts
											if ( $value[0] == 'global' ) {
												
												$settings 	= get_option("ct_global_settings"); 
												$value 		= $settings['fonts'][$value[1]];
											}
										}
										else {
											$value = htmlspecialchars_decode($value, ENT_QUOTES);
										}

										// skip empty values
										if ( $value === "" ) {
											continue;
										}

										// make font family accessible for web fonts loader
										$header_font_families[] = "$value";
									}
								}
							}
						}
						else {
					  		if(isset($class[$statekey]['font-family'])) {
								$value = $class[$statekey]['font-family'];
								if ( is_array( $value ) ) {
									// handle global fonts
									if ( $value[0] == 'global' ) {
										
										$settings 	= get_option("ct_global_settings"); 
										$value 		= isset($settings['fonts'][$value[1]])?$settings['fonts'][$value[1]]:'';
									}
								}
								else {
									$value = htmlspecialchars_decode($value, ENT_QUOTES);
								}

								// skip empty values
								if ( $value === "" ) {
									continue;
								}

								// make font family accessible for web fonts loader
								$header_font_families[] = "$value";			  			
					  		}
					  	}
				  	}
			  	}
			}

			$font_families = Oxygen_VSB_Base::oxygen_vsb_get_font_families_string( $header_font_families );

			if ( $font_families ) {

				echo "
				<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/webfont/1/webfont.js'></script>
				<script type=\"text/javascript\">
				WebFont.load({
					google: {
						families: [$font_families]
					}
				});
				</script>
				";
			}
			
		}

		function get_shortcode_font($m) {

			global $header_font_families;

			$parsed_atts= shortcode_parse_atts( $m[3] );

			if (!isset($parsed_atts['ct_options'])) {
				return substr($m[0], 1, -1);
			}
			$decoded_atts = json_decode( $parsed_atts['ct_options'], true );

			if(!is_array($decoded_atts))
				return substr($m[0], 1, -1);
			
			$states = array();

			// get states styles (original, :hover, ...) from shortcode atts
			foreach ( $decoded_atts as $key => $state_params ) {
				if ( is_array( $state_params ) ) {
					$states[$key] = $state_params;
				}
			}

			foreach ( $states as $key => $atts ) {
				
				//echo $key."\n";
				if ( in_array($key, array("classes", "name", "selector") ) ) {
					continue;
				}

				if( $key == 'media') {

					foreach($atts as $bpkey => $bp) {
						foreach($bp as $bpstatekey => $bpstate) {
							if(isset($bpstate['font-family'])) {
								$value = $bpstate['font-family'];
								if ( is_array( $value ) ) {
									// handle global fonts
									if ( $value[0] == 'global' ) {
										
										$settings 	= get_option("ct_global_settings"); 
										$value 		= $settings['fonts'][$value[1]];
									}
								}
								else {
									$value = htmlspecialchars_decode($value, ENT_QUOTES);
								}

								// skip empty values
								if ( $value === "" ) {
									continue;
								}

								// make font family accessible for web fonts loader
								$header_font_families[] = "$value";
			  				}
						}
					}
				}
				else {
					// loop trough properties (background, color, ...)
					foreach ( $atts as $prop => $value ) {					

						if ( is_array( $value ) ) {
							// handle global fonts
							if ( $prop == "font-family" && $value[0] == 'global' ) {
								
								$settings 	= get_option("ct_global_settings"); 
								$value 		= $settings['fonts'][$value[1]];
							}
						}
						else {
							$value = htmlspecialchars_decode($value, ENT_QUOTES);
						}

						// skip empty values
						if ( $value === "" ) {
							continue;
						}

						// make font family accessible for web fonts loader
						if ( $prop == "font-family" ) {
							$header_font_families[] = "$value";
						}

					} // endforeach
				}
				
			}
			
			return substr($m[0], 1, -1);
			
		}


		/**
		 * Get global settings
		 *
		 * @since 1.1.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_get_global_settings() {

			// get saved settings
			$settings = get_option("ct_global_settings"); 
			
			// defaults
			$settings = wp_parse_args( 
				$settings,
				array ( "fonts" => array(
								'Text' 		=> 'Open Sans',
								'Display' 	=> 'Source Sans Pro' )
					)
			);

			return $settings;
		}


		/**
		 * Get page settings
		 *
		 * @since 1.1.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_get_page_settings( $id ) {

			// get saved settings

			// if it is builder mode, and the aim is to edit inner_content layout, let the outer template's page settings apply
			$ct_inner = defined("OXYGEN_VSB_SHOW_BUILDER") && isset($_REQUEST['ct_inner'])? true:false;
			if($ct_inner) {

				// get the outer template, either it would be the one defined by ct_render_post_using or generic view
				$ct_render_post_using = get_post_meta($id, 'ct_render_post_using', true);
				$ct_other_template = false;

				if($ct_render_post_using && $ct_render_post_using == 'other_template') {
					$ct_other_template = get_post_meta($id, 'ct_other_template', true);
				}

				if(!$ct_other_template || $ct_other_template == 0) { // get the generic template
					
					if(intval($id) == intval(get_option('page_on_front')) || intval($id) == intval(get_option('page_for_posts'))) {
						$template = oxygen_vsb_get_archives_template( $id );
					}
					else {
						$template = oxygen_vsb_get_posts_template( $id );
					}

					if($template) {
						$id = $template->ID;
					}
				}
				else
					$id = $ct_other_template;
			}

			$settings = get_post_meta( $id, "ct_page_settings", true );

			// defaults
			$settings = wp_parse_args( 
				$settings,
				array(
					"max-width" => "1120"
				)
			);

			return $settings;
		}


		/**
		 * Minify CSS
		 *
		 * @since 1.1.1
		 * @author Ilya K.
		 */

		function oxygen_css_minify( $css ) {
			
			// Remove comments
			$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

			// Remove space after colons
			$css = str_replace(': ', ':', $css);

			// Remove new lines and tabs
			$css = str_replace(array("\r\n", "\r", "\n", "\t"), '', $css);

			// Remove excessive spaces
			$css = str_replace(array("     ", "    ", "   ", "  "), ' ', $css);

			// Remove space near commas
			$css = str_replace(', ', ',', $css);
			$css = str_replace(' ,', ',', $css);

			// Remove space before/after brackets
			$css = str_replace('{ ', '{', $css);
			$css = str_replace('} ', '}', $css);
			$css = str_replace(' {', '{', $css);
			$css = str_replace(' }', '}', $css);

			// Remove last semicolon
			$css = str_replace(';}', '}', $css);

			// Remove spaces after semicolon
			$css = str_replace('; ', ';', $css);

			return $css;
		}

		/**
		 * Return body class string in response to GET request
		 *
		 * @since 1.4
		 * @author Ilya K. 
		 */

		function oxygen_vsb_get_body_class() {

			if ( isset( $_GET['ct_get_body_class'] ) && $_GET['ct_get_body_class'] ) {

				echo join( ' ', get_body_class() );
				die();
			}
		}

		/**
		 * This is used to offset the IDs of outer template, when inner_content component is used
		 *
		 * @since 1.2.0
		 * @author Gagan S Goraya.
		 */

		function obfuscate_ids($matches) {
			return $matches[1].((intval($matches[2]) > 0)?(intval($matches[2])+100000):0);
		}

		function obfuscate_selectors($matches) {
			$id =  intval(substr($matches[2], strrpos($matches[2], '_')+1 , strlen($matches[2])-strrpos($matches[2], '_')-1));
			$prefix = substr($matches[2] , 0, strrpos($matches[2], '_')+1);
			return $matches[1].$prefix.(($id > 0)?($id+100000):0).'_post_';
		}


		/**
		 * This is used to offset the depths of inner_content shortcodes when it has to be contained within an outer template
		 *
		 * @since 1.2.0
		 * @author Gagan S Goraya.
		 */

		function oxygen_vsb_offsetDepths($matches) {
			global $oxygen_vsb_offsetDepths_source;
			//print_r($matches);
			$tag = $matches[2];

			$depth = is_numeric($matches[3])?intval($matches[3]):1;
			$newdepth = $depth;
			// if tag has a trailing _, remove it
			if(substr($tag, strlen($tag)-1, 1) == '_')
				$tag = substr($tag, 0, strlen($tag)-1);

			if(isset($oxygen_vsb_offsetDepths_source[$tag])) {
				$newdepth += $oxygen_vsb_offsetDepths_source[$tag];
			}

			return $matches[1].$tag.(($newdepth > 1)?'_'.$newdepth:'');
			
		}

		function oxygen_vsb_undoOffsetDepths($matches) {
			global $oxygen_vsb_offsetDepths_source;
			//print_r($matches);
			$tag = $matches[2];
			$depth = is_numeric($matches[3])?intval($matches[3]):1;
			$newdepth = $depth;
			// if tag has a trailing _, remove it
			if(substr($tag, strlen($tag)-1, 1) == '_')
				$tag = substr($tag, 0, strlen($tag)-1);

			if(isset($oxygen_vsb_offsetDepths_source[$tag])) {
				$newdepth -= $oxygen_vsb_offsetDepths_source[$tag];
			}
			return $matches[1].$tag.(($newdepth > 1)?'_'.$newdepth:'');
			
		}

		function set_oxygen_vsb_offsetDepths_source($parent_id, $shortcodes) {

			global $oxygen_vsb_offsetDepths_source;
			$oxygen_vsb_offsetDepths_source = array();
			$last_parent_id = false;
			$matches = array();
			while($parent_id > 0 && $parent_id !== $last_parent_id) {
				
				preg_match_all("/\[(ct_[^\s\[\]\d]*)[_]?([0-9]?)[^\]]*ct_id[\"|\']?:$parent_id\,[\"|\']?ct_parent[\"|\']?:(\d*)\,/", $shortcodes, $matches);
				//print_r($matches);
				$last_parent_id = $parent_id;
				$parent_id = intval($matches[3][0]);
				$depth = is_numeric($matches[2][0])?intval($matches[2][0]):1;
				$tag = $matches[1][0];

				// if tag has a trailing _, remove it
				if(substr($tag, strlen($tag)-1, 1) == '_')
					$tag = substr($tag, 0, strlen($tag)-1);
				//echo $tag."  ".$depth."  ".$parent_id."\n";

				if(isset($oxygen_vsb_offsetDepths_source[$tag]) ) {
					if($oxygen_vsb_offsetDepths_source[$tag] > $depth) {
						$oxygen_vsb_offsetDepths_source[$tag] = $depth;
					}
				}
				else
					$oxygen_vsb_offsetDepths_source[$tag] = $depth;

			}
		}


		/**
		 * If post/page has Oxygen template applied return empty stylesheet URL, so theme functions.php never run   
		 *
		 * @since 1.4
		 * @author Ilya K.
		 */

		function oxygen_vsb_disable_theme_load( $stylesheet_dir ) {

			// disable theme entirely for now
			return "fake";
		}
		// Need to remove for both parent and child themes


		/**
		 * Filter template name so plugins don't confuse Oxygen with any other theme  
		 *
		 * @since 1.4.1
		 * @author Ilya K.
		 */

		function oxygen_vsb_oxygen_template_name($template) {
			return "oxygen-is-not-a-theme";
		}


		/**
		 * Hook to run on plugin activation for proper CPT init
		 *
		 * @since 1.4.1
		 * @author Ilya K.
		 */

		function oxygen_activate_plugin() {

			// Register CPT the right way
			oxygen_vsb_add_templates_cpt(); // it also hooked into 'init'
			flush_rewrite_rules();
			// set flag
			update_option("oxygen_rewrite_rules_updated", "1");
		}

		function oxygen_vsb_get_pro_link( $links ) {
			$pro_link = array('<a target="_blank" href="' . esc_url( 'https://oxygenapp.com/' ) . '">' . __( 'Get Pro', 'component-theme' ) . '</a>');
			$links = array_merge( $links, $pro_link );
			return $links;
		}

		/**
		 * @param string $type Type of content to filter.  Current options: page_settings, global_settings, style_sheets, 'classes', 'custom_selectors', 'style_sets'
		 * @param array $content
		 *
		 * @return array Filtered array of content
		 */
		static function filter_content( $type, $content = array() ) {
			switch ( $type ) {
				case 'page_settings':
					$allowed_content = array( 'max-width' => 'intval' );
					$filter_keys = false;
					break;
				case 'global_settings':
					$allowed_content = array(
						'fonts' => array(
							'Text' => 'sanitize_text_field',
							'Display' => 'sanitize_text_field',
						),
					);
					$filter_keys = false;
					break;
				case 'style_sheets':
					$allowed_content = array(
						'/^.*$/' => 'base64_encode'
					);
					$filter_keys     = 'sanitize_html_class';
					break;
				case 'classes':
				case 'custom_selectors':
				case 'style_sets':
					$allowed_content = array(
						'/^.*$/' => array(  // Class name
							'/^.*$/' => array(  // States
								'custom-js' => 'base64_encode',
								'custom-css' => 'base64_encode',
								'/^.*$/' => 'sanitize_text_field',  // Arbitrary fields
							)
						)
					);
					$filter_keys = 'sanitize_html_class';
					break;
				default:
				    $allowed_content = array();
                    $filter_keys = false;

			}
			// Allow plugins to adjust the filters of content
			$allowed_content =  apply_filters( 'oxygen_vsb_component_filter_content_allowed', $allowed_content, $type, $content, $filter_keys );

			$new_content = self::filter_array_recursive( $content, $allowed_content, $filter_keys );

			// Allow plugins to expand content that are allowed to be used
			return apply_filters( 'oxygen_vsb_component_filter_content', $new_content, $type, $content, $filter_keys );
		}

		/**
		 * Filter a single piece of content
		 * @param string $data Content to be filtered
		 * @param string|boolean $filter Name of callable function to use for filtering
		 *
		 * @return bool|mixed Filtered content
		 */
		static function filter_single_content( $data, $filter ) {
			if ( is_callable( $filter ) ) {
				return call_user_func( $filter, $data );
			} elseif ( false === $filter ) {
				return false;
			}
			return $data;
		}

		/**
		 * Recursively filter $data array with functions in $filter array
		 *
		 * @param string|array $data Array to be filtered
		 * @param string|array $filter Array containing filters
		 * @param string|boolean $filter_keyname Function to call to filter name of keys or false to not filter
		 *
		 * @return array Filtered array
		 */
		static function filter_array_recursive( $data, $filter, $filter_keyname = false ) {
			if ( is_array( $filter ) ) {
				$new_data = array();
				foreach ( $filter as $filter_key => $filter_value ) {
					// Walk filter array matching regexp and absolute matches)
					if ( isset( $data[ $filter_key ] ) ) {
						// Handle literal filters
						if ( isset( $filter_keyname ) && is_callable( $filter_keyname ) ) {
							$new_key = call_user_func( $filter_keyname, $filter_key );
						} else {
							$new_key = $filter_key;
						}

						if ( is_array( $filter_value ) ) {
							$new_data[ $new_key ] = self::filter_array_recursive( $data[ $filter_key ], $filter_value, $filter_keyname );
						} else {
							$new_data[ $new_key ] = self::filter_single_content( $data[ $filter_key ], $filter_value );
						}
					} elseif ( '/' === $filter_key[0] ) {
						// Key regexp
						$matched_keys = preg_grep( $filter_key, array_keys( $data ) );
						foreach ( $matched_keys as $key ) {
							if ( isset( $filter_keyname ) && is_callable( $filter_keyname ) ) {
								$new_key = call_user_func( $filter_keyname, $key );
							} else {
								$new_key = $key;
							}
							if ( !isset( $new_data[ $new_key ] ) ) {
							    // Only allow entry to be filtered by first match
								$new_data[ $new_key ] = self::filter_array_recursive( $data[ $key ], $filter_value, $filter_keyname );
							}
						}
					}

				}
			} else {
				return self::filter_single_content( $data, $filter );
			}
			return $new_data;

		}
	}


	add_action('admin_init', array('Oxygen_VSB_Base','oxygen_vsb_plugin_setup'));
	add_action('init', array('Oxygen_VSB_Base','oxygen_vsb_is_show_builder'), 1 );
	add_action('wp', array('Oxygen_VSB_Base','oxygen_vsb_check_user_caps'), 1 );
	add_action( 'admin_bar_menu',  array('Oxygen_VSB_Base','oxygen_vsb_oxygen_admin_menu'), 1000 );
	add_action('wp', array('Oxygen_VSB_Base','oxygen_vsb_editing_template'), 1 );
	add_action( 'wp_enqueue_scripts',  array('Oxygen_VSB_Base','oxygen_vsb_enqueue_scripts') );
	add_action('init', array('Oxygen_VSB_Base','oxygen_vsb_init'), 2);
	add_action('wp',  array('Oxygen_VSB_Base','oxygen_vsb_get_base'));
	add_action('oxygen_vsb_footer_styles',  array('Oxygen_VSB_Base','oxygen_vsb_css_styles'));
	add_action('wp_footer',  array('Oxygen_VSB_Base','oxygen_vsb_footer_stylesheets_hook'));
	add_action('wp_footer',  array('Oxygen_VSB_Base','oxygen_vsb_footer_script_hook'));
	add_action('wp_footer',  array('Oxygen_VSB_Base','oxygen_vsb_chrome_modal'));
	add_action('init',  array('Oxygen_VSB_Base','oxygen_vsb_theme_support'));
	add_action( 'send_headers',  array('Oxygen_VSB_Base','oxygen_vsb_add_headers') );
	add_action( 'wp_head',  array('Oxygen_VSB_Base','add_web_font'), 0 );
	
	add_action( 'plugin_action_links_' . plugin_basename(dirname(dirname(__FILE__)).'/functions.php'), array('Oxygen_VSB_Base','oxygen_vsb_get_pro_link') );

	add_filter('run_wptexturize',  '__return_false');
	add_filter( 'template_include',  array('Oxygen_VSB_Base','oxygen_vsb_css_output'), 99 );
	add_filter( 'template_include',  array('Oxygen_VSB_Base','oxygen_vsb_determine_render_template'), 98 );
	add_filter('body_class',  array('Oxygen_VSB_Base','oxygen_vsb_body_class'));
	add_filter('template_directory',  array('Oxygen_VSB_Base','oxygen_vsb_disable_theme_load'), 1, 1);
	add_filter('stylesheet_directory',  array('Oxygen_VSB_Base','oxygen_vsb_disable_theme_load'), 1, 1);
	add_filter('template',  array('Oxygen_VSB_Base','oxygen_vsb_oxygen_template_name'));
	add_filter('validate_current_theme',  '__return_false');



	register_activation_hook( OXYGEN_VSB_PLUGIN_MAIN_FILE, array('Oxygen_VSB_Base','oxygen_activate_plugin') );
	// flush rules on deactivation
	register_deactivation_hook( OXYGEN_VSB_PLUGIN_MAIN_FILE, 'flush_rewrite_rules' );
}

