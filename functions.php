<?php

/*
Plugin Name: Oxygen Visual Site Builder
Author: Soflyy
Author URI: http://soflyy.com
Description: Oxygen replaces your WordPress theme and allows you to design your entire website visually, inside WordPress. Construct static pages from fundamental HTML elements and visually style their CSS properties. Create views that will be used to render dynamic content like blog posts, WooCommerce products, or any other custom post type.
Version: 1.0
Text Domain: component-theme
*/

define("OXYGEN_VSB_VERSION", 	"1.0");
define("OXYGEN_VSB_FW_PATH", 	plugin_dir_path( __FILE__ )  . 	"component-framework" );
define("OXYGEN_VSB_FW_URI", 	plugin_dir_url( __FILE__ )  . 	"component-framework" );
define("OXYGEN_VSB_PLUGIN_MAIN_FILE", __FILE__ );

require_once("component-framework/component-init.php");