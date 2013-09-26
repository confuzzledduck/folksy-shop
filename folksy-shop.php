<?php

/*

	Plugin Name: Folksy Shop
	Plugin URI: http://www.flutt.co.uk/development/wordpress-plugins/folksy-shop/
	Version: 0.1
	Description: Helps Folksy sellers to manage their shop and drive traffic to their store from their blog or website.
	Author: ConfuzzledDuck
	Author URI: http://www.flutt.co.uk/

*/

#
#  folksy-shop.php
#
#  Created by Jonathon Wardman on 27-08-2013.
#  Copyright 2013, Jonathon Wardman. All rights reserved.
#
#  This program is free software: you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation, either version 3 of the License, or
#  (at your option) any later version.
#
#  You may obtain a copy of the License at:
#  http://www.gnu.org/licenses/gpl-3.0.txt
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.

 /**
  * Main Folksy Shop Manager functionality.
  *
  * @package FolksyShop
  */

	 // If the class doesn't exist we want to make it...
if ( !class_exists( 'FolksyShop' ) ) {

	class FolksyShop {

 /**
  * Base URL of the Folksy website to use when fetching data. Should include a
  * trailing slash and protocol (ie http://).
  */
		const FOLSKY_BASE_URL = 'http://folksy.com/';
	
 /**
  * Plugin version number.
  */
		const PLUGIN_VERSION = '0.1';

 /**
  * Settings format version number this build of the plugin requires.
  */
		const OPTIONS_VERSION = '1';

 /* General settings and init functionality. */

 /**
  * Constructor. Registers the hooks required by the Folksy Shop Manager.
  *
  * @since 0.1
  */
		public function __construct() {

 /* One off hooks and actions. */
	 // Call activation method when activating the plugin...
			register_activation_hook( __FILE__, array( &$this,  'activate' ) );
	 // Call deactivation method when deactivating the plugin...
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

 /* Regular hooks and actions. */
	 // Firstly it's important to create the item post type and category type...
			add_action( 'init', array( $this, 'create_folksy_types' ) );

 /* Our own hooks. */
	 // The hook for WP cron to update items from Folksy...
			add_action( 'folksy-shop-update', array( $this, 'update_items_from_folksy' ) );
			
		}

 /**
  * Activates the plugin.
  *
  * Flushes rewrite rules to make sure that the custom post type is available.
  * Sets up the cron job to keep the shop items updated.
  *
  * @since 0.1
  */
		public function activate() {

	 // Post types and taxonomy...
			$this->create_folksy_types();
			flush_rewrite_rules();

	 // WP cron...
			wp_schedule_event( time(), 'hourly', 'folksy_shop_update' );

		}

 /**
  * Deactivates the plugin.
  *
  * Turns off the WP cron.
  *
  * @since 0.1
  */
		public function deactivate() {

	 // WP cron...
			wp_clear_scheduled_hook( 'folksy_shop_update' );

		}
		
 /**
  * Updates items using Folksy as the base source.
  *
  * @since 0.1
  */
		public function update_items_from_folksy() {

		}

 /**
  * Creates the Folksy item post type and taxonomy type.
  *
  * @since 0.1
  */
		public function create_folksy_types() {

			register_post_type( 'folksy_item', array( 'labels' => array( 'name' => 'Folksy Listings',
			                                                             'singular_name' => 'Folksy Listing',
			                                                             'all_items' => 'All Listings',
			                                                             'add_new_item' => 'Add New Listing',
			                                                             'edit_item' => 'Edit Listing',
			                                                             'new_item' => 'Add Listing',
			                                                             'view_item' => 'View Listing',
			                                                             'search_items' => 'Search Listings',
			                                                             'not_found' => 'No listings founs',
			                                                             'not_found_in_trash' => 'No listings found in trash'),
			                                          'description' => 'Items listed on Folksy',
			                                          'public' => false	, # Post type is not just for internal use
			                                          'show_ui' => true, # This will probably change in due course to make items read-only
			                                          'menu_position' => 20, # Put the menu item below Pages and above Comments
			                                          'capability_type' => 'page', # For now we want this to behave like a page
			                                          'hierarchical' => false,
			                                          'supports' => array( 'author' => false,
			                                                               'excerpt' => false,
			                                                               'page-attrubutes' => false ),
			                                          'has_archive' => false, # Just to be explicit
			                                          'rewrite' => array( 'slug' => 'folksy',
			                                                              'with_front' => false,
			                                                              'feeds' => true, # We want feeds even though we don't want archives
			                                                              'pages' => true ) ) );
			register_taxonomy( 'folksy_store_section', 'folksy_item', array( 'labels' => array( 'name' => 'Shop Sections',
			                                                                                    'singular_name' => 'Shop Section',
			                                                                                    'menu_name' => 'Shop Sections',
			                                                                                    'all_items' => 'All Sections',
			                                                                                    'edit_item' => 'Edit Section',
			                                                                                    'view_item' => 'View Section',
			                                                                                    'update_item' => 'Update Section',
			                                                                                    'add_new_item' => 'Add New Section',
			                                                                                    'new_item_name' => 'New Section Name',
			                                                                                    'search_items' => 'Search Sections',
			                                                                                    'add_or_remove_items' => 'Add or remove sections' ),
			                                                                 'public' => true,
			                                                                 'show_tagcloud' => false,
			                                                                 'hierarchical' => false, # Default, but let's be explicit
			                                                                 'query_var' => 'folksy-section',
			                                                                 'rewrite' => array( 'slug' => 'folksy-section',
			                                                                                     'with_front' => false,
			                                                                                     'hierarchical' => false ) ) );

		}

 /**
  * Fetches a JSON page from Folksy if one is available. If one is not available
  * or the response (where it should have been available) cannot be understood
  * then returns false.
  *
  * Currently Folksy provides JSON formats for /shops, /items and category
  * listing (although we're not concerned about that at this stage).
  *
  * @since 0.1
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @return object|boolean An object representing the Folksy JSON reponse, or false if the request failed.
  */
		protected function _fetchFolksyJson( $pagePath ) {
		
			if ( !empty( $pagePath ) ) {
				$pagePath = ( substr( $pagePath, 0, 1 ) == '/' ) ? substr( $pagePath, 1 ) : $pagePath;
				if ( preg_match('/^shops|items/', $pagePath) ) {
					if ( $curlHandle = curl_init( self::FOLSKY_BASE_URL.$pagePath ) ) {

						$curlOptions = array( CURLOPT_HEADER => false,
						                      CURLOPT_RETURNTRANSFER => true,
						                      CURLOPT_USERAGENT => 'Folksy Shop for WordPress (v'.self::PLUGIN_VERSION.')',
						                      CURLOPT_HTTPHEADER => array( 'Accept: application/json, text/javascript, */*' ) );
						curl_setopt_array( $curlHandle, $curlOptions );

						$rawJson = curl_exec( $curlHandle );
						curl_close( $curlHandle );

						if ( $rawJson !== false && !empty( $rawJson )) {
							$decodedResult = json_decode( $rawJson );
							if ( $decodedResult !== null ) {
								return $decodedResult;
							}
						}

					}
				}
			}
			
			return false;
		
		}
		
	}

	 // Create the class and get going...
	$folksy = new FolksyShop();

}
