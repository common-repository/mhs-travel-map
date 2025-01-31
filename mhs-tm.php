<?php

/*
Plugin Name: My Hitchhiking Spot Travel Map (MHS Travel Map)
Plugin URI: 
Description: Create your travel map with use of google maps by adding coordinates to a map, make your route public, write a story for each coordinate and import backup files from the Android app "<a title="My Hitchhiking Spots" href="https://play.google.com/store/apps/details?id=com.myhitchhikingspots" target="_blank" rel="noopener">My Hitchhiking Spots</a>"
Version: 1.5.1 
Author: Jonas Damhuis
Author URI: 
License: GPL3
*/

/*  Copyright 2020 Jonas Damhuis  (email : jonas-damhuis@web.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as
    published by the Free Software Foundation.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Holds the absolute location of MHS Travel Map
 *
 * @since 1.0.0
 */
if ( ! defined( 'MHS_TM_ABSPATH' ) )
	define( 'MHS_TM_ABSPATH', dirname( __FILE__ ) );

/**
 * Holds the URL of MHS Travel Map
 *
 * @since 1.0.0
 */
if ( ! defined( 'MHS_TM_RELPATH' ) )
	define( 'MHS_TM_RELPATH', plugin_dir_url( __FILE__ ) );

/**
 * Holds the name of the MHS Travel Map directory
 *
 * @since 1.0.0
 */
if ( !defined( 'MHS_TM_DIRNAME' ) )
	define( 'MHS_TM_DIRNAME', basename( MHS_TM_ABSPATH ) );

/**
 * Admin UI
 *
 * @since 1.0
 */
if ( is_admin() ) {
        
	/* templates for tables */
	require_once( MHS_TM_ABSPATH . '/admin/tables/list-table-maps.php' );
	require_once( MHS_TM_ABSPATH . '/admin/tables/list-table-map-routes.php' );
	require_once( MHS_TM_ABSPATH . '/admin/tables/list-table-routes.php' );
        
	/* functional classes (usually insantiated only once) */
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-main.php' );
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-maps.php' );
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-map-edit.php' );
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-routes.php' );
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-settings.php' );
	require_once( MHS_TM_ABSPATH . '/admin/class-mhs-tm-admin-utilities.php' );

	/* template classes (non-OOP templates are included on the spot) */
	require_once( MHS_TM_ABSPATH . '/templates/class-mhs-tm-admin-page.php' );
	require_once( MHS_TM_ABSPATH . '/templates/class-mhs-tm-admin-form.php' );
            
	/**
	 * MHS_TM_Admin object
	 *
	 * @since 1.0
	 */
	$GLOBALS['MHS_TM_Admin'] = new MHS_TM_Admin();
	$GLOBALS['MHS_TM_Admin_Maps'] = new MHS_TM_Admin_Maps();
	$GLOBALS['MHS_TM_Admin_Map_Edit'] = new MHS_TM_Admin_Map_Edit();
	$GLOBALS['MHS_TM_Admin_Routes'] = new MHS_TM_Admin_Routes();
	$GLOBALS['MHS_TM_Admin_Settings'] = new MHS_TM_Admin_Settings();
	$GLOBALS['MHS_TM_Admin_Utilities'] = new MHS_TM_Admin_Utilities();

	/**
	 * MHS_TM_Admin ajax
	 *
	 * @since 1.0.1
	 */
	add_action( 'wp_ajax_routes_save', array( 'MHS_TM_Admin_Routes', 'routes_save' ) );
	add_action( 'wp_ajax_get_coordinate_note', array( 'MHS_TM_Maps', 'get_coordinate_note' ) );

	/**
	 * MHS_TM_Admin Wp_List_Table Bulk action 
	 *
	 * @since 1.5.0
	 */ 
        add_action( 'admin_action_mhs_tm_change_map_routes', array( 'MHS_TM_Admin_Maps', 'mhs_tm_change_map_routes' ) );
}

/**
 * Enqueue the plugin's javascript
 *
 * @since 1.0
 */
