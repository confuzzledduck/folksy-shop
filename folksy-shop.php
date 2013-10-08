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
if ( !class_exists( 'Folksy_Shop' ) ) {

	class Folksy_Shop {
	
 /* Constants. */

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
		
 /**
  * The name of the taxonomy we create.
  */
		const TAXONOMY_NAME = 'folksy_store_section';

 /**
  * The name of the post type we create.
  */
		const POST_TYPE_NAME = 'folksy_item';

 /* Variables. */
		
 /**
  * Folksy JSON mapping to our own meta fields in the format
  *   'folksy_key' => 'meta_name'
  */
		private $_metaMapping = array( 'price' => '_price',
		                               'subcategory_id' => '_folksy_category',
		                               'id' => '_folksy_id',
		                               'quantity' => '_quantity',
		                               'image' => '_folksy_image' );

 /* Magic methods. */

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
			
	 // Register settings and add settings page to admin menu and to plugins page...
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'settings_menu' ) );
			add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

 /* Our own hooks. */
	 // The hook for WP cron to update items from Folksy...
			add_action( 'folksy-update-cron', array( $this, 'update_cron' ), 10, 1 );
			
		}
		
 /**
  * Destructor. Currently unused.
  *
  * @since 0.1
  */
		public function __desctruct() { }

 /* Init and WP specific hook methods, etc. */
 
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

	 // Set up the options if we don't already have something. We do it this way
	 // to stop the settings from autoloading...
			if ( !get_option( 'folksy_shop_options' ) ) {
				add_option( 'folksy_shop_options', array(), '', 'no' );
			}

	 // WP cron...
			wp_schedule_event( time(), 'hourly', 'folksy-update-cron' );

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
			wp_clear_scheduled_hook( 'folksy-update-cron' );

		}
		
 /**
  * Creates the Folksy item post type and taxonomy type.
  *
  * @since 0.1
  */
		public function create_folksy_types() {

			register_post_type( self::POST_TYPE_NAME, array( 'labels' => array( 'name' => 'Folksy Listings',
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
			                                                 'public' => true	, # Post type is not just for internal use
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
			register_taxonomy( self::TAXONOMY_NAME, 'folksy_item', array( 'labels' => array( 'name' => 'Shop Sections',
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
			                                                              'hierarchical' => true,
			                                                              'query_var' => 'folksy-section',
			                                                              'rewrite' => array( 'slug' => 'folksy-section',
			                                                                                  'with_front' => false,
			                                                                                  'hierarchical' => false ) ) );

		}

 /* Cron related methods. */
		
 /**
  * Cron to update the local sections and items from Folksy.
  *
  * @since 0.1
  */
		public function update_cron() {
		
			if ( ( $shopOptions = get_option( 'folksy_shop_options' ) ) && !empty( $shopOptions['folksy_username'] ) ) {
				$this->update_sections( $shopOptions['folksy_username'] );
				$this->update_items( $shopOptions['folksy_username'] );
			}
		
		}

 /* General functionality (the body of the plugin). */
		
 /**
  * Fetches shop items from Folksy.
  *
  * @since 0.1
  * @see FolksyShop::updateItems()
  * @param string $shopname The name of the shop to fetch items from.
  */
		public function fetch_items( $shopName ) {
		
			if ( $shopDetails = $this->_fetch_json( 'shops/'.$shopName ) ) {
				$shopItemsArray = array();
				foreach ( $shopDetails->shop->items AS $shopItem ) {
					$thisItem = array();
					foreach ($shopItem AS $key => $value) {
						$thisItem[$key] = trim($value);
					}
					$shopItemsArray[] = $thisItem;
				}
				
				return $shopItemsArray;

			} else {
				return false;
			}

		}

 /**
  * Updates and inserts items using Folksy as the base source.
  *
  * @since 0.1
  * @see FolksyShop::fetchItems
  * @param string $shopname The name of the shop to update items from.
  */
		public function update_items( $shopName ) {
		
			if ( $shopItems = $this->fetch_items( $shopName ) ) {

				if ( count( $shopItems ) > 0 ) {
					$shopSections = get_terms( self::TAXONOMY_NAME, array( 'hide_empty' => false ) );
					foreach ( $shopItems AS $shopItem ) {

	 // Find any items matching the Folksy ID...
						$matchingItems = get_posts( array( 'post_type' => self::POST_TYPE_NAME,
						                                   'meta_key' => '_folksy_id',
						                                   'meta_value' => $shopItem['id'] ) );
						if ( count( $matchingItems ) > 0 ) {

	 // We've seen the item before so let's update it if required...
							$postData = array( 'ID' => $matchingItems[0]->ID );
							if ( $matchingItems[0]->post_content != $shopItem['description'] ) {
								$postData['post_content'] = $shopItem['description'];
							}
							if ( $matchingItems[0]->post_title != $shopItem['title'] ) {
								$postData['post_title'] = $shopItem['title'];
							}
							if ( count( $postData ) > 1 ) {
								wp_update_post( $postData );
							}
							
							$existingMeta = get_post_meta( $matchingItems[0]->ID );
							foreach ( $this->_metaMapping AS $folksyKey => $metaKey ) {
								if ( $existingMeta[$metaKey] != $shopItem[$folksyKey] ) {
									update_post_meta( $matchingItems[0]->ID, $metaKey, $shopItem[$folksyKey] );
								}
							}
						
						} else {

	 // This is the first time we've seen this item, so let's insert it...
							$pageId = wp_insert_post( array( 'post_content' => $shopItem['description'],
							                                 'post_title' => $shopItem['title'],
							                                 'post_status' => 'publish',
							                                 'post_type' => self::POST_TYPE_NAME ) );
							if ( $pageId > 0 ) {
							
	 // Insert additional meta data relating to the item...
								foreach ( $this->_metaMapping AS $folksyKey => $metaKey ) {
									add_post_meta( $pageId, $metaKey, $shopItem[$folksyKey], true );
								}
							
	 // Match to relevant category based on shop section...
								foreach ( $shopSections AS $shopSection ) {
									if ( $shopSection->description == $shopItem['section_id'] ) {
                    $pageTaxonomies = wp_set_object_terms( $pageId, (int) $shopSection->term_id, self::TAXONOMY_NAME, false );
										break;
									}
								}
								
							}
						}
						
					}
				}
				
				return true;

			} else {
				return false;
			}
		
		}
		
 /**
  * Fetches shop sections from Folksy.
  *
  * Shop sections contain the name of the section (['title']) and the Folksy
  * shop section ID (['id']).
  *
  * @since 0.1
  * @see FolksyShop::update_sections()
  * @param string $shopname The name of the shop to update items from.
  * @return array Details of the shop sections. If there are no sections then returns a blank array.
  */
		public function fetch_sections( $shopName ) {
		
			if ( $shopDetails = $this->_fetch_json( 'shops/'.$shopName ) ) {
			
				$shopSectionsArray = array();
				foreach ( $shopDetails->shop->sections AS $shopSection ) {
					$shopSectionsArray[] = array( 'id' => $shopSection->id,
					                              'title' => $shopSection->title );
				}
				
				return $shopSectionsArray;
			
			} else {
				return false;
			}
		
		}
		
 /**
  * Updates shop taxonomies from shop sections using Folksy as the base source.
	* The title of the shop section is used as the term itself, and we use the
  * description field to hold the Folksy shop ID because we need this to match
	* categories to items.
  *
  * @since 0.1
  * @see FolksyShop::fetch_sections()
  * @param string $shopname The name of the shop to update items from.
  */
		public function update_sections( $shopName ) {

			if ( $shopSections = $this->fetch_sections( $shopName ) ) {

				if ( count( $shopSections ) > 0 ) {
					foreach ( $shopSections AS $shopSection ) {
						$termExists = term_exists( $shopSection['title'], self::TAXONOMY_NAME );
						if ( 0 == $termExists || null == $termExists ) {
							wp_insert_term( $shopSection['title'], self::TAXONOMY_NAME, array( 'description' => $shopSection['id'] ) );
						}
					}
				}
				
				return true;

			} else {
				return false;
			}
		
		}
		
 /* Settings related functionality. */

 /**
  * Register the settings variable into WordPress. We use just one setting value
	* for all our settings to keep things nice and tidy.
  *
  * @since 0.1
  */
		public function register_settings() {

			register_setting( 'folksy_shop_options', 'folksy_shop_options', array( $this, 'sanitize_settings' ) );

		}

 /**
  * Validates and sanitises the settings submitted from the settings page.
	*
	*  - Usernames must be between 3 and 40 letters or numbers only.
  *
  * @since 0.1
  */
		public function sanitize_settings( $settings ) {
		
			$existingOptions = get_option( 'folksy_shop_options' );

	 // The username has been unlocked, so we'll wipe out all the currently stored
	 // itemps and shop sections (you were warned!)...
			if ( isset( $settings['unlock'] ) ) {
				set_transient( 'folksy_username_unlock', true, 60 );
				$allItems = get_posts( array( 'posts_per_page' => -1,
				                              'post_type' => self::POST_TYPE_NAME ) );
				if ( count( $allItems ) > 0 ) {
					foreach ( $allItems AS $item ) {
						wp_delete_post( $item->ID, true );
					}
				}
				$allSections = get_terms( self::TAXONOMY_NAME, array( 'hide_empty' => false ) );
				if ( count( $allSections ) > 0 ) {
					foreach ( $allSections AS $section ) {
						wp_delete_term( $section->term_id, self::TAXONOMY_NAME );
					}
				}
				return $existingOptions;
			}
			
			if ( isset( $settings['folksy_username'] ) ) {
				if ( !preg_match( '/^[a-z-09]{3,40}$/i', $settings['folksy_username'] ) ) {
					add_settings_error( 'folksy_username', 'folksy_username', 'Folksy usernames must be between 3 and 40 characters and contain only letters and numbers.', 'error' );
					$settings['folksy_username'] = ( !empty( $existingOptions['folksy_username'] ) ) ? $existingOptions['folksy_username'] : '';
				}
			} else {
				$settings['folksy_username'] = $existingOptions['folksy_username'];
			}
			
			return $settings;

		}

 /**
  * Adds the plugin settings page to general settings menu.
  *
  * @since 0.1
	* @see Folksy_Shop::settings_page()
  */
		public function settings_menu() {

			add_submenu_page( 'options-general.php', 'Folksy Shop Settings', 'Folksy Shop', 'manage_options', 'folksy_shop', array( $this, 'settings_page' ) );

		}

 /**
  * Adds a link to the plugin settings page to the plugins table.
  *
  * @since 0.1
	* @see Folksy_Shop::settings_page()
  */
		public function settings_link( $links, $file ) {

			if ( $file == plugin_basename( __FILE__ ) ) {
				array_push( $links, '<a href="options-general.php?page=folksy_shop">Settings</a>' );
			}
			return $links;

		}

 /**
  * Plugin settings page. Includes folksy-shop-settings.php.
  *
  * @since 0.1
  */
		public function settings_page() {

			$folksyShopOptions = get_option( 'folksy_shop_options' );
			$unlockFlag = get_transient( 'folksy_username_unlock' );
			if ( 1 == $unlockFlag ) {
			  delete_transient( 'folksy_username_unlock' );
			}
			if ( empty( $folksyShopOptions['folsky_username'] ) ) {
				$folksyShopOptions['folsky_username'] = '';
			}
			require_once( 'folksy-shop-settings.php' );

		}
		
 /* Protected and private methods for internal use only. */

 /**
  * Fetches a JSON page from Folksy if one is available. If one is not available
  * or the response (where it should have been available) cannot be understood
  * then returns false.
  *
  * Currently Folksy provides JSON formats for /shops, /items and category
  * listing (although we're not concerned about that at this stage).
  *
  * @since 0.1
  * @see FolksyShop::_fetchFolksyDocument
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @return object|boolean An object representing the Folksy JSON reponse, or false if the request failed.
  */
		protected function _fetch_json( $pagePath ) {
		
			if ( $rawJson = $this->_fetch_document( $pagePath, 'json' ) ) {
				if ( $rawJson !== false && !empty( $rawJson )) {
					$decodedResult = json_decode( $rawJson );
					if ( $decodedResult !== null ) {
						return $decodedResult;
					}
				}
			}
			
			return false;
		
		}
		
 /**
  * Fetches an HTML page from Folksy if one is available. If it is not available
  * (ie. the page is a 404 error) then false is returned.
  *
  * @since 0.1
  * @see FolksyShop::_fetchFolksyDocument
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @return string|boolean The contents of the page requested, or false if the request failed.
  */
		protected function _fetch_html( $pagePath ) {

			return $this->_fetch_document( $pagePath );

		}
		
 /**
  * Fetches a document from Folksy if one is available. If it is not available
  * (ie. the page is a 404 error or it was an invalid request in the first
  * place) then false is returned.
  *
  * @since 0.1
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @return string|boolean The contents of the page requested, or false if the request failed.
  */
		protected function _fetch_document( $pagePath, $accept = 'html' ) {

			if ( !empty( $pagePath ) ) {
				$pagePath = ( substr( $pagePath, 0, 1 ) == '/' ) ? substr( $pagePath, 1 ) : $pagePath;
				if ( $curlHandle = curl_init( self::FOLSKY_BASE_URL.$pagePath ) ) {

					$curlOptions = array( CURLOPT_HEADER => false,
					                      CURLOPT_RETURNTRANSFER => true,
					                      CURLOPT_USERAGENT => 'Folksy Shop for WordPress (v'.self::PLUGIN_VERSION.')' );
					if ( 'json' == $accept ) {
						$curlOptions[CURLOPT_HTTPHEADER] = array( 'Accept: application/json, text/javascript' );
					} else {
						$curlOptions[CURLOPT_HTTPHEADER] = array( 'Accept: text/html, application/xhtml+xml, application/xml' );
					}
					curl_setopt_array( $curlHandle, $curlOptions );

					$rawResponse = curl_exec( $curlHandle );
					curl_close( $curlHandle );

					return $rawResponse;

				}
			}

			return false;

		}
		
	}

	 // Create the class and get going...
	$folksy = new Folksy_Shop();

}
