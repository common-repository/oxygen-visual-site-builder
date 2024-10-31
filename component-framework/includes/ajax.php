<?php

function oxygen_vsb_prepare_inner_content_for_save($children, $inner_content_id) {
	global $oxygen_vsb_offsetDepths_source;

	foreach($children as $key => $value) {

		// replace the parent_id of the elements having that equal to the ID of the inner_content module with 0 (assign to root)
		if($children[$key]['options']['ct_parent'] == $inner_content_id) {
			$children[$key]['options']['ct_parent'] = 0;
		}

		// oxygen_vsb_undoOffsetDepths
		if(isset($oxygen_vsb_offsetDepths_source[$children[$key]['name']])) {
			$children[$key]['depth'] -= $oxygen_vsb_offsetDepths_source[$children[$key]['name']];
		}

		if($children[$key]['children']) {
			$children[$key]['children'] = oxygen_vsb_prepare_inner_content_for_save($children[$key]['children'], $inner_content_id);
		}
	}

	return $children;
}

function oxygen_vsb_find_inner_contents($children) {
	global $oxygen_vsb_offsetDepths_source;
	$inner_content = false;
	
	foreach($children as $key => $value) {
		
		if($inner_content !== false) {
			continue;
		}

		$name = $children[$key]['name'];

		if($name == 'ct_inner_content') {
			$inner_content =  $children[$key];
		}
		else {
			//set_oxygen_vsb_offsetDepths_source

			$depth = $children[$key]['depth'] || 0;

			if(isset($oxygen_vsb_offsetDepths_source[$name]) ) {
				if($oxygen_vsb_offsetDepths_source[$name] > $depth) {
					$oxygen_vsb_offsetDepths_source[$name] = $depth;
				}
			}
			else
				$oxygen_vsb_offsetDepths_source[$name] = $depth;

			if($children[$key]['children']) {
				$inner_content = oxygen_vsb_find_inner_contents($children[$key]['children']);
			}
		}
	}

	return $inner_content;
}

/**
 * Receive Components Tree and other options as JSON object
 * and save as post conent and meta
 * 
 * @since 0.1
 */