function MHS_TM_enqueue() {
    
	/* register scripts */ 
	wp_register_script( 'google_jsapi','https://www.google.com/jsapi', true ); 
	wp_register_script( 'mhs_tm_map', MHS_TM_RELPATH . 'js/mhs-tm-map.js', array(), '1.0.8' ); 
	wp_register_script( 'mhs_tm_utilities', MHS_TM_RELPATH . 'js/mhs-tm-utilities.js', array( 'jquery', 'jquery-ui-dialog' ), '1.0.7' );
	    
	/* register styles */
        wp_register_style( 'mhs_tm_jquery_style', MHS_TM_RELPATH . 'css/jquery-ui/jquery-ui.css', false, '1.12.1' );
        wp_register_style( 'mhs_tm_map_style', MHS_TM_RELPATH . 'css/mhs-tm-map.css', false, '1.0.7' );
	wp_register_style( 'mhs_tm_loading_overlay_style', MHS_TM_RELPATH . 'css/mhs-tm-loading-overlay.css', false, '1.0.3' );
	
	/* enqueue scripts */
	wp_enqueue_script( 'google_jsapi' );
	wp_enqueue_script( 'mhs_tm_utilities' );
    
	/* enqueue stylesheets */
	wp_enqueue_style( 'mhs_tm_jquery_style' ); 
	wp_enqueue_style( 'mhs_tm_map_style' );
	wp_enqueue_style( 'mhs_tm_loading_overlay_style' );
    
}
add_action( 'wp_enqueue_scripts', 'MHS_TM_enqueue' );

function MHS_TM_admin_enqueue() {
	$jqui_params = array(
		'monthNames' => array(
			_x( 'January', 'Months', 'MHS_TM' ),
			_x( 'February', 'Months', 'MHS_TM' ),
			_x( 'March', 'Months', 'MHS_TM' ),
			_x( 'April', 'Months', 'MHS_TM' ),
			_x( 'May', 'Months', 'MHS_TM' ),
			_x( 'June', 'Months', 'MHS_TM' ),
			_x( 'July', 'Months', 'MHS_TM' ),
			_x( 'August', 'Months', 'MHS_TM' ),
			_x( 'September', 'Months', 'MHS_TM' ),
			_x( 'October', 'Months', 'MHS_TM' ),
			_x( 'November', 'Months', 'MHS_TM' ),
			_x( 'December', 'Months', 'MHS_TM' )
		),
		'dayNamesMin' => array(
			_x( 'Sun', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Mon', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Tue', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Wed', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Thu', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Fri', 'Weekdays, Shortform', 'MHS_TM' ),
			_x( 'Sat', 'Weekdays, Shortform', 'MHS_TM' )
		)
	);
	$admin_params = array(
		'strings' => array(
			'btnDeselect' => __( 'Deselect all', 'MHS_TM' ),
			'btnSelect' => __( 'Select all', 'MHS_TM' )
		)
	);
        
	/* register scripts */
	wp_register_script( 'jquery_datetimepicker', MHS_TM_RELPATH . 'js/jquery.datetimepicker.full.min.js', array( 'jquery' ), '1.0.1' );
	wp_register_script( 'papaparse', MHS_TM_RELPATH . 'js/papaparse-4.1.2.js', array( 'jquery' ), '4.1.2' );
	wp_register_script( 'mhs_tm_admin_import', MHS_TM_RELPATH . 'js/mhs-tm-admin-import.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-accordion', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), '1.0.2' );
	wp_register_script( 'mhs_tm_utilities', MHS_TM_RELPATH . 'js/mhs-tm-utilities.js', array( 'jquery', 'jquery-ui-dialog' ), '1.0.8' );
	wp_register_script( 'mhs_tm_admin_maps', MHS_TM_RELPATH . 'js/mhs-tm-admin-maps.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-accordion', 'jquery-ui-dialog', 'jquery-ui-sortable' ), '1.0.5' );
	wp_register_script( 'mhs_tm_admin_routes', MHS_TM_RELPATH . 'js/mhs-tm-admin-routes.js', array( 'jquery', 'jquery-ui-dialog' ), '1.0.4' );
	wp_register_script( 'google_jsapi','https://www.google.com/jsapi', true ); 
	wp_register_script( 'jquery_ui_touch_punch_min', MHS_TM_RELPATH . 'js/jquery.ui.touch-punch.min.js', array(), '1.0.1' );
	wp_register_script( 'mhs_tm_map', MHS_TM_RELPATH . 'js/mhs-tm-map.js', array(), '1.0.8' );
	wp_register_script( 'mhs_tm_map_edit', MHS_TM_RELPATH . 'js/mhs-tm-map-edit.js', array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-accordion', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), '1.0.6' );
	wp_register_script( 'spectrum', MHS_TM_RELPATH . 'js/spectrum.js', array(), '1.0.0' );
	wp_register_script( 'mhs_tm_admin_settings', MHS_TM_RELPATH . 'js/mhs-tm-admin-settings.js', array( 'jquery', 'jquery-ui-dialog' ), '1.0.1' );
	wp_register_script( 'mhs_tm_admin_export', MHS_TM_RELPATH . 'js/mhs-tm-admin-export.js', array( 'jquery' ), '1.0.0' );

	/* register styles */
        wp_register_style( 'mhs_tm_admin_jquery_style', MHS_TM_RELPATH . 'css/jquery-ui/jquery-ui.css', false, '1.12.1' );
	wp_register_style( 'jquery_datetimepicker_style', MHS_TM_RELPATH . 'css/jquery.datetimepicker.min.css', false, '1.0.0'  );
	wp_register_style( 'mhs_tm_admin_style', MHS_TM_RELPATH . 'css/mhs-tm-admin.css', false, '1.0.4' );
	wp_register_style( 'mhs_tm_admin_page_style', MHS_TM_RELPATH . 'css/mhs-tm-admin-page.css', false, '1.0.1' );
	wp_register_style( 'mhs_tm_admin_form_style', MHS_TM_RELPATH . 'css/mhs-tm-admin-form.css', false, '1.0.2' );
	wp_register_style( 'mhs_tm_loading_overlay_style', MHS_TM_RELPATH . 'css/mhs-tm-loading-overlay.css', false, '1.0.3' );
        wp_register_style( 'mhs_tm_map_style', MHS_TM_RELPATH . 'css/mhs-tm-map.css', false, '1.0.6' );
	wp_register_style( 'spectrum', MHS_TM_RELPATH . 'css/spectrum.css', false, '1.0.0' );
        
	/* enqueue scripts */
	wp_enqueue_script( 'mhs_tm_utilities' );
	wp_enqueue_script( 'jquery_datetimepicker' );
	wp_enqueue_script( 'papaparse' );
        wp_enqueue_script( 'google_jsapi' );
        wp_enqueue_script( 'spectrum' );
        
	/* enqueue stylesheets */   
	wp_enqueue_style( 'mhs_tm_map_style' );
	wp_enqueue_style( 'spectrum' );   
	wp_enqueue_style( 'mhs_tm_admin_style' );
	wp_enqueue_style( 'mhs_tm_admin_page_style' );
	wp_enqueue_style( 'mhs_tm_admin_form_style' );
	wp_enqueue_style( 'mhs_tm_loading_overlay_style' );
	wp_enqueue_style( 'jquery_datetimepicker_style' ); 
	wp_enqueue_style( 'mhs_tm_admin_jquery_style' );      

	/* localize */
        
}
add_action( 'admin_enqueue_scripts', 'MHS_TM_admin_enqueue' );

