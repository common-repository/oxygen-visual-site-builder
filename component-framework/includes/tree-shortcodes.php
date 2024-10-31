<?php 

/**
 * Interface to transform Components Tree JSON object to
 * WordPress shortcodes and vice versa.
 *  
 */


/**
 * Transform JSON Components Tree to WordPress nested shortcodes
 * 
 * @return string
 * @since 0.1
 */

function oxygen_vsb_components_json_to_shortcodes( $json, $reusable = false ) {

	//var_dump($json);

	$components_tree = json_decode( $json, true );

	//var_dump($components_tree);

	if ( $reusable ) {
		$components_tree['children'] = oxygen_vsb_update_ids( $components_tree['children'], 1, $components_tree );
	};

	//var_dump($components_tree);

	if ( is_null ( $components_tree ) )
		return false;

	//return print_r( $components_tree['children'], true);

	$output = oxygen_vsb_parse_components_tree( $components_tree['children'] );

	return $output;
}


/**
 * Recursive function that actually transform an Object to WordPress shortcodes
 *
 * @since 0.1
 */

function oxygen_vsb_parse_components_tree( $components_tree ) {
	global $oxygen_signature;
	//var_dump($components_tree);

	if ( !is_array( $components_tree ) ) {
		return false;
	}

	$output = "";

	foreach( $components_tree as $id => $item ) {
		$name = sanitize_text_field( $item['name'] );
		$ct_options = null;
		$shortcode_atts = array();
		$ct_options_string = null;
		$ct_content = '';
		$nested = false;

		// check if nested column or section
		if ( $item['depth'] > 1 && in_array( $item['name'], array( 'ct_link', 'ct_section', 'ct_container', 'ct_inner_content', 'ct_columns', 'ct_column', 'ct_div_block' ) ) ) {
			$nested = true;
		}

		// add depth suffix if needed
		if ( $nested ) {
			$name .= '_' . $item['depth'];
		}

		// add shortcode parameters
		if ( is_array( $item['options'] ) ) {
			
			foreach ( $item['options'] as $key => $value ) {
	
				if ( $key == "url" && $item['name'] == "embed" ) {
					unset( $item['options']['url'] );
				}

				if ( $key == "classes" ) {
					continue;
				}

				if ( is_array( $value ) ) {

					if ( ! empty( $value ) ) {

						foreach ( $value as $array_key => $array_value ) {

							if ( $array_key == "custom-css" ) {
								$item['options'][$key][$array_key] = oxygen_vsb_normalize_custom_css( $array_value );
							}

							if ( $array_key == "code-php" ||
								 $array_key == "code-css" ||
								 $array_key == "code-js"
							) {
								$item['options'][$key][$array_key] = base64_encode( $item['options'][$key][$array_key] );
							}

							if ( $array_key == "alt" ) {
								$item['options'][$key][$array_key] = base64_encode( $item['options'][$key][$array_key] );	
							}
						}
					}
					else {
						unset( $item['options'][$key] );
					}
				}
				elseif ( $key == "ct_content" ) {

					$ct_content = $item['options']['ct_content'];

					unset($item['options'][$key]);// = htmlspecialchars( $value, ENT_QUOTES );
				}
			}

			$value = json_encode( $item['options'], JSON_FORCE_OBJECT );

			$value = oxygen_vsb_unicode_decode( $value );
			$shortcode_atts['ct_options'] = $value;
			$ct_options_string = "ct_options='{$value}'";
		}

		// handle embed URL
		if ( isset( $item['options']['url'] ) && $item['name'] == "embed" ) {
			$ct_content .= $item['options']['url'];
		}

		if ( $item['children'] ) {
			if ( !empty( $ct_content ) ) {
				// if we have content and children at the same time
				$temp_content = $ct_content;

				$shortcodes = array();

				// get shortcodes for each child
				foreach( $item['children'] as $id => $child ) {
					$shortcodes["<span id=\"ct-placeholder-{$child['id']}\"></span>"] = oxygen_vsb_parse_components_tree( array( $id => $child ) );
				}

				// replace placeholders with shortcodes
				$temp_content = strtr($temp_content, $shortcodes);

				// output
				$ct_content .= $temp_content;
			} else {
				// go deeper into the tree if item has children and have no content
				$ct_content .= oxygen_vsb_parse_components_tree( $item['children'] );
			}
		} elseif ( !empty( $ct_content ) ) {
			// add content with escaped slashes and quotes
			$ct_content = addslashes($ct_content);
		}

		// Component classes aren't coupled here, so leverage WordPress filters for validation
		if ( false !== $component = apply_filters( "oxygen_vsb_filter_{$item['name']}", array( 'item' => $item, 'content' => $ct_content ) ) ) {
			// Generate signature
			$signature = $oxygen_signature->generate_signature_shortcode_string( $name, $shortcode_atts, $component['content'] );
			// Generate output
			$output .= "[{$name} {$signature} {$ct_options_string}]{$component['content']}[/{$name}]";
		}

	}

	return $output;
}