function oxygen_vsb_save_components_tree_as_post() {
	
	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( ! isset( $nonce, $post_id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' );
	}

	// check if user can edit this post
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		die ( 'Security check' );
	}

	// get all data JSON
	$data = file_get_contents('php://input');

	// encode and separate tree from options
	$data = json_decode($data, true);

	$params = $data['params'];
	$tree 	= $data['tree'];

	// settings
	$page_settings	 	= Oxygen_VSB_Base::filter_content( 'page_settings', $params['page_settings'] );
	$global_settings 	= Oxygen_VSB_Base::filter_content( 'global_settings', $params['global_settings'] );

	// classes and selectors
	$classes		 	= Oxygen_VSB_Base::filter_content( 'classes', $params['classes'] );
	$style_sets 		= Oxygen_VSB_Base::filter_content( 'style_sets', $params['style_sets'] );
	$custom_selectors 	= isset($params['custom_selectors']) ? Oxygen_VSB_Base::filter_content( 'custom_selectors', $params['custom_selectors'] ) : array();
	$style_sheets 		= Oxygen_VSB_Base::filter_content( 'style_sheets', $params['style_sheets'] );

	// if it is page's inner content, then discard all the template related shortcodes here
	$ct_inner = isset($_REQUEST['ct_inner'])? true:false;

	if($ct_inner) {

		// find the inner contents inside the $tree['children'] and separate it from the main tree, assign it back to $tree['children']
		global $oxygen_vsb_offsetDepths_source;
		$oxygen_vsb_offsetDepths_source = array();

		$inner_content = oxygen_vsb_find_inner_contents($tree['children']);
		
		$tree['children'] = oxygen_vsb_prepare_inner_content_for_save($inner_content['children'], $inner_content['id']);

	}

	// base64 encode js and css code in the IDs
	$tree['children'] = oxygen_vsb_base64_encode_decode_tree($tree['children']);

	// code tree back to JSON to pass into old function
	$components_tree_json = json_encode($tree);

	ob_start();

	// transform JSON to shortcodes
	$shortcodes = oxygen_vsb_components_json_to_shortcodes( $components_tree_json );

	// we don't need anything to be output by custom shortcodes
	ob_clean();
	
	// Save as post Meta (NEW WAY)
	update_post_meta( $post_id, 'ct_builder_shortcodes', $shortcodes );
	do_action( 'save_post', $post_id, get_post( $post_id ) );
  	
  	// Process settings
  	// Page
  	$page_settings_saved 	= update_post_meta( $post_id, "ct_page_settings", $page_settings );

  	// Global
  	$global_settings_saved 	= update_option("ct_global_settings", $global_settings );

  	// Process classes
  	//$classes 				= json_decode( stripslashes( $classes ), true );

/*  	// base64 encode js and css code in the classes
	foreach($classes as $key => $class) {

		foreach( $class as $statekey => $state) {
			
			if( $statekey == "media") {
				foreach($state as $bpkey => $bp) {
					foreach($bp as $bpstatekey => $bp) {
						if(isset($class[$statekey][$bpkey][$bpstatekey]['custom-css']))
		  					$classes[$key][$statekey][$bpkey][$bpstatekey]['custom-css'] = base64_encode($classes[$key][$statekey][$bpkey][$bpstatekey]['custom-css']);

		  				if(isset($class[$statekey][$bpkey][$bpstatekey]['custom-js']))
		  					$classes[$key][$statekey][$bpkey][$bpstatekey]['custom-js'] = base64_encode($classes[$key][$statekey][$bpkey][$bpstatekey]['custom-js']);  						
					}
				}
			}
			else {

		  		if(isset($class[$statekey]['custom-css']))
		  			$classes[$key][$statekey]['custom-css'] = base64_encode($class[$statekey]['custom-css']);
		  		if(isset($class[$statekey]['custom-js']))
		  			$classes[$key][$statekey]['custom-js'] = base64_encode($class[$statekey]['custom-js']);
		  	}
	  	}
  	}*/
  	
  	$classes_saved = update_option("ct_components_classes", $classes );

  	// Process custom CSS selectors
  	$custom_selectors_saved = update_option("ct_custom_selectors", $custom_selectors );
  	$style_sets_updated 	= update_option("ct_style_sets", $style_sets );

  	$style_sheets_saved = update_option("ct_style_sheets", $style_sheets );

  	$return_object = array(
  		
  		"page_settings_saved" 	 => $page_settings_saved, // true or false
  		"global_settings_saved"  => $global_settings_saved, // true or false
  		
  		"classes_saved" 		 => $classes_saved, // true or false
  		"custom_selectors_saved" => $custom_selectors_saved, // true or false
  		"style_sheets_saved" 	 => $style_sheets_saved, // true or false
  		
  	);

	// echo JSON
  	header('Content-Type: application/json');
  	echo json_encode( $return_object );
	die();
}
add_action('wp_ajax_ct_save_components_tree', 'oxygen_vsb_save_components_tree_as_post');

/**
 * Helper function to base 64 encode/decode custom-css and js recursively through the tree
 * default is encode operation
 * Set second param to be true, for decode operation
 * 
 * @since 0.3.4
 * @author gagan goraya 
 */