/**
 * Require needed files
 *
 * @since 1.0
 */
/* core of the plugin, frontend (usually insantiated only once)*/
require_once ( MHS_TM_ABSPATH . '/includes/class-mhs-tm-maps.php' );
// utilities for the plugin
require_once ( MHS_TM_ABSPATH . '/includes/class-mhs-tm-utilities.php' );

/**
 * MHS_TM_Admin ajax
 *
 * @since 1.3.0
 */
add_action( 'wp_ajax_nopriv_get_coordinate_note', array( 'MHS_TM_Maps', 'get_coordinate_note' ) );

/**
 * MHS_TM Objects
 *
 * @global object $MHS_TM
 * @since 1.0
 */
$GLOBALS['MHS_TM_Maps'] = new MHS_TM_Maps();
$GLOBALS['MHS_TM_Utilities'] = new MHS_TM_Utilities();

/**
 * Define globals
 *
 * @since 1.0
 */
$MHS_TM_db_version = "1.1";

/**
 * Installation & Update Routines
 *
 * Creates and/or updates plugin's tables.
 * The install method is only triggered on plugin installation
 * and when the database version number
 * ( "MHS_TM_db_version", see above )
 * has changed.
 *
 * @since 1.0
 */
function MHS_TM_install() {
   global $wpdb, $MHS_TM_db_version, $MHS_TM_Utilities;
        
        $installed_ver = get_option( "MHS_TM_db_version" );
		
		// if the plugin is not installed the db version is false
        if ( $installed_ver === false ) {
		
                /* SQL statements to create required tables */
                $sql = array();
                $sql[] = "CREATE TABLE " . $wpdb->prefix . "mhs_tm_maps (
                        id int UNSIGNED NOT NULL AUTO_INCREMENT ,
                        active BOOL NOT NULL DEFAULT true,
                        create_date DATETIME NOT NULL DEFAULT 0 ,
                        updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
                        route_ids LONGTEXT NOT NULL ,
                        options LONGTEXT NOT NULL ,
                        selected BOOL NOT NULL,
                        PRIMARY KEY  (id)
                );";
                $sql[] = "CREATE TABLE " . $wpdb->prefix . "mhs_tm_routes (
                        id int UNSIGNED NOT NULL AUTO_INCREMENT ,
                        active BOOL NOT NULL DEFAULT true,
                        create_date DATETIME NOT NULL DEFAULT 0 ,
                        updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
                        coordinates LONGTEXT NOT NULL ,
                        options LONGTEXT NOT NULL ,
                        PRIMARY KEY  (id)
                );";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );

				// insert in maps table first row for settings of the plugin
				// active is set to 0 so this map is actually deleted
				$wpdb->insert(
				$wpdb->prefix . 'mhs_tm_maps', array(
					'active'		 => 0,
					'options'		 => ''
				), array( '%d', '%s' )
				);
				
                add_option( 'MHS_TM_db_version', $MHS_TM_db_version ); 
        }   
		//First DB Update adding id for class option in json array
		if ( version_compare( $installed_ver, '1.1' ) < 0 ) {
			$plugin_settings = $MHS_TM_Utilities->get_plugin_settings();
			$transport_classes = $plugin_settings['transport_classes'];
			
                        //get the classes and add a unique id 
			$x = 1;
                        
                        if( !is_array( $transport_classes ) )
                        {
                            return;
                        }
                        
			foreach ( $transport_classes as $transport_class ) {
				$transport_classes[$x - 1]['id'] = $x;  
				$x++;
			}
			$plugin_settings['transport_classes'] = json_encode( $transport_classes );
			
			//save the last created id (highest one)
			$plugin_settings['transport_classes_next_id'] = $x;
			
			//save the plugin setting array
			$plugin_settings_json = json_encode( $plugin_settings );
			
			$wpdb->update(
			$wpdb->prefix . 'mhs_tm_maps', array(
				'active'	 => 0,
				'options'	 => $plugin_settings_json,
			), array( 'id' => 1 ), array( '%d', '%s' ), array( '%d' )
			);
			
			//get options column of all routes in db
			$routes = $wpdb->get_results("select * from " . $wpdb->prefix . "mhs_tm_routes", ARRAY_A );
			
			//find options rows with a transportation_class
                        
                        if( !is_array( $routes ) )
                        {
                            return;
                        }
                        
			foreach ( $routes as $key => $route_row ) {
				$route_row_option = json_decode( $route_row['options'], true );
				
				if ( is_array( $route_row_option ) && array_key_exists('transport_class', $route_row_option)) {
					$transport_class = $route_row_option['transport_class'];
				
					foreach ( $transport_classes as $transport_class_option ) {
						if( $transport_class_option['name'] == $transport_class ) {
							
							//If a row has the key transportation_class and the value is also in the
							//tranportation_class option array, save the id of the transportation_class 
							//option in the route options row
							$route_row_option['transport_class'] = $transport_class_option['id'];
							
							$wpdb->update(
							$wpdb->prefix . 'mhs_tm_routes', array(
								'options'	 => json_encode( $route_row_option ),
							), array( 'id' => $route_row['id'] ), array( '%s' ), array( '%d' )
							);	
						}
					}
				}
			}		
		}
		
		update_option( 'MHS_TM_db_version', $MHS_TM_db_version );   
        register_uninstall_hook( __FILE__, 'MHS_TM_uninstall' );
}
register_activation_hook( __FILE__, 'MHS_TM_install' );

