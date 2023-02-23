<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Language_Switcher {

	/**
	 * The single instance of Language_Switcher.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;
	
	public $_base 	= null;
	public $_prefix = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $filesystem = null;
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;
	public $views;
	public $lang;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */

	public static $plugin_prefix;
	public static $plugin_url;
	public static $plugin_path;
	public static $plugin_basefile;
	
	public $items;
	public $labels 		= array();
	public $post_types 	= array();
	public $taxonomies 	= array();
	
	public $locale;
	public $locales;
	public $language;
	public $languages;
	
	public $active_post_types;
	public $active_taxonomies;
	public $active_languages;
	
	public $switchers = array();
	
	// addons
	
	public $everywhere;
	public $synchronizer;
	public $importer;
	 
	public function __construct ( $file = '', $version = '1.0.0' ) {
		
		$this->_version = $version;
		$this->_token 	= 'language-switcher';
		$this->_base 	= 'lsw_';
		$this->_prefix	= $this->get_cookie_prefix();

		// Load plugin environment variables
		
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->lang   		= trailingslashit( $this->dir ) . 'lang';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= home_url( trailingslashit( str_replace( ABSPATH, '', $this->dir ))  . 'assets/' );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		Language_Switcher::$plugin_prefix 		= $this->_base;
		Language_Switcher::$plugin_basefile 	= $this->file;
		Language_Switcher::$plugin_url 			= plugin_dir_url($this->file); 
		Language_Switcher::$plugin_path 		= trailingslashit($this->dir);

		// register plugin activation hook
		
		//register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions
		
		$this->admin = new Language_Switcher_Admin_API($this);

		/* Localisation */
		
		add_filter('locale', function ($locale){
			
			if( !is_admin() && !$this->is_disabled('switch_to_locale') && !empty($_COOKIE[$this->_prefix . 'm']) ){
			
				$locale = $this->get_locale_by_code(sanitize_text_field($_COOKIE[$this->_prefix . 'm']));
			
				if(!defined('WPLANG')){
					
					define('WPLANG',$locale);
				}
			}

			return $locale;
			
		},99999999);
		
		$this->locale = apply_filters('plugin_locale', get_locale(), 'language-switcher');
		
		load_textdomain('language-switcher', Language_Switcher::$plugin_path . 'lang/language-switcher-'.$this->locale.'.mo');
		load_plugin_textdomain('language-switcher', false, Language_Switcher::$plugin_path . 'lang/');
		
		add_action('admin_init', array($this, 'init_backend'));
			
		add_action('init', array($this, 'init_language'));
		
		// shorcodes
		
		add_shortcode('language-switcher', array($this,'get_language_switcher_shortcode') );
		
		//widgets
		
		add_action('widgets_init', array($this,'init_widgets'));		
		
		//menus
		
		add_filter('wp_nav_menu_objects', array($this,'get_language_switcher_menu'), 9999, 2 );
		
		add_action('wp_head', array($this,'add_hreflang_in_head'));
		
		// multisite sync hooks
		
		add_action('msc_before_export_post_meta', array($this,'filter_export_post_meta'),0,3);

		add_action('msc_before_import_post_meta', array($this,'filter_import_post_meta'),0,5);
				
		add_action('msc_before_export_term_meta', array($this,'filter_export_term_meta'),0,3);

		add_action('msc_before_import_term_meta', array($this,'filter_import_term_meta'),0,5);
		
	} // End __construct ()
	
	public function get_cookie_prefix(){
		
		if( is_null($this->_prefix) ){
		
			global $wpdb;
		
			$this->_prefix = $this->_base . hash('crc32', $wpdb->prefix ) . '_';
		}
		
		return $this->_prefix;
	}
	
	public function is_disabled($feature){
	
		if( get_option($this->_base . 'disable_' . $feature) == 'on' ) 
		
			return true;
			
		return false;
	}
	
	public function is_session_started(){
		
		if ( php_sapi_name() !== 'cli' ) {
			
			if ( version_compare(phpversion(), '5.4.0', '>=') ) {
				
				return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
			} 
			else {
				
				return session_id() === '' ? FALSE : TRUE;
			}
		}
		
		return FALSE;
	}
	
	public function init_language(){
		
		//get current language
		
		add_filter('wp', array($this, 'get_current_language'));
		
		if( !is_admin() ){
		
			//filter languages
			
			if( !$this->is_disabled('posts_query_filter') ){
			
				add_filter('pre_get_posts', array( $this, 'query_language_posts') );
			}
			
			if( !$this->is_disabled('terms_query_filter') ){
			
				add_filter('get_terms_args', array( $this, 'query_language_taxonomies'), 10, 2 );
			}
			
			//filter menus
			
			if( !$this->is_disabled('menus_query_filter') ){
			
				add_filter('wp_get_nav_menu_items', array( $this, 'filter_language_menus'), 10, 9999999 );
			}
			
			/*
			if( !$this->is_disabled('comments_query_filter') ){
			
				add_filter('pre_get_comments', array( $this, 'query_language_comments') );
			}
			*/			
		}
		
		//append urls
		
		add_filter('month_link', array( $this, 'get_month_link'), 10, 3 );
		
		//add switchers

		add_action( 'wp_footer', array( $this, 'add_switchers'), 100);
	}
	
	public function init_widgets() {
		
		$widget = new Language_Switcher_Widget( $this );
		
		register_widget($widget);
	}
	
	public function normalize_url($url,$current_url) {
		
		$proto = ( is_ssl() ? 'https://' : 'http://' );	

		if( $url[0] == '/' ){

			$url = home_url( $url );
		}
		else{
			
			$u = parse_url($url);
			
			if( empty($u['host']) ){
				
				$url = $current_url . '/' . $url;
			}
			elseif( empty($u['scheme']) ){
				
				$url = $proto . $url;
			}
		}
		
		return $url;
	}
	
	public function get_post_language($post_id){
		
		$default_lang = $this->get_default_language(true);
		
		$language = get_post_meta( $post_id, $this->_base . 'language_switcher' ,true );
		
		if( empty($language) || !is_array($language) ){
			
			$language = array();
		}

		if( !isset($language['urls']) ){
			
			$language['urls'] = array();
		}
		
		if( !isset($language['main']) ){
			
			$language['main'] = get_post_meta( $post_id, $this->_base . 'main_language' ,true );
		}
		
		if( empty($language['main']) ){
			
			$parent_id  = wp_get_post_parent_id( $post_id );
			
			while( $parent_id && $parent_id > 0 ){
			
				$parent_language = get_post_meta( $parent_id, $this->_base . 'language_switcher' ,true );
				
				if( !is_array($parent_language) )
					
					$parent_language = array();
				
				if( !isset($parent_language['main']) ){
					
					$parent_language['main'] = get_post_meta( $parent_id, $this->_base . 'main_language' ,true );
				}				
				
				if( !empty($parent_language['main']) ){
					
					$language['main'] = $parent_language['main'];
					
					break;
				}
				else{
					
					$parent_id  = wp_get_post_parent_id( $parent_id );
				}
			}

			if( !empty($language['main']) ){
				
				//update post language
				
				update_post_meta( $post_id, $this->_base . 'language_switcher', $language);
				
				update_post_meta( $post_id, $this->_base . 'main_language', $language['main']);
			}
			else{
			
				$language['main'] = $default_lang;
			}			
		}
		
		if( empty($language['urls'][$language['main']]) ){
		
			$language['urls'][$language['main']] = apply_filters('lsw_sanitize_link',get_permalink($post_id));
		}
		
		return $language;
	}
	
	public function get_term_language($term_id){
		
		$default_lang = $this->get_default_language(true);
		
		$language = get_term_meta( $term_id, 'language_switcher' ,true );
		
		if( !is_array($language) ){
			
			$language = array();
		}
		
		if( !isset($language['urls']) ){
			
			$language['urls'] = array();
		}
	
		$language['urls'][$default_lang] = apply_filters('lsw_sanitize_link',get_term_link($term_id));
		
		if( empty($language['main']) ){
			
			$language['main'] = $default_lang;
		}
		
		return $language;
	}
	
	public function get_browser_language(){
		
		$language='';
		
		if( get_option($this->_base . 'detect_browser_language') == 'on' ){
			
			if( !empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) && preg_match_all( '#([^;,]+)(;[^,0-9]*([0-9\.]+)[^,]*)?#i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches, PREG_SET_ORDER ) ) {
			
				if( $languages = $this->get_active_languages() ){

					$priority = 1.0;
					
					$prefered_languages = array();
					
					foreach ( $matches as $match ) {
						
						if ( ! isset( $match[3] ) ) {
							
							$pr       = $priority;
							$priority -= 0.001;
						} 
						else {
							
							$pr = (float)$match[3];
						}
						
						$prefered_languages[ str_replace( '-', '_', $match[1] ) ] = $pr;
					}

					arsort( $prefered_languages, SORT_NUMERIC );

					$browser_languages = array_keys( $prefered_languages );
					
					foreach ( $browser_languages as $browser_language ) {
						
						if( in_array($browser_language,$languages) ){
							
							$language = $browser_language;
							break;
						}
					}
				}
			}
		}
		
		return $language;
	}
	
	public function get_default_language($skip_cookie=false){
		
		if( !$skip_cookie && !empty($_COOKIE[$this->_prefix . 'd']) ){
			
			$default_lang = sanitize_text_field($_COOKIE[$this->_prefix . 'd']);
		}
		else{
			
			$default_locale = get_option('WPLANG');
			
			if( empty($default_locale) ){
				
				if( defined('WPLANG') && !empty(WPLANG) ){
					
					$default_locale = WPLANG;
				}
				else{
					
					$default_locale = get_locale();
				}
			}
			
			$default_lang = $this->get_code_by_locale($default_locale);
		}
		
		return $default_lang;	
	}

	public function get_current_language(){

		if( empty($this->language) ){
			
			$default_lang = $this->get_default_language();
			
			$default_urls = get_option( $this->_base . 'default_language_urls' );

			if( is_singular() && !is_front_page() ){
				
				if( !$language = $this->get_post_language( get_queried_object_id() )){
					
					$language = array();
				}

				if( empty($language['main']) ){
					
					$language['main'] = $default_lang;
				}
			}
			elseif( is_category() || is_tag() || is_tax() ){
				
				if( $queried = get_queried_object() ){
				
					$term_id = $queried->term_id;
					
					if( !$language = get_term_meta( $term_id, 'language_switcher' ,true )){
					
						$language = array();
					}
				}
				
				if( empty($language['main']) ){
					
					$language['main'] = $default_lang;
				}				
			}
			elseif( is_archive() ){
								
				$language = array(
				
					'urls' => $default_urls,
					'main' => (!empty($_REQUEST['lang']) ? sanitize_title($_REQUEST['lang']) : $default_lang),
				);
				
				if( !empty($language['urls']) ){
					
					foreach( $language['urls'] as $iso => $url ){
						
						if( $iso != $default_lang ){
							
							$language['urls'][$iso] = add_query_arg( array('lang' => $iso), $this->get_current_url() );
						}
						else{
							
							$language['urls'][$iso] = remove_query_arg( array('lang'), $this->get_current_url() );
						}
					}
				}
			}
			elseif( !empty($_REQUEST['lang']) ){
			
				$language = array(
				
					'urls' => $default_urls,
					'main' => sanitize_title($_REQUEST['lang']),
				);
			}
			else{
				
				$language = array(
				
					'urls' => $default_urls,
					'main' => $default_lang,
				);
			}
			
			//set default language
				
			$language['default'] = $default_lang;

			if( !empty($language['main']) ){
				
				// get main language
				
				$main_lang = $language['main'];
				
				$locale = $this->get_locale_by_code($main_lang);
				
				//get current url
				
				$current_url = $this->get_current_url();
				
				if( !empty($language['urls']) ){
				
					foreach( $language['urls'] as $iso => $url ){
						
						if( !empty($url) ){
							
							$language['urls'][$iso] = $this->normalize_url($url,$current_url);
						}
					}
				}
				
				if( !is_admin() ){
					
					// set cookies & switch language
					
					if( !isset($_COOKIE[$this->_prefix . 'm']) || $_COOKIE[$this->_prefix . 'm'] != $language['main'] || !isset($_COOKIE[$this->_prefix . 'd']) || $_COOKIE[$this->_prefix . 'd'] != $language['default'] ) {
						
						//set cookies
						
						setcookie($this->_prefix . 'm', $language['main'], 0, '/');
						
						setcookie($this->_prefix . 'd', $language['default'], 0, '/');
					}	
					elseif( !empty( $language['main'] ) && !$this->is_disabled('switch_to_locale') ){
						
						// switch locale
						
						$locale = $this->get_locale_by_code($language['main']);

						switch_to_locale( $locale );
					}
				}
			}
			
			
			// set language

			$this->language = $language;
		}
		
		return $this->language;
	}
	
	public function query_language_posts( $query ){
		
		$has_language = true;
		
		if( empty($_REQUEST['lang']) 
			&& 	 !is_archive() 
			&& 	 !is_home() 
			&& ( !is_object($query) 
				|| 	empty($query->query) 
				|| 	!isset($query->query['post_type']) 
				|| 	!is_array($this->get_active_post_types()) 
				|| 	!in_array($query->query['post_type'],$this->get_active_post_types() )
			)
		){
			
			$has_language = false;
		}
		
		if($has_language){
			
			$language = array();
			
			$default_lang = $this->get_default_language();
			
			if( $query->is_main_query() ){
				
				if( $query->is_category() || $query->is_tag() || $query->is_tax() ){
					
					$queried = get_queried_object();
					
					if( !empty($queried->term_id) ){
						
						if( !$language = get_term_meta( $queried->term_id, 'language_switcher' ,true ) ){
							
							$language = array();
						}
				
						$language['default'] = $default_lang;
					}
				}
				elseif( $query->is_archive() || is_home() ){
					
					//date archive
					
					if( !empty($_REQUEST['lang']) ){
						
						$language['main'] = sanitize_title($_REQUEST['lang']);
					}
					else{
						
						$language['main'] = $default_lang;
					}
					
					$language['default'] = $default_lang;
				}
			}
			elseif( !empty($_COOKIE[$this->_prefix . 'm']) ){
				
				$lang_loc = sanitize_text_field($_COOKIE[$this->_prefix . 'm']);
				
				$lang_loc = explode('-',$lang_loc);
				
				$lang = $lang_loc[0];
				
				if( !isset($query->query['post_type']) || $query->query['post_type'] != 'nav_menu_item' ){
				
					$language['main'] 		= $lang;
					$language['default'] 	= ( !empty($_COOKIE[$this->_prefix . 'd']) ? sanitize_text_field($_COOKIE[$this->_prefix . 'd']) : $default_lang );
				}
			}
			
			if( !empty($language['main']) ){
				
				if( $language['main'] == $language['default'] ){

					$query->set( 'meta_query', array(
						'relation'		=> 'OR',
						array(
							'key' 		=> $this->_base . 'main_language',
							'value' 	=> '',
							'compare' 	=> 'NOT EXISTS',
						),
						array(
							'key' 		=> $this->_base . 'main_language',
							'value' 	=> $language['main'],
							'compare' 	=> 'LIKE',
						),
					));
				}
				else{
					
					$query->set( 'meta_query', array(
						array(
							'key' 		=> $this->_base . 'main_language',
							'value' 	=> $language['main'],
							'compare' 	=> 'LIKE',
						),
					));
				}
			}
		}

		return $query;
	}
		
	public function query_language_taxonomies( $args, $taxonomies ){
		
		$has_language = false;
		
		if( !empty($taxonomies) ){
			
			$active_taxonomies = $this->get_active_taxonomies();
		
			foreach( $taxonomies as $taxonomy){

				if( is_string($taxonomy) && is_array($active_taxonomies) ){
				
					if( in_array( $taxonomy, $active_taxonomies ) ){
						
						$has_language = true;
					}
				}
			}
		}
		
		if( $has_language ){
		
			$language = '';
			
			$default_lang = $this->get_default_language();
			
			if( !empty($_COOKIE[$this->_prefix . 'm']) ){
				
				$lang_loc = sanitize_text_field($_COOKIE[$this->_prefix . 'm']);
				
				$lang_loc = explode('-',$lang_loc);
				
				$lang = $lang_loc[0];
				
				if( $_COOKIE[$this->_prefix . 'm'] != $default_lang ){
					
					$args['meta_key'] 	= $this->_base . 'main_language';
					$args['meta_value'] = $lang;					
				}
				else{

					$args['meta_query'] = array(
					   'relation' => 'OR',
						array(
							'key' 		=> $this->_base . 'main_language',
							'compare' 	=> 'NOT EXISTS'
						),
						array(
						 'key' 		=> $this->_base . 'main_language',
						 'value' 	=> $lang,
						)
					);
				}
			}
		}
		
		return $args;
	}
	
	public function query_language_comments( $query ){
		
		$default_lang = $this->get_default_language();
		
		$language = array(
			
			'main'		=> $default_lang,
			'default'	=> $default_lang,
		);
		
		if( !empty($_COOKIE[$this->_prefix . 'm']) ){
				
			$lang_loc = sanitize_text_field($_COOKIE[$this->_prefix . 'm']);
			
			$lang_loc = explode('-',$lang_loc);
			
			$lang = $lang_loc[0];
			
			$language['main'] = $lang;
			$language['default'] = ( !empty($_COOKIE[$this->_prefix . 'd']) ? sanitize_text_field($_COOKIE[$this->_prefix . 'd']) : $default_lang );
		}	
		
		if( $language['main'] == $language['default'] ){

			$query->query_vars['meta_query'] = array(
				'relation'		=> 'OR',
				array(
					'key' 		=> $this->_base . 'main_language',
					'value' 	=> '',
					'compare' 	=> 'NOT EXISTS',
				),
				array(
					'key' 		=> $this->_base . 'main_language',
					'value' 	=> $language['main'],
					'compare' 	=> 'LIKE',
				),
			);
		}
		else{
			
			$query->query_vars['meta_query'] = array(
				array(
					'key' 		=> $this->_base . 'main_language',
					'value' 	=> $language['main'],
					'compare' 	=> 'LIKE',
				),
			);
		}
		
		$query->meta_query->parse_query_vars( $query->query_vars );

		return $query;
	}
	
	public function get_month_link($monthlink, $year, $month){
		
		$language = $this->get_current_language();
		
		if( !empty($language['main']) ){
			
			if( $language['main'] != $language['default'] ){
			
				$monthlink = add_query_arg( 'lang', $language['main'], $monthlink );
			}
		}
		
		return $monthlink;
	}
	
	public function filter_language_menus( $menu, $args ){
		
		$language = $this->get_current_language();
		
		foreach( $menu as $i => $item ){
			
			if( $item->type == 'post_type' || $item->type == 'taxonomy' ){
			
				$menu_language = array();
				
				if( $item->type == 'post_type' ){
					
					$menu_language = (array) $this->get_post_language($item->object_id);
				}
				elseif( $item->type == 'taxonomy' ){
					
					$menu_language = (array) get_term_meta( $item->object_id, 'language_switcher' ,true );
				}
				
				if( empty($menu_language['main']) ){
					
					$menu_language['main'] = $language['default'];
				}
				
				if( $menu_language['main'] != $language['main'] ){
					
					unset($menu[$i]);
				}
			}
			elseif( $language['main'] != $language['default'] ){
				
				$item->url = add_query_arg( 'lang', $language['main'], $item->url);
			}
		}
		
		return $menu;
	}
	
	public function query_admin_language_post_type( $query ){
		
		if( !empty($_REQUEST['lang']) ){
		
			$query->set( 'meta_query', array(
				array(
					'key' 		=> $this->_base . 'main_language',
					'value' 	=> sanitize_title($_REQUEST['lang']),
					'compare' 	=> 'LIKE',
				),
			));	
		}		
		
		return $query;
	}
	
	public function query_admin_language_taxonomy( $args, $taxonomies ){
		
		if( !empty($_REQUEST['lang']) ){
			
			$args['meta_key'] 	= $this->_base . 'main_language';
			$args['meta_value'] = sanitize_title($_REQUEST['lang']);
		}

		return $args;
	}
	
	public function init_backend(){
		
		if( in_array( basename($_SERVER['SCRIPT_FILENAME']), array('post.php','post-new.php','edit.php') ) ){

			//add language in post types
			
			if( $post_types = $this->get_active_post_types() ){
				
				foreach( $post_types as $post_type ){
					
					add_action( 'add_meta_boxes', function(){
						
						foreach( $this->get_active_post_types() as $post_type ){
							
							$this->admin->add_meta_box (
							
								'language_switcher',
								__( 'Languages', 'language-switcher' ), 
								array($post_type),
								'side',
								'default'
							);
						}
					});
					
					add_filter( $post_type . '_custom_fields', array( $this, get_post_type_object( $post_type )->public ? 'add_post_type_language_switcher_with_url' : 'add_post_type_language_switcher_without_url' ));
				
					add_action( 'save_post_' . $post_type, array( $this, 'save_language_post_type' ), 10, 3 );
				
					add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'set_language_post_type_columns' ) );
				
					add_action( 'manage_'.$post_type.'_posts_custom_column' , array( $this, 'get_language_post_type_column' ), 10, 2 );
				}
			
				add_filter( 'pre_get_posts', array( $this, 'query_admin_language_post_type') );
			}
		}		
		elseif( in_array( basename($_SERVER['SCRIPT_FILENAME']), array('term.php','edit-tags.php') ) ){
		
			//add language in taxonomies
			
			if($taxonomies = $this->get_active_taxonomies()){
				
				foreach( $taxonomies as $taxonomy ){
				
					add_action( $taxonomy . '_edit_form_fields', array($this, 'add_language_switcher_taxonomy_field'), 10, 2 );
				
					add_action( 'edited_' . $taxonomy, array($this, 'save_language_taxonomy'), 10, 2 );
				
					add_filter( 'manage_edit-'.$taxonomy.'_columns' , array( $this, 'set_language_taxonomy_columns' ) );
					
					add_action( 'manage_'.$taxonomy.'_custom_column', array( $this, 'get_language_taxonomy_column' ), 10,3 );			
				}
			}
			
			add_filter( 'get_terms_args', array( $this, 'query_admin_language_taxonomy'), 10, 2 );			
		}
	}
	
	public function set_language_post_type_columns($columns) {
		
		$columns['language'] = '<img src="' . $this->assets_url . '/images/language-icon.png" alt="">';

		return $columns;
	}
	
	public function get_language_post_type_column( $column, $post_id ) {
		
		if( !isset($this->items[$post_id])  ){
			
			$this->items[$post_id] = get_post_meta($post_id);
			
			if( isset($this->items[$post_id][ $this->_base . 'main_language'][0]) ){
				
				$this->items[$post_id][ $this->_base . 'main_language'] = $this->items[$post_id][ $this->_base . 'main_language'][0];
			}
			else{
				
				$this->items[$post_id][ $this->_base . 'main_language'] = '';
			}
		}
		
		switch ( $column ) {
			
			case 'language' :
				
				if( !empty($this->items[$post_id][ $this->_base . 'main_language']) ){
								
					$url = add_query_arg( 'lang', $this->items[$post_id][ $this->_base . 'main_language'], $this->get_current_url() );
					
					$html = '<a href="' . esc_url($url) .'">';
					
						$html .= strtoupper($this->items[$post_id][ $this->_base . 'main_language']);
				
					$html .= '</a>';
					
					echo wp_kses_normalize_entities($html);
				}
				
			break;
		}
		
		return $column;
	}
	
	public function set_language_taxonomy_columns($columns) {
		
		$columns['language'] = '<img src="' . $this->assets_url . '/images/language-icon.png" alt="">';

		return $columns;
	}
	
	public function get_language_taxonomy_column( $content,$column_name,$term_id ) {
		
		if( !isset($this->items[$term_id])  ){
			
			$this->items[$term_id] = get_term_meta($term_id);
			
			if( isset($this->items[$term_id][ $this->_base . 'main_language' ][0]) ){
				
				$this->items[$term_id][ $this->_base . 'main_language' ] = $this->items[$term_id][ $this->_base . 'main_language'][0];
			}
			else{
				
				$this->items[$term_id][ $this->_base . 'main_language'] = '';
			}
		}
		
		switch ($column_name) {
			
			case 'language':
			
				if( !empty($this->items[$term_id][ $this->_base . 'main_language']) ){
					
					$url = add_query_arg( array(
						
						'lang' => $this->items[$term_id][ $this->_base . 'main_language'],
					
					), $this->get_current_url() );
					
					$content =  '<a href="' . $url .'">';	
					
						$content .= strtoupper( $this->items[$term_id][ $this->_base . 'main_language'] );
					
					$content .=  '</a>';
				}
				else{
					
					$content =  '';
				}
				
			break;
		}
		
		return $content;		
	}
	
	public function add_language_switcher_taxonomy_field($term){
		
	   // Check for existing taxonomy meta for the term you're editing  
		
		?>  
		  
		<tr class="form-field">  
			<th scope="row" valign="top">  
				<label for="presenter_id"><?php _e('Languages'); ?></label>  
			</th>  
			<td>  
				<?php 
				
				echo $this->admin->display_field( array(
				
					'type'				=> get_taxonomy( $term->taxonomy )->public ? 'language_switcher_with_url' : 'language_switcher_without_url',
					'id'				=> 'language_switcher',
					'name'				=> 'language_switcher',
					'placeholder'		=> 'add new languages',
					'data'				=> get_term_meta($term->term_id,'language_switcher', true),
					'description'		=> '',
					
				), false );				 
				?>
			</td>  
		</tr>  
		  
		<?php 		
	}

	public function get_labels(){
		
		//post types
		
		if( $post_types = $this->get_post_types() ){
			
			foreach( $post_types as $post_type ){
				
				if( !isset($this->labels['post_types'][$post_type]) ){
				
					$obj = get_post_type_object( $post_type );

					$this->labels['post_types'][$post_type] = $obj->labels->singular_name;
				}
			}
			
			//taxonomies
			
			$taxonomies = $this->get_taxonomies();
			
			foreach( $taxonomies as $taxonomy ){
				
				if( !isset($this->labels['taxonomies'][$taxonomy]) ){
				
					$obj = get_taxonomy( $taxonomy );
			
					$this->labels['taxonomies'][$taxonomy] = $obj->labels->singular_name;
				}
			}
		}
		
		return $this->labels;		
	}
	
	public function get_post_types(){

		if( $post_types = get_post_types('','')){
			
			foreach( $post_types as $slug => $post_type){

				if( $post_type->show_ui === true ){
					
					$this->post_types[$slug] = $post_type->name;
				}
			}
		}

		return $this->post_types;
	}
	
	public function get_taxonomies(){
		
		if(	$taxonomies = get_taxonomies('', '') ){
		
			foreach( $taxonomies as $slug => $taxonomy){
				
				if( $taxonomy->show_ui === true ){
				
					$this->taxonomies[$slug] = $taxonomy->name;
				}
			}
		}
		
		return $this->taxonomies;
	}
	
	public function get_locales(){
		
		if(	is_null( $this->locales ) ){
		
			require_once( $this->lang . '/languages.php' );
		
			$this->locales = $locales;
		}
		
		return $this->locales;
	}
	
	public function get_locale_by_code($code){
		
		$locales = $this->get_locales();
		
		foreach( $locales as $locale ){
			
			if( $locale['code'] == $code ){
				
				return $locale['locale'];
			}
		}
		
		return false;
	}
	
	public function get_code_by_locale($locale){
		
		$locales = $this->get_locales();
		
		if( isset($locales[$locale]) ){
			
			return $locales[$locale]['code'];
		}
		
		return false;
	} 
	
	public function get_language_labels(){
		
		if(	is_null( $this->languages ) ){
			
			$locales = $this->get_locales();
			
			foreach( $locales as $locale => $data ){
				
				$code 	= $data['code'];
				
				$arr 	= array_reverse(explode('-',$code));
				
				$flag 	= '<span class="flag flag-' . implode(' flag-',$arr) . '"></span>';
				
				$loc	= strtoupper($arr[0]);
				
				$name 	= ucfirst(strtok($data['name'], '('));
				
				$native = ucfirst($data['native']);
				
				$this->languages[$code]['iso'] 		= $flag . '<span class="lsw-iso">' . $loc . '</span>';
				
				$this->languages[$code]['full'] 	= $flag . '<span class="lsw-iso">' . $loc . '</span> <span class="lsw-language">' . $name . '</span> <i class="lsw-native">' . $native . '</i>';
				
				$this->languages[$code]['language'] = $flag . '<span class="lsw-language">' . $name . '</span>';
				
				$this->languages[$code]['native'] 	= $flag . '<span class="lsw-language">' . $native . '</span>';
			}
			
			unset($languages);
		}
		
		return $this->languages;
	}
	
	public function get_active_taxonomies(){
		
		if( is_null($this->active_taxonomies) ){
			
			$valid = get_option( $this->_base . 'language_taxonomies');
			
			if( !empty($valid) ){
			
				foreach( $valid as  $e => $taxonomy ){
					
					if( !$this->is_valid_taxonomy($taxonomy) ){
						
						unset( $valid[$e] );
					}
				}
			}
			
			$this->active_taxonomies = $valid;
		}
		
		return $this->active_taxonomies;
	}
	
	public function get_active_post_types(){
		
		if( is_null($this->active_post_types) ){

			$valid = get_option( $this->_base . 'language_post_types');
			
			if( !empty($valid) && is_array($valid) ){
				
				foreach( $valid as $e => $post_type ){
					
					if( !$this->is_valid_post_type($post_type) ){
						
						unset( $valid[$e] );
					}
				}
			}
			
			$this->active_post_types = $valid;
		}
		
		return $this->active_post_types;
	}
	
	public function get_active_languages(){
		
		if( is_null($this->active_languages) ){
			
			$default = $this->get_default_language();
			
			if( $this->active_languages = get_option( $this->_base . 'active_languages') ){
			
				if( !in_array( $default, $this->active_languages) ){
					
					$this->active_languages[] = $default;
				}
			}
			else{
				
				$this->active_languages = array($default);
			}
		}
		
		return $this->active_languages;
	}
	
	public function is_valid_taxonomy($taxonomy){
		
		if( is_object($this->everywhere) && method_exists( $this->everywhere, 'get_valid_taxonomies') ){
			
			$valid = $this->everywhere->get_valid_taxonomies();
		}
		else{
			
			$valid = array('category','post_tag','link_category');
		}
		
		if( in_array($taxonomy,$valid) ){
			
			return true;
		}
		
		return false;
	}
	
	public function is_valid_post_type($post_type){
		
		if( is_object($this->everywhere) && method_exists( $this->everywhere, 'get_valid_post_types') ){
			
			$valid = $this->everywhere->get_valid_post_types();
		}
		else{
			
			$valid = array('post','page','attachment','revision');
		}

		if( in_array($post_type,$valid) ){
			
			return true;
		}
		
		return false;
	}
	
	public function is_valid_object($object,$value){
		
		if( $object == 'taxonomy' || $object == 'taxonomies' ){
			
			return $this->is_valid_taxonomy($value);
		}
		elseif( $object == 'post_type' || $object == 'post_types' ){
			
			return $this->is_valid_post_type($value);
		}
		
		return false;
	}	
	
	public function add_post_type_language_switcher_with_url($fields){
		
		$fields[]=array(
		
			"metabox" =>
				array('name'=> "language_switcher"),
				'type'				=> 'language_switcher_with_url',
				'id'				=> 'language_switcher',
				'data'				=> isset($_REQUEST['post']) ? $this->get_post_language( sanitize_text_field($_REQUEST['post']) ) : '',
				'description'		=> '',
		);
		
		return $fields;	
	}
	
	public function add_post_type_language_switcher_without_url($fields){
		
		$fields[]=array(
		
			"metabox" =>
				array('name'=> "language_switcher"),
				'type'				=> 'language_switcher_without_url',
				'id'				=> 'language_switcher',
				'data'				=> $this->get_post_language( sanitize_text_field($_REQUEST['post']) ),
				'description'		=> '',
		);
		
		return $fields;	
	}	
	
	public function save_language_taxonomy($term_id){
		
		if( isset($_REQUEST['language_switcher']) && is_array($_REQUEST['language_switcher']) ){
			
			$language_switcher = $this->sanitize_language_switcher($_REQUEST['language_switcher']);
			
			update_term_meta($term_id,'language_switcher',$language_switcher);

			if( !empty($language_switcher['main']) ){
			
				update_term_meta($term_id,$this->_base . 'main_language',$language_switcher['main']);
			}
		}
		
		do_action('lsw_taxonomy_edited',$term_id);
	}
	
	public function save_language_post_type( $post_id ) {
		
		if( isset($_REQUEST['language_switcher']) && is_array($_REQUEST['language_switcher']) ){
			
			$language_switcher = $this->sanitize_language_switcher($_REQUEST['language_switcher']);

			update_post_meta($post_id,$this->_base . 'language_switcher',$language_switcher);
			
			if( !empty($language_switcher['main']) ){

				update_post_meta($post_id,$this->_base . 'main_language',$language_switcher['main']);
			}
			
		}
		
		do_action('lsw_post_type_edited',$post_id);
	}
	
	public function sanitize_language_switcher($array) {
		
		foreach( $array as $key => &$value ) {
			
			if( is_array($value) ){
				
				$value = $this->sanitize_language_switcher($value);
			}
			elseif( $key == 'main' ){
				
				$value = sanitize_text_field($value);
			}
			else{
				
				$value = sanitize_url($value,['http','https']);
			}
		}

		return $array;
	}
	
	public function get_current_url(){
		
		global $wp;

		return home_url(add_query_arg( $_SERVER['QUERY_STRING'], '', $wp->request));
	}
	
	public function get_language_urls($languages){
		
		$urls = array();
		
		if( $active_languages = $this->get_active_languages() ){
			
			$default_urls = get_option( $this->_base . 'default_language_urls' );
			
			$language = $this->get_current_language();
			
			foreach($active_languages as $iso){
				
				if( !empty($languages[$iso]) ){
					
					$urls[$iso]['language'] = $languages[$iso]['full'];
					
					if( !empty($language['urls'][$iso]) ){
						
						$urls[$iso]['url'] = $language['urls'][$iso];
					}
					elseif( $language['main'] != $iso ){
						
						if( !empty($default_urls[$iso]) ){
						
							$urls[$iso]['url'] = $default_urls[$iso];
						}
						else{
							
							$urls[$iso]['url'] = add_query_arg( array('lang' => $iso), $this->get_current_url() );
						}
					}
					else{
						
						$urls[$iso]['url'] = $this->get_current_url();
					}
				}
			}
		}
		
		return $urls;
	}
		
	public function get_language_switcher_shortcode( $atts ){
		
		$display 	= ( !empty($atts['display']) ? $atts['display'] : 'button' );
		$show 		= ( !empty($atts['show']) ? $atts['show'] : 'title_option' );
		$icon 		= ( !empty($atts['icon']) ? $atts['icon'] : '' );
		
		return $this->get_language_switcher( $display, $show, $icon );
	}
		
	public function get_language_switcher( $display = 'button', $show = 'title_option', $icon = '' ){
		
		// get languages
		
		$languages = $this->get_language_labels();
		
		// get language urls
		
		$urls = $this->get_language_urls($languages);
		
		// current language
		
		$language = $this->get_current_language();

		// output dropdown
		
		$title = '';
		
		if( !empty($show) && $show!='none' ){
		
			$title = $this->get_switcher_title($languages);
			
			if( $show!='title_option' ){
				
				foreach( $urls as $iso => $data ){
					
					if( $language['main'] == $iso ){
						
						if( !empty($languages[$iso][$show]) ){
						
							$title = $languages[$iso][$show];
						}
						
						break;
					}
				}
			}
			
			$title = $title;
		}
		
		$switcher = '';
		
		$id = uniqid();
		
		if( $display == 'list' ){
			
			$switcher .= '<div id="' . esc_attr('jq-list-'.$id) . '" class="jq-list">';
				
				$switcher .= '<ul class="jq-list-menu">';
				
					foreach( $urls as $iso => $data ){
						
						$switcher .= '<li'.( $language['main'] == $iso ? ' class="lsw-active"' : '' ).'>';
							
							$switcher .= '<a onclick="setLang(\''.$iso.'\');" href="'.esc_url($data['url']).'">'.$data['language'].'</a>';
							
						$switcher .= '</li>';
					}
					
				$switcher .= '</ul>';
			
			$switcher .= '</div>';				
		}
		else{
			
			if( !empty($title) ){
				
				if( !empty($icon) ){
					
					$switcher .='<a class="language-switcher-icon" href="#" data-jq-dropdown="#' . esc_attr('jq-dropdown-'.$id) . '"><img src="'.esc_url($icon).'" />'.$title.'</a>';
				}
				else{
					
					$switcher .='<a class="language-switcher-btn" href="#" data-jq-dropdown="#' . esc_attr('jq-dropdown-'.$id) . '">'.$title.'</a>';
				}
			}
			elseif( !empty($icon) ){
			
				$switcher .='<a class="language-switcher-icon" href="#" data-jq-dropdown="#' . esc_attr('jq-dropdown-'.$id) . '"><img src="'.esc_url($icon).'" /></a>';
			}
			else{
				
				$switcher .='<a class="language-switcher-btn" href="#" data-jq-dropdown="#' . esc_attr('jq-dropdown-'.$id) . '">'.$title.'</a>';
			}
			
			// add switcher for inclusion in footer
			
			$html = '<div id="' . esc_attr('jq-dropdown-'.$id) . '" class="jq-dropdown jq-dropdown-tip">';
				
				$html .= '<ul class="jq-dropdown-menu">';
				
					foreach( $urls as $iso => $data ){

						$html .= '<li'.( $language['main'] == $iso ? ' class="lsw-active"' : '' ).'>';
							
							$html .= '<a onclick="setLang(\''.$iso.'\');" href="' . esc_url($data['url']) . '">' . $data['language'] . '</a>';
						
						$html .= '</li>';
					}
					
				$html .= '</ul>';
			
			$html .= '</div>';

			$this->switchers[$id] = $html;
		}
		
		return $switcher;
	}
	
	public function get_language_switcher_menu( $items, $args ) {
		
		if( $menus = get_option( $this->_base . 'add_switcher_to_menus', false )){
			
			foreach( $menus as $menu ){
				
				if( $args->menu->slug == $menu ) {
					
					// get languages
					
					$languages = $this->get_language_labels();
				
					// get language urls
					
					if( $urls = $this->get_language_urls($languages) ){
						
						$link = array (
							'title'            	=> $this->get_switcher_title($languages),
							'menu_item_parent' 	=> '0',
							'ID'              	=> 'languages',
							'db_id'            	=> 'languages',
							'object'          	=> 'custom',
							'type'          	=> 'custom',
							'url'              	=> '#language',
							'classes'           => array('menu-item','menu-item-type-custom','menu-item-object-custom','menu-item-has-children'),
							'target'           	=> '',
							'xfn'           	=> '',
							'current'         	=> false,
						);
						
						$items[] = (object) $link;
						
						foreach( $urls as $iso => $data ){

							$link = array (
								'title'            	=> $data['language'],
								'menu_item_parent' 	=> 'languages',
								'ID'               	=> 'lang-'.$iso,
								'db_id'            	=> 'lang-'.$iso,
								'object'          	=> 'custom',
								'type'          	=> 'custom',
								'url'              	=> $data['url'],
								'type'          	=> 'custom',
								'classes'           => array('menu-item','menu-item-type-custom','menu-item-object-custom'),
								'target'           	=> '',
								'xfn'           	=> '',
								'current'         	=> false,
							);
						
							$items[] = (object) $link;
						}
					}
				}
			}
		}

		return $items;
	}	
	
	public function get_switcher_title($languages){
		
		$title = __('Language');
		
		$option = get_option( $this->_base . 'switcher_title', 'selected_lang' );
		
		if( $option == 'selected_lang' || $option == 'selected_iso' || $option == 'selected_nat' ){
			
			$language = $this->get_current_language();
			
			$main_lang = $language['main'];
			
			if( !empty($languages[$main_lang]) ){
				
				if( $option == 'selected_iso' ){
					
					$title = $languages[$main_lang]['iso'];
				}
				elseif( $option == 'selected_nat' ){
					
					$title = $languages[$main_lang]['native'];
				}
				else{
					
					$title = $languages[$main_lang]['full'];
				}
			}
		}
		elseif( $option == 'custom_title' ){
			 
			if( $custom_title = get_option( $this->_base . 'custom_title' ) ){
				
				$title = __($custom_title,'language-switcher');
			}
		}
		
		return ucfirst($title);
	}
	
	public function add_switchers(){
		
		if( !empty($this->switchers) ){
			
			foreach( $this->switchers as $id => $switcher ){
				
				echo wp_kses_normalize_entities($switcher);
			}
		}
	}
	
	public function add_hreflang_in_head(){
		
		// get languages
		
		$languages = $this->get_language_labels();
		
		// get language urls
		
		if( $urls = $this->get_language_urls($languages) ){
			
			echo PHP_EOL;
			
			foreach( $urls as $iso => $data ){
				
				echo '<link rel="alternate" href="' . esc_url($data['url']) . '" hreflang="' . esc_attr($iso) . '" />' . PHP_EOL;
			}
		}
	}
	
	public function filter_export_term_meta($meta,$term,$site){
		
		if( $active_taxonomies = $this->get_active_taxonomies() ){
			
			if( in_array($term->taxonomy,$active_taxonomies) ){
				
				$default_lang = $this->get_default_language(true);
				
				if( empty($meta['language_switcher']['urls'][$default_lang]) ){
					
					$meta['language_switcher'] = $this->get_term_language($term);
				}
			}
		}
		
		return $meta;
	}

	public function filter_export_post_meta($meta,$post,$site){
		
		if( $post_types = $this->get_active_post_types() ){
			
			if( in_array($post->post_type,$post_types) ){
				
				$default_lang = $this->get_default_language(true);
				
				if( empty($meta['lsw_language_switcher']['urls'][$default_lang]) ){
					
					$meta['lsw_language_switcher'] = $this->get_post_language($post->ID);
				}
			}
		}
		
		return $meta;
	}
	
	public function filter_import_post_meta($meta,$data,$post_id,$post_type,$site){
		
		if( $post_types = $this->get_active_post_types() ){
			
			if( in_array($post_type,$post_types) && !empty($data['lsw_language_switcher']['urls'])) {

				$default_lang = $this->get_default_language(true);
				
				if( $language = $this->get_post_language($post_id) ){
					
					foreach( $data['lsw_language_switcher']['urls'] as $lang => $url ){
						
						if( !empty($url) && $lang != $default_lang ){
							
							$language['urls'][$lang] = $url;
						}
					}
					
					$meta['lsw_main_language'] = $default_lang;
					
					$meta['lsw_language_switcher'] = $language;
				}
			}
		}
		
		return $meta;
	}
	
	public function filter_import_term_meta($meta,$data,$term_id,$taxonomy,$site){
		
		if( $active_taxonomies = $this->get_active_taxonomies() ){
			
			if( in_array($taxonomy,$active_taxonomies) && !empty($data['language_switcher']['urls'])) {
				
				$default_lang = $this->get_default_language(true);
				
				if( $language = $this->get_term_language($term_id) ){
					
					foreach( $data['language_switcher']['urls'] as $lang => $url ){
						
						if( !empty($url) && $lang != $default_lang ){
							
							$language['urls'][$lang] = $url;
						}
					}
					
					$meta['lsw_main_language'] = $default_lang;
					
					$meta['language_switcher'] = $language;
				}
			}
		}
		
		return $meta;
	}
	
	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new Language_Switcher_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new Language_Switcher_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}
	
	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend-1.0.1.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );		

		wp_register_style( $this->_token . '-dropdown', esc_url( $this->assets_url ) . 'css/jquery.dropdown.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-dropdown' );	
		
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		//wp_register_script($this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend.js', array(), $this->_version );
		//wp_enqueue_script($this->_token . '-frontend' );

		wp_register_script($this->_token . '-switcher', '', array() );
		wp_enqueue_script($this->_token . '-switcher' );
		wp_add_inline_script($this->_token . '-switcher', $this->get_switcher_script() );
		
		wp_register_script($this->_token . '-dropdown', esc_url( $this->assets_url ) . 'js/jquery.dropdown.min.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script($this->_token . '-dropdown' );	
		
	} // End enqueue_scripts ()
	
	public function get_switcher_script(){
		
		$script = '';
		
		$script .= 'function setLang(lang){';

			$script .= 'document.cookie = "' . $this->_prefix . 'm=" + lang + ";path=/;SameSite=Strict";';
			
		$script .= '}';
		
		$script .= 'var links = document.querySelectorAll("link[hreflang]");';

		$script .= 'for (var i = 0; i < links.length; i++) {';
				
			$script .= 'var lang = links[i].hreflang;';
			
			$script .= 'var menus = document.querySelectorAll("a.menu-item-lang-" + lang);';
			  
			$script .= 'for (var j = 0; j < menus.length; j++) {';
				
				$script .= 'menus[j].addEventListener("click", function(event) {';

					$script .= 'setLang(lang);';
				
				$script .= '});';
			
			$script .= '}';
		
		$script .= '}';
		
		return $script;
	}

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), '1.0.4' );
		wp_enqueue_script( $this->_token . '-admin' );	

	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		
		load_plugin_textdomain( 'language-switcher', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		
	    $domain = 'language-switcher';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()
	
	/**
	 * Main Language_Switcher Instance
	 *
	 * Ensures only one instance of Language_Switcher is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Language_Switcher()
	 * @return Main Language_Switcher instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		
		if ( is_null( self::$_instance ) ) {
			
			self::$_instance = new self( $file, $version );
		}
		
		return self::$_instance;
	} // End instance ()
	
	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()
}