function oxygen_vsb_base64_encode_decode_tree($children, $decode = false) {

	if(!is_array($children))
		return array();


	foreach($children as $key => $item) {

		if(isset($item['children']))
			$children[$key]['children'] = oxygen_vsb_base64_encode_decode_tree( $item['children'], $decode );
		
		if(!isset($item['options']))
			continue;

		foreach($item['options'] as $optionkey => $option) {
			// ignore ct_id // ignore ct_parent

			if($optionkey == 'ct_id' || $optionkey == 'ct_parent' || $optionkey == 'selector' || $optionkey == 'ct_content')
				continue;

			// if media then 
			if($optionkey == 'media') {
				foreach($option as $mediakey => $mediaoption) {
					foreach($mediaoption as $mediastatekey => $mediastate) {
						if(isset($mediastate['custom-css'])) {
							if($decode) {
								if(!strpos($mediastate['custom-css'], ' ')) {
									$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['custom-css'] = base64_decode($mediastate['custom-css']);
								}
							}
							else {
								$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['custom-css'] = base64_encode($mediastate['custom-css']);
							}
						}
						if(isset($mediastate['custom-js'])) {
							if($decode) {
								if(!strpos($mediastate['custom-js'], ' ')) {
									$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['custom-js'] = base64_decode($mediastate['custom-js']);
								}
							}
							else {
								$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['custom-js'] = base64_encode($mediastate['custom-js']);
							}
						}

						// base64 encode the content of the before and after states
						if(Oxygen_VSB_Base::is_pseudo_element($mediastatekey)) {
							if(isset($mediastate['content'])) {
								if($decode) {
									$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['content'] = base64_decode($mediastate['content']);
								}
								else {
									$children[$key]['options'][$optionkey][$mediakey][$mediastatekey]['content'] = base64_encode($mediastate['content']);
								}
							}
						}
					}
				}
				continue;
			}


			// for all others, do the thing
			if(isset($option['custom-css'])) {
				if($decode) {
					if(!strpos($option['custom-css'], ' ')) {
						$children[$key]['options'][$optionkey]['custom-css'] = base64_decode($option['custom-css']);
					}
				}
				else {
					$children[$key]['options'][$optionkey]['custom-css'] = base64_encode($option['custom-css']);
				}
			}
			if(isset($option['custom-js'])) {
				if($decode) {
					if(!strpos($option['custom-js'], ' ')) {
						$children[$key]['options'][$optionkey]['custom-js'] = base64_decode($option['custom-js']);
					}
				}
				else {
					$children[$key]['options'][$optionkey]['custom-js'] = base64_encode($option['custom-js']);
				}
			}
			
			// base64 encode the content of the before and after states
			if(Oxygen_VSB_Base::is_pseudo_element($optionkey)) {
				if(isset($option['content'])) {
					if($decode) {
						//if(substr($option['content'], -2) == '==') {
							$children[$key]['options'][$optionkey]['content'] = base64_decode($option['content']);
						//}
					}
					else {
						$children[$key]['options'][$optionkey]['content'] = base64_encode($option['content']);
					}
				}
			}
		}

	}

	return $children;
}

/**
 * Save single component (or array of same level components)
 * as "reusable_part" view (ct_tempalte CPT)
 * 
 * @since 0.2.3 
 */

