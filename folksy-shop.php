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
  * Plugin version number.
  */
		const PLUGIN_VERSION = '0.2.1';

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
		function __construct() {
		
 /* One off hooks and actions. */
	 // Call activation method when activating the plugin...
			register_activation_hook( __FILE__, array( &$this,  'activate' ) );
		
 /* Regular hooks and actions. */
	 // Firstly it's important to create the item post type...
			add_action( 'init', array( &$this, 'create_post_type' ) );
		
		}
		
 /**
  * Activates the plugin.
	*
	* Flushes rewrite rules to make sure that the custom post type is available.
  *
  * @since 0.1
  */
		function activate() {

			create_post_type();
			flush_rewrite_rules();

		}
		
 /**
  * Creates the Folksy item post type.
  *
  * @since 0.1
  */
		function create_post_type() {

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
			                                          'public' => true, # Post type is not just for internal use
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
			                                                              'pages' => true ) );
		
		}
	
	}

}
