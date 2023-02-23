<?php
/*
 * Plugin Name: Language Switcher
 * Plugin URI: https://code.recuweb.com/download/language-switcher/
 * Description: Add a Language Switcher to Post Types and Taxonomies
 * Version: 3.7.1
 * Author: Rafasashi
 * Author URI: https://code.recuweb.com/about-us/
 * Requires at least: 4.6
 * Tested up to: 6.2
 * Tags: language switcher, languages, internationalisation, internationalization, language, switcher, multilingual
 *
 * Text Domain: language-switcher
 * Domain Path: /lang/
 * 
 * Copyright: © 2018 Recuweb.
 * License: GNU General Public License v3.0
 * License URI: https://code.recuweb.com/product-licenses/
 */

	if(!defined('ABSPATH')) exit; // Exit if accessed directly
	
	if( defined('REST_REQUEST') && REST_REQUEST === true ) return; // Disabled for REST API
	
	/**
	* Minimum version required
	*
	*/
	if ( get_bloginfo('version') < 3.3 ) return;
	
	// Load plugin class files
	
	require_once( 'includes/class-language-switcher.php' );
	require_once( 'includes/class-language-switcher-settings.php' );
	
	// Load plugin libraries
	
	require_once( 'includes/lib/class-language-switcher-admin-api.php' );
	require_once( 'includes/lib/class-language-switcher-post-type.php' );
	require_once( 'includes/lib/class-language-switcher-taxonomy.php' );
	
	// Load widget libraries
	
	require_once( 'includes/widgets/class-language-switcher-widget.php' );
	
	/**
	 * Returns the main instance of Language_Switcher to prevent the need to use globals.
	 *
	 * @since  1.0.0
	 * @return object Language_Switcher
	 */
	function Language_Switcher() {
				
		$instance = Language_Switcher::instance( __FILE__, time() );	
				
		if ( is_null( $instance->settings ) ) {
			
			$instance->settings = Language_Switcher_Settings::instance( $instance );
		}

		return $instance;
	}

	Language_Switcher();