function oxygen_vsb_save_component_as_view() {

	$name 		= sanitize_text_field( $_REQUEST['name'] );
	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( !isset( $nonce, $post_id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( '0' ); 
	}

	// check if user can publish posts
	if ( ! current_user_can( 'publish_posts' ) ) {
		die ( '0' );
	}

	$component 	= file_get_contents('php://input');
	$tree 		= json_decode($component, true);

	// base64 encode js and css code in the IDs
	$tree["children"] = oxygen_vsb_base64_encode_decode_tree($tree['children']);

	$component = json_encode($tree);

	//var_dump($component);

	$shortcodes = oxygen_vsb_components_json_to_shortcodes( $component, true );

	//var_dump($shortcodes);

	$post = array(
		'post_title'	=> $name,
		'post_type' 	=> "ct_template",
		'post_status'	=> "publish",
		// TODO: check who is a post author
		//'post_author' 	=> ""
	);
	
	// Insert the post into the database
	$post_id = wp_insert_post( $post );

	if ( $post_id !== 0 ) {
		$meta = update_post_meta( $post_id, 'ct_template_type', "reusable_part");
		update_post_meta( $post_id, 'ct_builder_shortcodes', $shortcodes );
	}

	// echo JSON
	header('Content-Type: application/json');
	echo $post_id;
	die();
}
add_action('wp_ajax_ct_save_component_as_view', 'oxygen_vsb_save_component_as_view');

function oxygen_vsb_embed_inner_content($children, $inner_content) {
	foreach($children as $key => $val) {
		$name = $children[$key]['name'];
		if($name == 'ct_inner_content') {
			$children[$key]['children'] = $inner_content;
		}
		elseif(isset($children[$key]['children'])) { // go recursive
			$children[$key]['children'] = oxygen_vsb_embed_inner_content($children[$key]['children'], $inner_content);
		}
	}
	return $children;
}

function oxygen_vsb_prepare_outer_template($children) {
	global $oxygen_vsb_offsetDepths_source;
	
	$inner_content = false;
	$container_id = false;
	$parent_id = false;

	foreach($children as $key => $val) {

		$name = $children[$key]['name'];

		if($children[$key]['options']['ct_id'] > 0) {
			// obfuscate selector
			$children[$key]['options']['selector'] = str_replace('_'.$children[$key]['id'].'_post_', '_'.($children[$key]['id']+100000).'_post_', $children[$key]['options']['selector']);
			// obfuscate Ids 
			$children[$key]['options']['ct_id'] += 100000; 
		}
		
		if($children[$key]['options']['ct_parent'] > 0) { // obfuscate parent ids
			$children[$key]['options']['ct_parent'] += 100000;
		}

		

		if($name == 'ct_inner_content') {
			$inner_content = $children[$key];
			$container_id = $children[$key]['options']['ct_id'];;
			$parent_id = $children[$key]['options']['ct_parent'];
		}

		//set_oxygen_vsb_offsetDepths_source
		$depth = $children[$key]['depth'] || 0;

		if(isset($oxygen_vsb_offsetDepths_source[$name]) ) {
			if($oxygen_vsb_offsetDepths_source[$name] > $depth) {
				$oxygen_vsb_offsetDepths_source[$name] = $depth;
			}
		}
		else
			$oxygen_vsb_offsetDepths_source[$name] = $depth;

		if(isset($children[$key]['children'])) { // go recursive
			$prepared_outer_content = oxygen_vsb_prepare_outer_template($children[$key]['children']);
			$children[$key]['children'] = $prepared_outer_content['content'];

			if($prepared_outer_content['inner_content']) {
				$inner_content = $prepared_outer_content['inner_content'];
			}

			if($prepared_outer_content['container_id']) {
				$container_id = $prepared_outer_content['container_id'];
			}

			if($prepared_outer_content['parent_id']) {
				$parent_id = $prepared_outer_content['parent_id'];
			}
		}

		$children[$key]['id'] = $children[$key]['options']['ct_id'];
	}

	return array('content' => $children, 'inner_content' => $inner_content, 'container_id' => $container_id, 'parent_id' => $parent_id);
}

function oxygen_vsb_prepare_inner_content($children, $container_id) {
	
	global $oxygen_vsb_offsetDepths_source;

	foreach($children as $key => $val) {

		if($children[$key]['options']['ct_parent'] === 0) {
			$children[$key]['options']['ct_parent'] = $container_id;
		}
		
		// apply oxygen_vsb_offsetDepths
		if(isset($oxygen_vsb_offsetDepths_source[$children[$key]['name']])) {
			$children[$key]['depth'] += $oxygen_vsb_offsetDepths_source[$children[$key]['name']];
		}

		if($children[$key]['children']) {
			$children[$key]['children'] = oxygen_vsb_prepare_inner_content($children[$key]['children'], $container_id);
		}
	}

	return $children;

}

/**
 * Return post Components Tree as a JSON object 
 * in response to AJAX call
 * 
 * @since 0.1.7
 * @author Ilya K.
 */

function oxygen_vsb_get_components_tree() {

	// possible fix
	//error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNINGS|E_DEPRECATED));

	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );
	$id 		= intval( $_REQUEST['id'] );

	// check nonce
	if ( !isset( $nonce, $post_id, $id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' ); 
	}

	// check user role
	if ( ! current_user_can( 'read_post', $id ) ) {
		die ( 'Security check' );
	}
	
	// if the intended target to be edited is the inner content
	$shortcodes = false;
	$ct_inner = isset($_REQUEST['ct_inner'])?true:false;
	
	$singular_shortcodes = get_post_meta($id, "ct_builder_shortcodes", true);
	
	// check for the validity of the $singular_shortcodes here only
	$singular_shortcodes = oxygen_vsb_parse_shortcodes($singular_shortcodes);

	if ( $ct_inner ) {

		$ct_render_post_using = get_post_meta( $id, "ct_render_post_using", true );
		
		if($ct_render_post_using != 'other_template')
			$shortcodes = get_post_meta( $id, "ct_builder_shortcodes", true );
		else
			$shortcodes = false;

		if ( !$shortcodes ) { // it is not a custom view

			$template_id = get_post_meta( $id, 'ct_other_template', true );

			if($template_id) {
				$template = get_post($template_id);
			}
			else {
				if(intval($id) == intval(get_option('page_on_front')) || intval($id) == intval(get_option('page_for_posts')))
					$template = oxygen_vsb_get_archives_template( $id );
				else
					$template = oxygen_vsb_get_posts_template( $id );
			}

			// get template shortcodes
			$shortcodes = get_post_meta( $template->ID, "ct_builder_shortcodes", true );
		}

		if($shortcodes) {
			// verify the validity of the $shortcodes here, i.e., check for the signs
			$shortcodes = oxygen_vsb_parse_shortcodes( $shortcodes ); // returns valid and parsed shortcodes
			
			//if(empty($singular_shortcodes)) {
				/*$content_post = get_post($id);
				$content = $content_post->post_content;
				$content = apply_filters('the_content', $content);
				$content = trim(str_replace(']]>', ']]&gt;', $content));*/

				//if(!empty($content))
			//	$singular_shortcodes = '[ct_code_block ct_options=\'{"ct_id":2,"ct_parent":0,"selector":"ct_code_block_2_post_7","original":{"code-php":"PD9waHAKCXdoaWxlKGhhdmVfcG9zdHMoKSkgewogICAgCXRoZV9wb3N0KCk7CgkJdGhlX2NvbnRlbnQoKTsKICAgIH0KPz4="},"activeselector":false}\'][/ct_code_block]';
			//}

		
			//recursively obfuscate_ids: ct_id and ct_parent of all elements in $parsed, also obfuscate_selectors
			global $oxygen_vsb_offsetDepths_source;
			$oxygen_vsb_offsetDepths_source = array();

			$prepared_outer_content = oxygen_vsb_prepare_outer_template($shortcodes['content']);
			$shortcodes['content'] = $prepared_outer_content['content'];

			$container_id = $prepared_outer_content['container_id'];
			$parent_id = $prepared_outer_content['parent_id'];
			
			$singular_shortcodes['content'] = oxygen_vsb_prepare_inner_content($singular_shortcodes['content'], $container_id);

			$shortcodes['content'] = oxygen_vsb_embed_inner_content($shortcodes['content'], $singular_shortcodes['content']);

		}

	}

	if(!$shortcodes) {
		$shortcodes = $singular_shortcodes;
	}
	
	if($shortcodes['content']) {
		$root = array ( 
			"id"	=> 0,
			"name" 	=> "root",
			"depth"	=> 0 
		);
		
		$root['children'] = $shortcodes['content'];

		$components_tree = json_encode( $root );

		$json = $components_tree;
	}

	// base 64 decode all the custom-css and custom-js down the tree
	$tree = json_decode($json, true);


	$tree['children'] = oxygen_vsb_base64_encode_decode_tree($tree['children'], true);

	$json = json_encode($tree);

	// echo response
  	header('Content-Type: text/html');
  	echo $json;
	die();
}

add_action('wp_ajax_ct_get_components_tree', 'oxygen_vsb_get_components_tree');

/**
 * Adds a flag to the options that the non-chrome-browser 
 * warning in the builder has been dismissed
 * 
 * @since 0.3.4
 * @author Gagan Goraya.
 */

function oxygen_vsb_remove_chrome_modal() {

	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( ! isset( $nonce, $post_id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' ); 
	}

	// check user role
	if ( ! current_user_can( 'edit_posts' ) ) {
		die ( 'Security check' );
	}

	update_option('ct_chrome_modal', true);
	die();
}
add_action('wp_ajax_ct_remove_chrome_modal', 'oxygen_vsb_remove_chrome_modal');

/**
 * Return SVG Icon Sets
 * 
 * @since 0.2.1
 */

function oxygen_vsb_get_svg_icon_sets() {

	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( ! isset( $nonce, $post_id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' ); 
	}

	// check user role
	if ( ! current_user_can( 'edit_posts' ) ) {
		die ( 'Security check' );
	}

	$svg_sets = get_option("ct_svg_sets", array() );

	// Convert XML sets to Objects
	foreach ( $svg_sets as $name => $set ) {

		$xml = simplexml_load_string($set);

		$hasSymbols = true;

		foreach($xml->children() as $def) {
			
			if($def->getName() == 'defs') {
				
				foreach($def->children() as $symbol) {
					if($symbol->getName() == 'symbol') {
						$symbol['id'] = str_replace(str_replace(' ', '', $name), '', $symbol['id']);
					} else {
						$hasSymbols = false;
					}
				}
			} else {
				
				$hasSymbols = false;
			}
		}
		
		if( $hasSymbols ) {
			
			$set = $xml->asXML();
			$svg_sets[$name] = new SimpleXMLElement( $set );
		}
		else {
			unset($svg_sets[$name]);
		}
	}

	$json = json_encode( $svg_sets );

	// echo JSON
	header('Content-Type: application/json');
	echo $json;
	die();
}
add_action('wp_ajax_ct_get_svg_icon_sets', 'oxygen_vsb_get_svg_icon_sets');


/**
 * Return template/view data with single post or term posts as JSON
 * 
 * @since 0.1.7
 * @author Ilya K.
 */

function oxygen_vsb_get_template_data() {

	$template_id 		= intval( $_REQUEST['template_id'] );
	$preview_post_id 	= intval( $_REQUEST['preview_post_id'] );
	$nonce  			= $_REQUEST['nonce'];
	$post_id 			= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( ! isset( $nonce, $post_id ) ||  ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' ); 
	}

	// check user role
	if ( ! current_user_can( 'read_post', $post_id ) ) {
		die ( 'Security check' );
	}

	$type = get_post_meta( $template_id, 'ct_template_type', true );

	if ( "single_post" === $type ) {
		// single view
		$data = oxygen_vsb_get_templates_post( $template_id, $preview_post_id );
	} elseif ( "archive" === $type ) {
		// archive view
		$data = oxygen_vsb_get_templates_term( $template_id, $preview_post_id );
	}
	
	// make GET request to permalink to retrive body class
	$post_data = (array) $data["postData"];

	if($post_data['ID']) {
		// switch main wp_query for the time being into the post in question, to get its body classes
		global $wp_query;
		
		$wp_query = new WP_Query( array('p' => $post_data['ID'], 'post_type' => 'any'));
		
		$data["bodyClass"] = join(' ', get_body_class());
		
		// reset the wp_main query
		wp_reset_query();

	}
	// Return JSON
  	header('Content-Type: application/json');
	echo json_encode($data);
	die();
}

add_action('wp_ajax_ct_get_template_data', 'oxygen_vsb_get_template_data');


/**
 * Return single post object as JSON by ID including shortcodes
 * 
 * @since 0.2.3
 * @author Ilya K.
 */

function oxygen_vsb_get_post_data() {
	
	$nonce  	= $_REQUEST['nonce'];
	$post_id 	= intval( $_REQUEST['post_id'] );

	// check nonce
	if ( ! isset( $nonce, $post_id ) || ! wp_verify_nonce( $nonce, 'oxygen-nonce-' . $post_id ) ) {
	    // This nonce is not valid.
	    die( 'Security check' ); 
	}

	$id 	= intval( $_REQUEST['id'] );
	$post 	= get_post( $id );

	// check user role
	if ( ! current_user_can( 'read_post', $id ) ) {
		die ( 'Security check' );
	}

	if ( $post ) {
		$data = oxygen_vsb_filter_post_object( $post );
	}

	// base 64 decode all the custom-css and custom-js down the tree
	$data->post_tree = oxygen_vsb_base64_encode_decode_tree($data->post_tree, true);

	// Echo JSON
  	header('Content-Type: application/json');
	echo json_encode($data);
	die();
}
add_action('wp_ajax_ct_get_post_data', 'oxygen_vsb_get_post_data');
