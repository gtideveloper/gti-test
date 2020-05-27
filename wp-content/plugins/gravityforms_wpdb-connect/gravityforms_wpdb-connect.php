<?php


	/**
 	 Plugin Name: SP Gravity Forms WPDB Connect
	 Plugin URI: http://specialpress.de/plugins/spgfwpdb
	 Description: Connect Gravity Forms to the WPDB MYSQL Database
	 Version: 3.6.0
	 Date: 2019/03/15
	 Author: Ralf Fuhrmann
	 Author URI: http://naranili.de
	 */

	
	
	/**
	 * Changes
	 * -------
	 * - updated to the CODECANYON code requirements
	 * - updated merge tag handling
	 *
	 */
	 
	 
	
	/**
	 * To Do
	 * -----
	 *
	 * function to cache the table and field information
	 * uninstall function to delete all saved values and feeds
	 * check what happened if there isn't a primary key
	 * check what happened if there isn't a auto increment field
	 * add spaces to field values if database field is char
	 * delete trailing currency signs if database field is numeric
	 *
	 *
	 * To Do Pro
	 * ---------
	 *
	 * PRO button to export all (old) existing entries to WPDB
	 * PRO split pricing option in option name and option price
	 * PRO split pricing product in product name and product price
	 * PRO split pricing shipping in shipping name and shipping price
	 * PRO split post category into category name and category number
	 * PRO update the entry from the WPDB table 
	 *
	 */
	 
	
	
	if( !isset( $_SESSION ) )
		session_start();
	
    error_reporting( E_ERROR );
	
	
	
	/**
	 * check if GF is active and include common classes
	 */

	if ( class_exists( 'GFForms' ) ) {


		GFAddOn::register( 'SpGfWpdbConnect' );	

		GFForms::include_feed_addon_framework();

		/**
		 * check and maybe load the plugin classes
		 */
		if( !class_exists( 'SpGfWpdbConnect' ) ) 
			require_once( 'class.gravityforms_wpdb-connect.php' );
	

	}


?>