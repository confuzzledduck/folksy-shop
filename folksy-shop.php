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
#  Copyright 2013 - 2014, Jonathon Wardman. All rights reserved.
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
  * Base URL of the beta Folksy website. Included to support new shop fronts
  * currently on a different subdomain. Should include a trailing slash and
  * protocol (ie http://).
  */
		const FOLSKY_BASE_URL_BETA = 'https://beta.folksy.com/';

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
		
 /**
  * The name of the options element.
  */
		const OPTIONS_NAME = 'folksy_shop_options';

 /* Variables. */

 /**
  * Folksy JSON mapping to our own meta fields in the format
  * 'folksy_key' => 'meta_name'
  */
		private $_metaMapping = array( 'price' => '_price',
		                               'subcategory_id' => '_folksy_category',
		                               'id' => '_folksy_id',
		                               'quantity' => '_quantity' );

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
		public function __destruct() { }

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
			if ( !get_option( self::OPTIONS_NAME ) ) {
				add_option( self::OPTIONS_NAME, array( 'remote' => array(),
				                                       'folksy_sections_slug' => 'folksy-section',
				                                       'folksy_items_slug' => 'folksy' ), '', 'no' );
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
		
			$options = get_option( self::OPTIONS_NAME );
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
			                                                 'supports' => false,
			                                                 'has_archive' => false, # Just to be explicit
			                                                 'rewrite' => array( 'slug' => $options['folksy_items_slug'],
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
			                                                              'rewrite' => array( 'slug' => $options['folksy_sections_slug'],
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
		
			if ( ( $shopOptions = get_option( self::OPTIONS_NAME ) ) && !empty( $shopOptions['folksy_username'] ) ) {

	 // Options settings...
				$shopOptions['remote']['folksy_shop_holiday'] = $this->fetch_folksy_holiday( $shopOptions['folksy_username'], ( true === $shopOptions['new_shop'] ) ? 'beta' : 'main' );

	 // Update items...
				$this->update_sections( $shopOptions['folksy_username'], ( true === $shopOptions['new_shop'] ) ? 'beta' : 'main' );
				$this->update_items( $shopOptions['folksy_username'], ( true === $shopOptions['new_shop'] ) ? 'beta' : 'main', $shopOptions );

			}
		
		}

 /* General functionality (the body of the plugin). */
 
 /**
  * Fetches the holiday staus of the given Folksy shop. Returns true if the shop
	* is marked as on holiday on Folksy or false if it's not (ie. it's open for
	* business).
  *
  * @since 0.1
  * @param string $shopname The name of the shop to check the holiday status of.
  * @param string $folksyVersion If this shop is an old ('main') shopfront or a new (October 2013) ('beta') shopfront.
  */
		public function fetch_folksy_holiday( $shopName, $folksyVersion = 'main' ) {

			$shopHoliday = false;

			if ( 'beta' == $folksyVersion ) {

				if ( $shopMainPage = $this->_fetch_html( 'shops/'.$shopName, 'beta' ) ) {
					$shopMainPage = str_replace(array("\r", "\n", '  '), '', $shopMainPage );
					if ( preg_match( '/<div class=\"holiday-notice\">/', $shopMainPage ) ) {
						$shopHoliday = true;
					}
				}

			} else {
				// I don't know how holiday mode is displayed in "old" shops, so...
				$shopHoliday = false;
			}

			return $shopHoliday;

		}

 /**
  * Fetches shop items from Folksy. Uses the Folksy JSON response so pretty
  * complete information wise, but still not as comprehensive as
  * FolksyShop::fetch_item_details().
  *
  * @since 0.1
  * @see FolksyShop::updateItems()
  * @param string $shopname The name of the shop to fetch items from.
  */
		public function fetch_item_list( $shopName ) {
		
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
  * Fetches a list of shop items from beta Folksy shops. This method differs
  * FolksyShop::fetch_item_list() in that it doesn't return full item details
  * (thanks to the lack of JSON feeds for new shops).
  *
  * In order to get full details you must call
  * FolksyShop::fetch_item_details_beta() for each item. This isn't included
  * here to be a little more friendly to the Folksy servers (ie. so as not to
  * fetch every shop item page every hour when looking for new items).
  *
  * @since 0.1
  * @see FolksyShop::updateItems()
  * @param string $shopname The name of the shop to fetch items from.
  */
		public function fetch_item_list_beta( $shopName ) {

			$page = 1;
			$shopItemsArray = array();
			while (true) {
				if ( $shopDetails = $this->_fetch_html( 'shops/'.$shopName.'/items?page='.$page++, 'beta' ) ) {

					$shopDetails = str_replace(array("\r", "\n", '  '), '', $shopDetails );
					if ( preg_match_all( '/<li class="item">(.*?)<\/li>/', $shopDetails, $items ) ) {
						$itemCount = count( $items[1] );
						if ( $itemCount > 0 ) {
							foreach ( $items[1] AS $itemSegment ) {
								if ( preg_match( '/<a href="http:\/\/folksy.com\/items\/(\d+)-[a-z0-1-]+"><span class="image"><img alt=".+" item_prop="image" src="(\/\/images.folksy.com\/[a-z0-9-]+)\/shopitem" \/><\/span><div class="text"><h2 itemprop="name">(.+)<\/h2><p><span itemprop="price">.+?([\d\.]+)<\/span>(\d{1,3}) in stock<\/p><\/div><\/a>/i', $itemSegment, $itemDetails ) ) {
									$shopItemsArray[] = array( 'id' => $itemDetails[1],
									                           'image' => $itemDetails[2],
									                           'title' => html_entity_decode( $itemDetails[3] ),
									                           'price' => $itemDetails[4] * 100,
									                           'quantity' => $itemDetails[5] );
								}
							}
							if ( $itemCount < 60 ) {
								break;
							}
						} else {
							break;
						}
					}

				} else {
					break;
				}
			}

			return $shopItemsArray;
		
		}

 /**
  * Fetches details of an item from it's listing page. Required for new shops
  * due to the lack of JSON feeds for new shops but potentially useful for other
  * things too.
  *
  * @since 0.1
  * @see FolksyShop::fetch_item_details()
  * @param string $folksyItemId Folksy's ID of the item to get details about.
  */
		public function fetch_item_details( $folksyItemId ) {

			if ( $itemDetails = $this->_fetch_json( 'items/'.$folksyItemId ) ) {

				$itemDetailsArray = array();
				foreach ($itemDetails AS $key => $value) {
					if ( is_string( $value ) ) {
						$itemDetailsArray[$key] = trim($value);
					}
				}

				return $itemDetailsArray;

			} else {
				return false;
			}

		}
		
 /**
  * Fetches the URL of images of an item from it's listing page.
  *
  * @since 0.1
  * @param string $folksyItemId Folksy's ID of the item to get images of.
  */
		public function fetch_item_images( $folksyItemId ) {

			if ( $itemDetails = $this->_fetch_html( 'items/'.$folksyItemId ) ) {
				$itemDetails = str_replace(array("\r", "\n", '  '), '', $itemDetails );
			
	 // Featured image...
				if (preg_match( '/src="\/\/(images.folksy.com\/[a-z0-9-]+)\/main"/i', $itemDetails, $featuredImage )) {
					$featuredImage = $featuredImage[1];
				} else {
					$featuredImage = null;
				}

	 // All item images...
				if ( preg_match_all( '/<img alt="" data-role="preview-image" src="\/\/(images.folksy.com\/[a-z0-9-]+)\/mini" \/>/i', $itemDetails, $images ) ) {
					if ( isset( $images[1] ) ) {
						$imagesArray = array();
						foreach ( $images[1] AS $image ) {
							if ( $image == $featuredImage ) {
								$imagesArray[] = array( 'id' => $image,
								                        'featured' => true );
							} else {
								$imagesArray[] = array( 'id' => $image,
								                        'featured' => false );
							}
						}
						return $imagesArray;
					}
				} else {
				  return array();
				}

			} else {
				return false;
			}

		}

 /**
  * Updates and inserts items and their images using Folksy as the base source.
  *
  * @since 0.1
  * @see FolksyShop::fetch_item_list
  * @see FolksyShop::fetch_item_list_beta
  * @see FolksyShop::fetch_item_images
  * @param string $shopname The name of the shop to update items from.
  * @param string $folksyVersion If this shop is an old ('main') shopfront or a new (October 2013) ('beta') shopfront.
  * @param array $additionalSettings Additional configuration options (the output of get_option( self::OPTIONS_NAME ) will do nicely) for this update. Optional.
  * @return boolean True on success, false if no items were found in the given Folksy shop.
  */
		public function update_items( $shopName, $folksyVersion = 'main', array $additionalSettings = null ) {

			$itemsFunction = ( 'beta' == $folksyVersion ) ? 'fetch_item_list_beta' : 'fetch_item_list';
			if ( $shopItems = $this->$itemsFunction( $shopName ) ) {

				if ( count( $shopItems ) > 0 ) {
					$seenItemsList = array();
					$shopSections = get_terms( self::TAXONOMY_NAME, array( 'hide_empty' => false ) );
	 // If it's a beta shop we need to handle terms (collections) differently...
					if ( 'beta' == $folksyVersion ) {
						$collectionItems = array();
						foreach ( $shopSections AS $shopSection ) {
							$collectionItems[$shopSection->term_id] = $this->fetch_collection_items( $shopName, $shopSection->description );
						}
					}
					foreach ( $shopItems AS $shopItem ) {

	 // Find any items matching the Folksy ID...
						$matchingItems = get_posts( array( 'post_type' => self::POST_TYPE_NAME,
						                                   'meta_key' => '_folksy_id',
						                                   'meta_value' => $shopItem['id'] ) );
						if ( count( $matchingItems ) > 0 ) {

	 // We've seen the item before so let's update it if required...
							$postData = array( 'ID' => $matchingItems[0]->ID );
							if ( isset( $shopItem['description'] ) ) {
								if ( $matchingItems[0]->post_content != $shopItem['description'] ) {
									$postData['post_content'] = $shopItem['description'];
								}
							}
							if ( $matchingItems[0]->post_title != $shopItem['title'] ) {
								$postData['post_title'] = $shopItem['title'];
							}
							if ( count( $postData ) > 1 ) {
								wp_update_post( $postData );
							}

	 // Update the meta data...
							$existingMeta = get_post_meta( $matchingItems[0]->ID );
							foreach ( $this->_metaMapping AS $folksyKey => $metaKey ) {
								if ( $existingMeta[$metaKey] != $shopItem[$folksyKey] ) {
									update_post_meta( $matchingItems[0]->ID, $metaKey, $shopItem[$folksyKey] );
								}
							}
							
	 // Update the sections or categories which relate to this item. The easiest
	 // way to do this is simply to remove the item from all sections and then
	 // insert it into them again based on what Folksy told us...
							wp_delete_object_term_relationships( $pageId, self::TAXONOMY_NAME );
							if ( isset( $shopItem['section_id'] ) ) {
								foreach ( $shopSections AS $shopSection ) {
									if ( $shopSection->description == $shopItem['section_id'] ) {
										wp_set_object_terms( $matchingItems[0]->ID, (int) $shopSection->term_id, self::TAXONOMY_NAME, false );
										break;
									}
								}
	 // For new shops: check if the item belongs in any shop collections...
							} else if ( ( 'beta' == $folksyVersion ) && isset( $collectionItems ) ) {
								foreach ( $collectionItems AS $termId => $items) {
									if ( in_array( $shopItem['id'], $items ) ) {
										wp_set_object_terms( $matchingItems[0]->ID, $termId, self::TAXONOMY_NAME, true );
									}
								}
							}
							
	 // Record that we've seen this item...
							$seenItemsList[] = $matchingItems[0]->ID;

						} else {

	 // This is the first time we've seen this item, so let's insert it...
							if ( !isset( $shopItem['description'] ) ) {
								$additionalDetails = $this->fetch_item_details( $shopItem['id'] );
								$shopItem['description'] = $additionalDetails['description'];
							}
							$pageId = wp_insert_post( array( 'post_content' => $shopItem['description'],
							                                 'post_title' => $shopItem['title'],
							                                 'post_status' => 'publish',
							                                 'post_type' => self::POST_TYPE_NAME ) );
							if ( $pageId > 0 ) {

	 // Insert additional meta data relating to the item...
								foreach ( $this->_metaMapping AS $folksyKey => $metaKey ) {
									if ( isset( $shopItem[$folksyKey] ) ) {
										add_post_meta( $pageId, $metaKey, $shopItem[$folksyKey], true );
									}
								}

	 // For old shops: match to relevant category based on shop section...
								if ( isset( $shopItem['section_id'] ) ) {
									foreach ( $shopSections AS $shopSection ) {
										if ( $shopSection->description == $shopItem['section_id'] ) {
											wp_set_object_terms( $pageId, (int) $shopSection->term_id, self::TAXONOMY_NAME, false );
											break;
										}
									}
	 // For new shops: check if the item belongs in any shop collections...
								} else if ( ( 'beta' == $folksyVersion ) && isset( $collectionItems ) ) {
									foreach ( $collectionItems AS $termId => $items) {
										if ( in_array( $shopItem['id'], $items ) ) {
											wp_set_object_terms( $pageId, $termId, self::TAXONOMY_NAME, true );
										}
									}
								}
								
	 // Insert images of this item as attachments...
								if ( isset( $additionalSettings['folksy_images_download'] ) && ( true == $additionalSettings['folksy_images_download'] ) ) {
									if ( is_array( $itemImages = $this->fetch_item_images( $shopItem['id'] ) ) ) {
										$thumbnailSet = false;
										$wpUploadDir = wp_upload_dir();
										require_once( ABSPATH . 'wp-admin/includes/image.php' );
										foreach ( $itemImages AS $folksyImageDetails ) {
											$folksyImageUrl = $folksyImageDetails['id'];
											$fileUploadPath = $wpUploadDir['path'].'/'.substr( $folksyImageUrl, ( strpos( $folksyImageUrl, '/' ) + 1 ) ).'.jpg';
											if ( copy( 'http://'.$folksyImageUrl, $fileUploadPath ) ) {
												$fileTypeData = wp_check_filetype( basename( $fileUploadPath ) );
												$attachmentId = wp_insert_attachment( array( 'post_title' => $shopItem['title'].' Product Image',
												                                             'post_mime_type' => $fileTypeData['type'] ), $fileUploadPath, $pageId );
												if ( $attachmentId != 0 ) {
													wp_update_attachment_metadata( $attachmentId, wp_generate_attachment_metadata( $attachmentId, $fileUploadPath ) );
													if ( true == $folksyImageDetails['featured'] ) {
														add_post_meta( $attachmentId, '_folksy_featured', true );
														if ( false == $thumbnailSet ) {
															$thumbnailSet = set_post_thumbnail( $pageId, $attachmentId );
														}
													}
												}
											}
										}
									}
								}

							}
							
	 // Record that we've seen this item...
							$seenItemsList[] = $pageId;
							
						}
						
					}

	 // If we have to do something when items are no longer available...
					if ( isset( $additionalSettings['folksy_unavailable_action'] ) ) {
						$unavailableItems = get_posts( array( 'post_type' => self::POST_TYPE_NAME,
						                                      'post_status' => 'publish',
						                                      'posts_per_page' => -1,
						                                      'exclude' => $seenItemsList ) );
						if ( count( $unavailableItems ) > 0 ) {
							foreach ( $unavailableItems AS $unavailableItem ) {
								switch ( $additionalSettings['folksy_unavailable_action'] ) {
									case 'quantity':
										update_post_meta( $unavailableItem->ID, $this->_metaMapping['quantity'], 0 );
									break;
									case 'hide':
										wp_update_post( array( 'ID' => $unavailableItem->ID,
										                       'post_status' => 'draft' ) );
									break;
									case 'delete':
										$attachments = get_children( array( 'post_parent' => $unavailableItem->ID,
										                                    'post_type' => 'attachment' ) );
										if ( count( $attachments ) > 0 ) {
											foreach ( $attachments AS $attachment ) {
												wp_delete_attachment( $attachment->ID, true );
											}
										}
										wp_delete_post( $unavailableItem->ID );
									break;
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
  * Fetches shop collections from new Folksy shops.
  *
  * Shop collections contain the name of the section (['title']) and the Folksy
  * shop collection ID (['id']).
  *
  * @since 0.1
  * @see FolksyShop::update_sections()
  * @param string $shopname The name of the shop to fetch collections from.
  * @return array Details of the shop collections. If there are no collections then returns a blank array.
  */
		public function fetch_collections( $shopName ) {

			if ( $shopDetails = $this->_fetch_html( 'shops/'.$shopName, 'beta' ) ) {

				$shopCollectionsArray = array();
				$shopDetails = str_replace(array("\r", "\n", '  '), '', $shopDetails );
				if ( preg_match_all( '/<li class="collection">(.*?)<\/li>/', $shopDetails, $sections ) ) {

					foreach ( $sections[1] AS $sectionSegment ) {
						if ( preg_match( '/<a href="\/shops\/[a-z]+\/collections\/(\d+)"><span class="image"><img alt=".*?" src=".*?" \/><i>\d+<\/i><\/span><span class="text">(.*?)<\/span><\/a>/i', $sectionSegment, $sectionDetails ) ) {
							$shopCollectionsArray[] = array( 'id' => $sectionDetails[1],
							                                 'title' => html_entity_decode( $sectionDetails[2] ) );
						}
					}
				}

				return $shopCollectionsArray;

			} else {
				return false;
			}

		}
		
 /**
  * Fetches a list of all items in a specified shop collection from new Folksy
  * shops.
  *
  * @since 0.1
  * @see FolksyShop::fetch_collections()
  * @param string $shopname The name of the shop to which the specified collection belongs.
  * @param int $collectionId Folksy ID of the collection to fetch.
  * @return array Folksy IDs of all items in the section. If there are no items in the collection then returns a blank array.
  */
		public function fetch_collection_items( $shopName, $collectionId ) {
			
			$page = 1;
			$collectionItemsArray = array();
			while (true) {
				if ( $collectionDetails = $this->_fetch_html( 'shops/'.$shopName.'/collections/'.$collectionId.'/items?page='.$page++, 'beta' ) ) {

					$collectionDetails = str_replace(array("\r", "\n", '  '), '', $collectionDetails );
					if ( preg_match_all( '/<li class="item">(.*?)<\/li>/', $collectionDetails, $items ) ) {
						$itemCount = count( $items[1] );
						if ( $itemCount > 0 ) {
							foreach ( $items[1] AS $itemSegment ) {
								if ( preg_match( '/<a href="http:\/\/folksy.com\/items\/(\d+)-[a-z0-1-]+">/i', $itemSegment, $itemDetails ) ) {
									$collectionItemsArray[] = $itemDetails[1];
								}
							}
							if ( $itemCount < 60 ) {
								break;
							}
						} else {
							break;
						}
					}

				} else {
					break;
				}
			}
			
			return $collectionItemsArray;
		
		}

 /**
  * Updates shop taxonomies from shop sections using Folksy as the base source.
  * The title of the shop section is used as the term itself, and we use the
  * description field to hold the Folksy shop ID because we need this to match
  * categories to items.
  *
  * @since 0.1
  * @see FolksyShop::fetch_sections()
  * @param string $shopname Name of the shop to update items from.
  * @param string $folksyVersion Version of Folksy this fetch should come from. 'main' or 'beta'.
  */
		public function update_sections( $shopName, $folksyVersion = 'main' ) {

			$sectionFunction = ( 'beta' == $folksyVersion ) ? 'fetch_collections' : 'fetch_sections';
			if ( $shopSections = $this->$sectionFunction( $shopName ) ) {

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

			register_setting( self::OPTIONS_NAME, self::OPTIONS_NAME, array( $this, 'sanitize_settings' ) );

		}

 /**
  * Validates and sanitises the settings submitted from the settings page.
  *
  *  - Usernames must be between 3 and 40 letters or numbers only.
  *  - Download image option must be either 'yes' or 'no'. Anything else will
	*    default to 'no'.
  *
  * @since 0.1
  */
		public function sanitize_settings( $settings ) {

			$existingOptions = get_option( self::OPTIONS_NAME );

	 // The username has been unlocked, so we'll wipe out all the currently stored
	 // items and shop sections (you were warned!)...
			if ( isset( $settings['unlock'] ) ) {
				set_transient( 'folksy_username_unlock', true, 60 );
				$allItems = get_posts( array( 'posts_per_page' => -1,
				                              'post_type' => self::POST_TYPE_NAME ) );
				if ( count( $allItems ) > 0 ) {
					foreach ( $allItems AS $item ) {
						$attachments = get_children( array( 'post_parent' => $item->ID,
						                                    'post_type' => 'attachment' ) );
						if ( count( $attachments ) > 0 ) {
							foreach ( $attachments AS $attachment ) {
								wp_delete_attachment( $attachment->ID, true );
							}
						}
						wp_delete_post( $item->ID, true );
					}
				}
				$allSections = get_terms( self::TAXONOMY_NAME, array( 'hide_empty' => false ) );
				if ( count( $allSections ) > 0 ) {
					foreach ( $allSections AS $section ) {
						wp_delete_term( $section->term_id, self::TAXONOMY_NAME );
					}
				}
				add_settings_error( 'folksy_username', 'folksy_username', 'Username unlocked. <br />Folksy items and shop categories have been removed.', 'updated' );
				return $existingOptions;
			}

	 // Clean up changed settings if the username wasn't unlocked...
	 // Username handling...
			if ( isset( $settings['folksy_username'] ) ) {
				if ( preg_match( '/^[a-z-09]{3,40}$/i', $settings['folksy_username'] ) ) {
					$settings['new_shop'] = $this->_is_new_shop( $settings['folksy_username'] );
				} else {
					add_settings_error( 'folksy_username', 'folksy_username', 'Folksy usernames must be between 3 and 40 characters and contain only letters and numbers.', 'error' );
					$settings['folksy_username'] = ( !empty( $existingOptions['folksy_username'] ) ) ? $existingOptions['folksy_username'] : '';
				}
			} else {
				$settings['folksy_username'] = $existingOptions['folksy_username'];
			}

	 // Unavailable items options...
			if ( isset( $settings['folksy_unavailable_action'] ) ) {
				switch ( $settings['folksy_unavailable_action'] ) {
					case 'quantity': case 'hide': case 'delete':
						$settings['folksy_unavailable_action'] = $settings['folksy_unavailable_action'];
					break;
					case 'nothing': default:
						unset($settings['folksy_unavailable_action']);
					break;
				}
			}
			
	 // Image download options...
			if ( isset( $settings['folksy_images_download'] ) ) {
				switch ( $settings['folksy_images_download'] ) {
					case 'yes':
						$settings['folksy_images_download'] = true;
					break;
					case 'no': default:
						$settings['folksy_images_download'] = false;
					break;
				}
			}
			
	 // Sections slug...
			if ( isset( $settings['folksy_sections_slug'] ) ) {
				$settings['folksy_sections_slug'] = sanitize_title($settings['folksy_sections_slug']);
			}
			
	 // Items slug...
			if ( isset( $settings['folksy_items_slug'] ) ) {
				$settings['folksy_items_slug'] = sanitize_title($settings['folksy_items_slug']);
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

	 // Remove the 'Add New' option from the 'Folksy Listings' menu...
			global $submenu;
			unset($submenu['edit.php?post_type='.self::POST_TYPE_NAME][10]);
			
	 // Add 'Folksy Shop' option to the settings menu...
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

			$folksyShopOptions = get_option( self::OPTIONS_NAME );
			$unlockFlag = get_transient( 'folksy_username_unlock' );
			if ( 1 == $unlockFlag ) {
			  delete_transient( 'folksy_username_unlock' );
			}
			if ( empty( $folksyShopOptions['folksy_username'] ) ) {
				$folksyShopOptions['folksy_username'] = '';
			}

			if ( empty( $folksyShopOptions['folksy_sections_slug'] ) ) {
				$folksyShopOptions['folksy_sections_slug'] = 'folksy-section';
			}
			if ( empty( $folksyShopOptions['folksy_items_slug'] ) ) {
				$folksyShopOptions['folksy_items_slug'] = 'folksy';
			}
			
			require_once( 'folksy-shop-settings.php' );

		}
		
 /* Protected and private methods for internal use only. */

 /**
  * Checks if the provided shop is an old or new (beta shops launched October
  * 2013) store front.
  *
  * @since 0.1
  * @param string $shopName Name of the shop to check.
  * @return boolean True if the given shop is a new shop, false if it is not.
  */
		protected function _is_new_shop( $shopName ) {

      if ( $shopDetails = $this->_fetch_html( 'shops/'.$shopName, 'beta' ) ) {
				return true;
			} else {
				return false;
			}
		
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
  * @see FolksyShop::_fetchFolksyDocument
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @param string $folksyVersion Version of Folksy this fetch should come from. 'main' or 'beta'.
  * @return object|boolean An object representing the Folksy JSON reponse, or false if the request failed.
  */
		protected function _fetch_json( $pagePath, $folksyVersion = 'main' ) {

			if ( $rawJson = $this->_fetch_document( $pagePath, 'json', $folksyVersion ) ) {
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
  * @param string $folksyVersion Version of Folksy this fetch should come from. 'main' or 'beta'.
  * @return string|boolean The contents of the page requested, or false if the request failed.
  */
		protected function _fetch_html( $pagePath, $folksyVersion = 'main' ) {

			return $this->_fetch_document( $pagePath, 'html', $folksyVersion );

		}

 /**
  * Fetches a document from Folksy if one is available. If it is not available
  * (ie. the page is a 404 error or it was an invalid request in the first
  * place) then false is returned.
  *
  * @since 0.1
  * @param string $pagePath Path (from /) of page to fetch from Folksy.
  * @param string $accept Switch controlling accept header. Json used for "old" site to request handy API style data.
  * @param string $folksyVersion Version of Folksy this fetch should come from. 'main' or 'beta'.
  * @return string|boolean The contents of the page requested, or false if the request failed.
  */
		protected function _fetch_document( $pagePath, $accept = 'html', $folksyVersion = 'main' ) {

			if ( !empty( $pagePath ) ) {
				$pagePath = ( substr( $pagePath, 0, 1 ) == '/' ) ? substr( $pagePath, 1 ) : $pagePath;
				if ( 'beta' == $folksyVersion ) {
					$curlHandle = curl_init( self::FOLSKY_BASE_URL_BETA.$pagePath );
				} else {
					$curlHandle = curl_init( self::FOLSKY_BASE_URL.$pagePath );
				}
				if ( $curlHandle ) {

					$curlOptions = array( CURLOPT_HEADER => false,
					                      CURLOPT_RETURNTRANSFER => true,
					                      CURLOPT_USERAGENT => 'Folksy Shop for WordPress (v'.self::PLUGIN_VERSION.') https://github.com/confuzzledduck/folksy-shop' );
					if ( 'json' == $accept ) {
						$curlOptions[CURLOPT_HTTPHEADER] = array( 'Accept: application/json, text/javascript' );
					} else {
						$curlOptions[CURLOPT_HTTPHEADER] = array( 'Accept: text/html, application/xhtml+xml, application/xml' );
					}
					curl_setopt_array( $curlHandle, $curlOptions );

					$rawResponse = curl_exec( $curlHandle );
					$httpCode = curl_getinfo( $curlHandle, CURLINFO_HTTP_CODE );
					curl_close( $curlHandle );

					if ( 200 != $httpCode ) {
						return false;
					} else {
						return $rawResponse;
					}

				}
			}

			return false;

		}
		
	}

	 // Create the class and get going...
	$folksy = new Folksy_Shop();

}

/* Template tags. */

 /**
  * Retrieve price for an item.
  *
  * @since 0.1
  * @param int|object $post Optional. Post ID or object.
  * @return string The price of the item formatted as %.2f (00.00).
  */
function get_folksy_price( $post = 0 ) {

	$post = get_post( $post );
	return sprintf( '%.2f', get_post_meta( $post->ID, '_price', true ) / 100 );

}

 /**
  * Display the price for an item preceeded by a pound sign (£). Must be called
  * from inside "The Loop".
  *
  * @since 0.1
  * @param string $before Optional HTML to display before the link.
  * @param string $after Optional HTML to display after the link.
  */
function folksy_price( $before = '', $after = '' ) {

	echo $before.esc_html( '&pound;'.get_folksy_price() ).$after;

}

 /**
  * Retrieve Folksy link for an item.
  *
  * @since 0.1
  * @param int|object $post Optional. Post ID or object.
  * @return string The URL of the item on Folksy.
  */
function get_folksy_link( $post = 0 ) {

	$post = get_post( $post );
	$slug = str_replace( '?folksy_item=', '', basename( get_permalink( $post->ID ) ) );
	return Folksy_Shop::FOLSKY_BASE_URL.'items/'.get_post_meta( $post->ID, '_folksy_id', true ).'-'.$slug;

}

 /**
  * Display the Folksy link for an item. Must be called from inside "The Loop".
  *
  * @since 0.1
  * @param string $text Optional The link text or HTML to be displayed. Defaults to 'View on Folksy'.
  * @param string $title Optional The tooltip for the link. Must be sanitized. Defaults to the sanitized post title.
  * @param string $before Optional HTML to display before the link.
  * @param string $after Optional HTML to display after the link.
  */
function folksy_link( $text = 'View on Folksy', $title = '', $before = '', $after = '' ) {

	if ( empty( $title ) ) {
		$title = the_title_attribute( array( 'echo' => false ) );
	}

	$link = '<a href="'.esc_url( get_folksy_link() ).'" title="'.esc_attr( $title ).'">'.esc_html( $text ).'</a>';
	echo $before.$link.$after;

}

 /**
  * Returns true if the Folksy shop linked to this blog was on holiday the last
  * time we checked for updated items.
  *
  * @since 0.1
  * @return boolean True if the shop is on holiday, false if not.
  */
function is_folksy_holiday() {

	$folksyOptions = get_option( self::OPTIONS_NAME );
		if ( isset( $folksyOptions['remote']['shop_holiday'] ) ) {
			if ( true == $folksyOptions['remote']['folksy_shop_holiday'] ) {
				return true;
			}
		}
		
		return false;

}