/**
 * Update Routine
 *
 * Checks if the databse is newer and will run the install routine again.
 *
 * @since 1.0
 */
function MHS_TM_update_db_check() {
    global $MHS_TM_db_version;
		
    if ( get_site_option( 'MHS_TM_db_version' ) != $MHS_TM_db_version ) {
        MHS_TM_install();
    }
}
add_action( 'plugins_loaded', 'MHS_TM_update_db_check' );

/**
 * Uninstall Routine
 *
 * Delete the added Database tables
 *
 * @since 1.0
 */
function MHS_TM_uninstall(){
    // drop a custom database table
    global $wpdb;
    
    //delete user meta datas
    $users = get_users( array( 'fields' => array( 'ID' ) ) );
    foreach($users as $user_id){
        delete_user_meta($user_id->ID, 'mhs_tm_map_routes_per_page');
        delete_user_meta($user_id->ID, 'mhs_tm_maps_per_page');
        delete_user_meta($user_id->ID, 'mhs_tm_routes_per_page');
        delete_user_meta($user_id->ID, 'managetoplevel_page_mhs_tm-mapscolumnshidden');
        delete_user_meta($user_id->ID, 'managemhs-travel-map_page_mhs_tm-maps-editcolumnshidden');
        delete_user_meta($user_id->ID, 'managemhs-travel-map_page_mhs_tm-routescolumnshidden');
    }
    delete_option( 'MHS_TM_db_version' );
    $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "mhs_tm_routes");
    $wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "mhs_tm_maps");
}

?>