/**
 * Update IDs for Re-usable parts start from $counter
 *
 * @since 0.2.3
 */

function oxygen_vsb_update_ids( $components_tree, $count, &$parent ) {

	global $counter;

	$counter = $count;

	foreach ( $components_tree as $key => $child ) {

		// update placeholder id's
		$parent['ct_content'] = str_replace("ct-placeholder-" . $components_tree[$key]['options']['ct_id'], 
											"ct-placeholder-" . $counter, 
											$parent['ct_content']);

		$components_tree[$key]['id'] 					= $counter;
		$components_tree[$key]['options']['ct_id'] 		= $counter;
		$components_tree[$key]['options']['ct_parent'] 	= $parent['id'];

		$counter++;

		if ( $components_tree[$key]['children'] ) {
			$components_tree[$key]['children'] = oxygen_vsb_update_ids( $components_tree[$key]['children'], $counter, $components_tree[$key] );
		}
	}

	return $components_tree;
}

/**
 * Recursive function that actually transform WordPress shortcodes to Array
 *
 * @return Array
 * @since 0.1
 */

function oxygen_vsb_parse_shortcodes( $content, $is_first = true, $verify_signature = true ) {

	$pattern = get_shortcode_regex();
	preg_match_all( '/'. $pattern .'/s', $content, $matches );

	$tags 			= $matches[0];
	$names 			= $matches[2];
	$args 			= $matches[3];
	$inner_content 	= $matches[5];
	
	if ( ! $args ) {
		return array(
			'is_shortcode' => false,
			'content' => $content );
	}

	if ( $is_first ) {
		
		// check if 
		$total_length = 0;
		foreach ( $tags as $tag ) {
			$total_length += strlen($tag);
		}

		if ( $total_length != strlen($content) ) {
			return array(
				'is_shortcode' => false,
				'content' => $content );
		}
	}

	$shortcodes = array();

	foreach ( $args as $key => $value ) {

		$shortcode 	= array();
		$depth 		= false;

		$options 	= shortcode_parse_atts( $value );

		// skip shortcode if no shortcode params
		if ( ! is_array( $options ) ) {
			continue;
		}
		global $oxygen_signature;
		// Skip shortcodes that are not properly signed
		if ( $verify_signature && ! $oxygen_signature->verify_signature( $names[ $key ], $options, $inner_content[ $key ] ) ) {
			continue;
		}



		/*$options['ct_options'] = str_replace("\n", "\\n", $options['ct_options']);
		$options['ct_options'] = str_replace("\r", "\\r", $options['ct_options']);
		$options['ct_options'] = str_replace("\t", "\\t", $options['ct_options']);*/

		$options 	= json_decode( $options['ct_options'], true );
		$id 		= $options['ct_id'];

		//var_dump($options);

		$sanitized_options = array();
		
		// sanitize option names
		if ( $options ) {
			foreach ( $options as $name => $value ) {

				$array = $value;

				if ( is_array( $array ) && ! empty( $array ) && $name != "classes" ) {

					foreach ( $array as $array_key => $array_value) {

						if ( $array_key == "code-php" ||
							 $array_key == "code-css" ||
							 $array_key == "code-js" )
						{
							$array[$array_key] = base64_decode( $array_value );
						}

						if ( $array_key == "alt" ) {
							$array[$array_key] = base64_decode( $array_value );
						}

						if ( $array_key == "custom-css" ) {
							$array[$array_key] = oxygen_vsb_prettify_custom_css( $array[$array_key] );
						}
					}

					$value = $array;
				}
				
				$sanitized_options[$name] = $value;
			}
		}
		
		$shortcode['id'] 		= $id;
		$shortcode['name'] 		= $names[$key];
		$shortcode['options'] 	= $sanitized_options;

		// add depth 1 
		if ( $shortcode['name'] == "ct_column" || 
			 $shortcode['name'] == "ct_columns" || 
			 $shortcode['name'] == "ct_container" || 
			 $shortcode['name'] == "ct_section" ||
			 $shortcode['name'] == "ct_div_block" || 
			 $shortcode['name'] == "ct_inner_content" ||
			 $shortcode['name'] == "ct_link" ) {

			$depth = 1;
		}

		// strip from depth postfix
		if ( strpos( $shortcode['name'], "ct_section_" ) !== false ) {
			
			$depth = substr( $shortcode['name'], 11 );
			$shortcode['name'] 	= "ct_section";
		}

		if ( strpos( $shortcode['name'], "ct_columns_" ) !== false ) {

			$depth = substr( $shortcode['name'], 11 );
			$shortcode['name'] 	= "ct_columns";
		}

		if ( strpos( $shortcode['name'], "ct_column_" ) !== false ) {

			$depth = substr( $shortcode['name'], 10 );
			$shortcode['name'] 	= "ct_column";
		}

		if ( strpos( $shortcode['name'], "ct_div_block_" ) !== false ) {

			$depth = substr( $shortcode['name'], 13 );
			$shortcode['name'] 	= "ct_div_block";
		}

		if ( strpos( $shortcode['name'], "ct_inner_content_" ) !== false ) {

			$depth = substr( $shortcode['name'], 17 );
			$shortcode['name'] 	= "ct_inner_content";
		}

		if ( strpos( $shortcode['name'], "ct_link_" ) !== false && strpos( $shortcode['name'], "ct_link_text" ) === false) {

			$depth = substr( $shortcode['name'], 8 );
			$shortcode['name'] 	= "ct_link";
		}

		// parse inner content shortcodes
		if ( $inner_content[$key] ) {
			
			$nested_content = oxygen_vsb_parse_shortcodes( $inner_content[$key], false );

			if ( $nested_content['is_shortcode'] ) {
				$shortcode['children'] = $nested_content['content'];
				
				// get shortcodes inside content
				if ( $shortcode['name'] == "ct_paragraph" || 
					 $shortcode['name'] == "ct_text_block" || 
					 $shortcode['name'] == "ct_headline" ||
					 $shortcode['name'] == "ct_link_text" ||
					 $shortcode['name'] == "ct_li") {

					preg_match_all( '/'. $pattern .'/s', $inner_content[$key], $inner_matches );

					$inner_shortcodes 			= $inner_matches[0];
					$inner_shortcodes_atts		= $inner_matches[3];
					$inner_shortcodes_parsed 	= $inner_content[$key];

					foreach ( $inner_shortcodes as $key => $inner_shortcode ) {
						
						// parse "ct_options" parameter
						$atts = shortcode_parse_atts( $inner_shortcodes_atts[$key] );
						$atts = json_decode( $atts['ct_options'], true );

						$inner_shortcodes_parsed 	= str_replace( $inner_shortcode, "<span id=\"ct-placeholder-{$atts['ct_id']}\"></span>", $inner_shortcodes_parsed );
						$inner_shortcodes_copy 		= str_replace( $inner_shortcode, "", $inner_shortcodes_copy );
					}

					if ( $inner_shortcodes) {
						$shortcode['options']['ct_content'] = $inner_shortcodes_parsed;	
					}
				}

			} else {
				
				$nested_content['content'] = trim($nested_content['content']);
				
				if(!($shortcode['name'] == 'ct_inner_content' && empty($nested_content['content'])))
					$shortcode['options']['ct_content'] = $nested_content['content'];
			}
		}

		if ( isset ( $depth ) )
			$shortcode['depth'] = $depth;

		$shortcodes[] = $shortcode;
	}

	return array(
			'is_shortcode' 	=> true,
			'content' 		=> $shortcodes );
}


/**
 * Update custom css variable so it can be 
 * used in shortcode attribute
 *
 * @since 0.1.4
 */

function oxygen_vsb_normalize_custom_css( $css ) {

	if ( $css ) {
		$css = str_replace("\n",'', $css);
		$css = str_replace("\r",'', $css);
		$css = str_replace("\t",'', $css);
	}

	return $css;
}


/**
 * Prettify custom CSS code
 *
 * @since 0.1.8
 */

function oxygen_vsb_prettify_custom_css( $css ) {

	if ( $css ) {
		$css = str_replace(";",";\n", $css);
	}

	return $css;
}


/**
 * Helper function to decode Unicode to UTF-8 characters
 *
 * @since 0.1.7
 */
function oxygen_vsb_unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'oxygen_vsb_replace_unicode_escape_sequence', $str);
}

function oxygen_vsb_replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}