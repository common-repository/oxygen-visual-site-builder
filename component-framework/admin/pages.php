<?php
/**
 * Add Dashboard pages/subpages for different settings
 *
 */


/**
 * Main Page
 * 
 * @since 0.2.0
 */

add_action('admin_menu', 'oxygen_vsb_dashboard_main_page');

function oxygen_vsb_dashboard_main_page(){
	
	if(!current_user_can( 'manage_options' )) {
		return;
	}

	add_menu_page( 	'Oxygen', // page <title>
					'Oxygen', // menu item name
					'manage_options', // capability
					'ct_dashboard_page', // get param
					'oxygen_vsb_dashboard_page_view',
					'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIzODFweCIgaGVpZ2h0PSIzODVweCIgdmlld0JveD0iMCAwIDM4MSAzODUiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+ICAgICAgICA8dGl0bGU+VW50aXRsZWQgMzwvdGl0bGU+ICAgIDxkZXNjPkNyZWF0ZWQgd2l0aCBTa2V0Y2guPC9kZXNjPiAgICA8ZGVmcz4gICAgICAgIDxwb2x5Z29uIGlkPSJwYXRoLTEiIHBvaW50cz0iMC4wNiAzODQuOTQgMzgwLjgwNSAzODQuOTQgMzgwLjgwNSAwLjYyOCAwLjA2IDAuNjI4Ij48L3BvbHlnb24+ICAgIDwvZGVmcz4gICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+ICAgICAgICA8ZyBpZD0iT3h5Z2VuLUljb24tQ01ZSyI+ICAgICAgICAgICAgPG1hc2sgaWQ9Im1hc2stMiIgZmlsbD0id2hpdGUiPiAgICAgICAgICAgICAgICA8dXNlIHhsaW5rOmhyZWY9IiNwYXRoLTEiPjwvdXNlPiAgICAgICAgICAgIDwvbWFzaz4gICAgICAgICAgICA8ZyBpZD0iQ2xpcC0yIj48L2c+ICAgICAgICAgICAgPHBhdGggZD0iTTI5Ny41MDgsMzQ5Ljc0OCBDMjc1LjQ0MywzNDkuNzQ4IDI1Ny41NTYsMzMxLjg2IDI1Ny41NTYsMzA5Ljc5NiBDMjU3LjU1NiwyODcuNzMxIDI3NS40NDMsMjY5Ljg0NCAyOTcuNTA4LDI2OS44NDQgQzMxOS41NzMsMjY5Ljg0NCAzMzcuNDYsMjg3LjczMSAzMzcuNDYsMzA5Ljc5NiBDMzM3LjQ2LDMzMS44NiAzMTkuNTczLDM0OS43NDggMjk3LjUwOCwzNDkuNzQ4IEwyOTcuNTA4LDM0OS43NDggWiBNMjIyLjMwNCwzMDkuNzk2IEMyMjIuMzA0LDMxMi4wMzkgMjIyLjQ0NywzMTQuMjQ3IDIyMi42MzksMzE2LjQ0MSBDMjEyLjMzLDMxOS4wOTIgMjAxLjUyOCwzMjAuNTA1IDE5MC40MDMsMzIwLjUwNSBDMTE5LjAxLDMyMC41MDUgNjAuOTI5LDI2Mi40MjMgNjAuOTI5LDE5MS4wMzEgQzYwLjkyOSwxMTkuNjM4IDExOS4wMSw2MS41NTcgMTkwLjQwMyw2MS41NTcgQzI2MS43OTQsNjEuNTU3IDMxOS44NzcsMTE5LjYzOCAzMTkuODc3LDE5MS4wMzEgQzMxOS44NzcsMjA2LjgzMyAzMTcuMDIsMjIxLjk3OCAzMTEuODE1LDIzNS45OSBDMzA3LjE3OSwyMzUuMDk3IDMwMi40MDQsMjM0LjU5MiAyOTcuNTA4LDIzNC41OTIgQzI1NS45NzQsMjM0LjU5MiAyMjIuMzA0LDI2OC4yNjIgMjIyLjMwNCwzMDkuNzk2IEwyMjIuMzA0LDMwOS43OTYgWiBNMzgwLjgwNSwxOTEuMDMxIEMzODAuODA1LDg2LjA0MiAyOTUuMzkyLDAuNjI4IDE5MC40MDMsMC42MjggQzg1LjQxNCwwLjYyOCAwLDg2LjA0MiAwLDE5MS4wMzEgQzAsMjk2LjAyIDg1LjQxNCwzODEuNDMzIDE5MC40MDMsMzgxLjQzMyBDMjEyLjQ5OCwzODEuNDMzIDIzMy43MDgsMzc3LjYwOSAyNTMuNDU2LDM3MC42NTcgQzI2NS44NDUsMzc5LjY0MSAyODEuMDM0LDM4NSAyOTcuNTA4LDM4NSBDMzM5LjA0MiwzODUgMzcyLjcxMiwzNTEuMzMgMzcyLjcxMiwzMDkuNzk2IEMzNzIuNzEyLDI5Ni4wOTIgMzY4Ljk4OCwyODMuMjgzIDM2Mi41ODQsMjcyLjIxOSBDMzc0LjI1MSwyNDcuNTc1IDM4MC44MDUsMjIwLjA1OCAzODAuODA1LDE5MS4wMzEgTDM4MC44MDUsMTkxLjAzMSBaIiBpZD0iRmlsbC0xIiBmaWxsPSIjMDBCM0MxIiBtYXNrPSJ1cmwoI21hc2stMikiPjwvcGF0aD4gICAgICAgIDwvZz4gICAgPC9nPjwvc3ZnPg==' ); 
}

function oxygen_vsb_dashboard_page_view(){
	echo "Component Page Test";	
}


/**
 * SVG Sets
 * 
 * @since 0.2.1
 */

add_action('admin_menu', 'oxygen_vsb_svg_sets_page');

function oxygen_vsb_svg_sets_page() {
	
	if(!current_user_can( 'manage_options' )) {
		return;
	}

	add_submenu_page( 	'ct_dashboard_page', 
						'SVG Sets', 
						'SVG Sets', 
						'manage_options', 
						'ct_svg_sets', 
						'oxygen_vsb_svg_sets_callback' );
}


/**
 * Export/Import
 * 
 * @since 0.2.1
 */

add_action('admin_menu', 'oxygen_vsb_export_import_page');

function oxygen_vsb_export_import_page() {
	
	if(!current_user_can( 'manage_options' )) {
		return;
	}

	add_submenu_page( 	'ct_dashboard_page', 
						'Export/Import', 
						'Export/Import', 
						'manage_options', 
						'ct_export_import', 
						'oxygen_vsb_export_import_callback' );
}