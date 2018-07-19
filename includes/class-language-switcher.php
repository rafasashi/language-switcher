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
	
	public $_dev = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $filesystem = null;
	public $notices = null;
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
	public $labels;
	public $post_types;
	public $taxonomies;
	public $language;
	public $languages;
	public $active_post_types;
	public $active_taxonomies;
	public $active_languages;
	public $everywhere;
	public $switchers = array();
	 
	public function __construct ( $file = '', $version = '1.0.0' ) {
	
		$this->_version = $version;
		$this->_token 	= 'language-switcher';
		$this->_base 	= 'lsw_';
		
		// Load plugin environment variables
		
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->lang   		= trailingslashit( $this->dir ) . 'lang';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

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
		
		$locale = apply_filters('plugin_locale', get_locale(), 'language-switcher');
		load_textdomain('language_switcher', WP_PLUGIN_DIR . "/".plugin_basename(dirname(__FILE__)).'/lang/language_switcher-'.$locale.'.mo');
		load_plugin_textdomain('language_switcher', false, dirname(plugin_basename(__FILE__)).'/lang/');
		
		if(is_admin()){
			
			add_action('admin_init', array($this, 'init_backend'));
		}
		else{
				
			add_action('init', array($this, 'init_language'));
		}
		
		// shorcodes
		
		add_shortcode( 'language-switcher', array($this,'get_language_switcher') );

		//widgets
		
		add_action( 'widgets_init', array($this,'init_widgets'));		
		
	} // End __construct ()
    
	public function init_language(){
		
		//get current language
		
		add_filter('wp', array($this, 'get_current_language'));
		
		//filter languages
		
		add_filter('pre_get_posts', array( $this, 'query_language_posts') );

		add_filter('get_terms_args', array( $this, 'query_language_taxonomies'), 10, 2 );
		 
		add_filter('pre_get_comments', array( $this, 'query_language_comments') );		
		
		//append urls
		
		add_filter('month_link', array( $this, 'get_month_link'), 10, 3 );
		
		//filter menus
		
		add_filter('wp_get_nav_menu_items', array( $this, 'filter_language_menus'), 10, 2 );
	
		//add switchers

		add_action( 'wp_footer', array( $this, 'add_switchers'), 100);
	}
	
	public function init_widgets() {
		
		$widget = new Language_Switcher_Widget( $this );
		
		register_widget($widget);
	}
	
	public function get_current_language(){
		
		if( empty($this->language) ){
		
			$default_lang = substr( get_bloginfo ( 'language' ), 0, 2 );
			
			$default_urls = get_option( $this->_base . 'default_language_urls' );

			if( is_singular() ){

				$this->language = get_post_meta( get_queried_object_id(), 'language_switcher' ,true );
				
				if( empty($this->language['main']) ){
					
					$this->language['main'] = $default_lang;
				}
			}
			elseif( is_category() || is_tag() || is_tax() ){
				
				$queried = get_queried_object();
				$term_id = $queried->term_id;
				
				$this->language = get_term_meta( $term_id, 'language_switcher' ,true );
			
				if( empty($this->language['main']) ){
					
					$this->language['main'] = $default_lang;
				}
			}
			elseif( !empty($_REQUEST['lang']) ){
				
				$this->language = array(
					'urls' => $default_urls,
					'main' => sanitize_title($_REQUEST['lang']),
				);
				
				if( is_archive() ){
				
					foreach( $this->language['urls'] as $iso => $url ){
						
						if( $iso != $default_lang ){
							
							$this->language['urls'][$iso] = add_query_arg( array('lang' => $iso), home_url( $_SERVER['REQUEST_URI'] ) );
						}
						else{
							
							$this->language['urls'][$iso] = remove_query_arg( array('lang'), home_url( $_SERVER['REQUEST_URI'] ) );
						}
					}
				}
			}
			else{
				
				$this->language = array(
					'urls' => $default_urls,
					'main' => $default_lang,
				);
			}

			if( !empty($this->language['main']) ){
				
				// get main language
				
				$main_lang = $this->language['main'];
			
				//set default language
				
				$this->language['default'] = $default_lang;
				
				//get current url
				
				$current_url = home_url( $_SERVER['REQUEST_URI'] );
				
				//normalize urls
				
				$proto = ( is_ssl() ? 'https://' : 'http://' );
				
				if( !empty($this->language['urls']) ){
				
					foreach( $this->language['urls'] as $iso => $url ){
						
						if( $url[0] == '/' ){
							
							$this->language['urls'][$iso] = home_url( $url );
						}
						elseif( !empty($url) ){
							
							if( strpos($url,'.') === FALSE ){
								
								$this->language['urls'][$iso] = $current_url . '/' . $url;
							}
							elseif( !preg_match("~^(?:f|ht)tps?://~i", $url) ){
								
								$this->language['urls'][$iso] = $proto . $url;
							}
						}
					}
				}
				
				//set cookies & switch language
				
				if( !isset($_COOKIE[$this->_base . 'main_language']) || $_COOKIE[$this->_base . 'main_language'] != $this->language['main'] || !isset($_COOKIE[$this->_base . 'default_language']) || $_COOKIE[$this->_base . 'default_language'] != $this->language['default'] ) {
					
					//set cookies
					
					setcookie($this->_base . 'main_language', $this->language['main'], strtotime('+1 day'));
					setcookie($this->_base . 'default_language', $this->language['default'], strtotime('+1 day'));

					// redirect language url
					
					if( $default_lang != $main_lang && !empty($this->language['urls'][$main_lang]) && $current_url != $this->language['urls'][$main_lang] ){
						
						wp_redirect( $this->language['urls'][$main_lang] );
						exit;						
					}						
					else{
						
						wp_redirect( $current_url );
						exit;
					}
				}
				elseif( $default_lang != $main_lang && !empty($this->language['urls'][$main_lang]) && $current_url != $this->language['urls'][$main_lang] ){
						
					// redirect language url
						
					wp_redirect( $this->language['urls'][$main_lang] );
					exit;						
				}			
				else{
					
					// switch locale

					switch_to_locale( $this->language['main'] . '_' . strtoupper($this->language['main']) );
				}
			}
		}
	}
	
	public function query_language_posts( $query ){
		
		$language = '';
		
		$default_lang = substr( get_bloginfo ( 'language' ), 0, 2 );
		
		if( $query->is_main_query() ){
			
			if( $query->is_category() || $query->is_tag() || $query->is_tax() ){
				
				$queried 	= get_queried_object();
				
				if( !empty($queried->term_id) ){
					
					$language 	= get_term_meta( $queried->term_id, 'language_switcher' ,true );
			
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
		elseif( !empty($_COOKIE[$this->_base . 'main_language']) ){
			
			if( !isset($query->query['post_type']) || $query->query['post_type'] != 'nav_menu_item' ){
			
				$language['main'] 		= $_COOKIE[$this->_base . 'main_language'];
				$language['default'] 	= $_COOKIE[$this->_base . 'default_language'];
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

		return $query;
	}
	
	public function query_language_taxonomies( $args, $taxonomies ){

		$language = '';
		
		if( !empty($_COOKIE[$this->_base . 'main_language']) ){
			
			$language['main'] = $_COOKIE[$this->_base . 'main_language'];
			$language['default'] = $_COOKIE[$this->_base . 'default_language'];
		
			$args['meta_key'] 	= $this->_base . 'main_language';
			$args['meta_value'] = $language['main'];
		}

		return $args;
	}
	
	public function query_language_comments( $query ){
		
		$language = '';
		
		if( !empty($_COOKIE[$this->_base . 'main_language']) ){
			
			$language['main'] = $_COOKIE[$this->_base . 'main_language'];
			$language['default'] = $_COOKIE[$this->_base . 'default_language'];
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
		
		if( !empty($this->language['main']) ){
			
			if( $this->language['main'] != $this->language['default'] ){
			
				$monthlink = add_query_arg( 'lang', $this->language['main'], $monthlink );
			}
		}
		
		return $monthlink;
	}
	
	public function filter_language_menus( $menu, $args ){

		foreach( $menu as $i => $item ){
			
			if( $item->type == 'post_type' || $item->type == 'taxonomy' ){
			
				$language = array();
				
				if( $item->type == 'post_type' ){
					
					$language = get_post_meta( $item->object_id, 'language_switcher' ,true );
				}
				elseif( $item->type == 'taxonomy' ){
					
					$language = get_term_meta( $item->object_id, 'language_switcher' ,true );
				}
				
				if( empty($language['main']) ){
					
					$language['main'] = $this->language['default'];
				}
				
				if( $language['main'] != $this->language['main'] ){
					
					unset($menu[$i]);
				}
			}
			elseif( $this->language['main'] != $this->language['default'] ){
				
				$item->url = add_query_arg( 'lang', $this->language['main'], $item->url);
			}
		}
		
		//echo'<pre>';var_dump($menu);exit;
		
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
	
		if( in_array( basename($_SERVER['SCRIPT_FILENAME']), array('post.php','edit.php') ) ){

			//add language in post types
			
			foreach( $this->get_active_post_types() as $post_type ){
				
				add_action( 'add_meta_boxes', function(){
					
					foreach( $this->get_active_post_types() as $post_type ){
						
						$this->admin->add_meta_box (
						
							'language_switcher',
							__( 'Languages', 'language-switcher' ), 
							array($post_type),
							'side'
						);
					}
				});
				
				add_filter( $post_type . '_custom_fields', array( $this, 'add_language_switcher_post_type_field' ));
			
				add_action( 'save_post_' . $post_type, array( $this, 'save_language_post_type' ), 10, 3 );
			
				add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'set_language_post_type_columns' ) );
			
				add_action( 'manage_'.$post_type.'_posts_custom_column' , array( $this, 'get_language_post_type_column' ), 10, 2 );
			}
			
			add_filter( 'pre_get_posts', array( $this, 'query_admin_language_post_type') );
		}		
		elseif( in_array( basename($_SERVER['SCRIPT_FILENAME']), array('term.php','edit-tags.php') ) ){
		
			//add language in taxonomies
			
			foreach( $this->get_active_taxonomies() as $taxonomy ){
			
				add_action( $taxonomy . '_edit_form_fields', array($this, 'add_language_switcher_taxonomy_field'), 10, 2 );
			
				add_action( 'edited_' . $taxonomy, array($this, 'save_language_taxonomy'), 10, 2 );
			
				add_filter( 'manage_edit-'.$taxonomy.'_columns' , array( $this, 'set_language_taxonomy_columns' ) );
				
				add_action( 'manage_'.$taxonomy.'_custom_column', array( $this, 'get_language_taxonomy_column' ), 10,3 );			
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
					
					global $wp;
				
					$url = add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
									
					$url = add_query_arg( 'lang', $this->items[$post_id][ $this->_base . 'main_language'], $url);
					
					echo '<a href="' . $url .'">';
					
						echo strtoupper($this->items[$post_id][ $this->_base . 'main_language']);
				
					echo '</a>';
				}
				
			break;
		}
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
					), home_url( $_SERVER['REQUEST_URI'] ) );
					
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
				
					'type'				=> 'language_switcher',
					'id'				=> 'language_switcher',
					'name'				=> 'language_switcher',
					'placeholder'		=> 'add new languages',
					'data'				=> get_term_meta( $term->term_id, 'language_switcher', true),
					//'default'			=> get_term_link($term,$term->taxonomy),
					'description'		=> '',
					
				), false );				 
				?>
			</td>  
		</tr>  
		  
		<?php 		
	}

	public function get_labels(){
		
		if(	is_null( $this->labels ) ){
			
			//post types
			
			$post_types = $this->get_post_types();
			
			foreach( $post_types as $post_type ){
				
				$obj = get_post_type_object( $post_type );

				$this->labels['post_types'][$post_type] = $obj->labels->singular_name;
			}
			
			//taxonomies
			
			$taxonomies = $this->get_taxonomies();
			
			foreach( $taxonomies as $taxonomy ){
				
				$obj = get_taxonomy( $taxonomy );
			
				$this->labels['taxonomies'][$taxonomy] = $obj->labels->singular_name;
			}
		}
		
		return $this->labels;		
	}
	
	public function get_post_types(){

		if(	is_null( $this->post_types ) ){
			
			$post_types = get_post_types('', '');
			
			$this->post_types = array();
			
			foreach( $post_types as $post_type){

				if( $post_type->show_ui === true ){
					
					$this->post_types[] = $post_type->name;
				}
			}
		}
		
		return $this->post_types;
	}
	
	public function get_taxonomies(){
		
		if(	is_null( $this->taxonomies ) ){
		
			$taxonomies = get_taxonomies('', '');

			$this->taxonomies = array();
			
			foreach( $taxonomies as $taxonomy){
				
				if( $taxonomy->show_ui === true ){
				
					$this->taxonomies[] = $taxonomy->name;
				}
			}
		}
		
		return $this->taxonomies;
	}
	
	public function get_languages(){
		
		if(	is_null( $this->languages ) ){
			
			require_once( $this->lang . '/languages.php' );
			
			foreach( $languages as $iso => $data ){
				
				if( $data['type'] == 'living' && $data['scope'] == 'individual' ){
					
					$this->languages[$data['iso_639_1']] = '<span style="font-weight:initial;background: #888;color: #fff;padding: 2px 4px;border-radius: 3px;">' . ucfirst($data['iso_639_1']) . '</span> ' . ucfirst( $data['name'] ) . ' <i style="font-size:60%;">(' . $data['native'] . ')</i>';
				} 
			}
			
			unset($languages);
		}
		
		return $this->languages;
	}
	
	public function get_active_taxonomies(){
		
		if( is_null($this->active_taxonomies) ){
			
			$valid = get_option( $this->_base . 'language_taxonomies');
			
			foreach( $valid as  $e => $taxonomy ){
				
				if( !$this->is_valid_taxonomy($taxonomy) ){
					
					unset( $valid[$e] );
				}
			}
			
			$this->active_taxonomies = $valid;
		}
		
		return $this->active_taxonomies;
	}
	
	public function get_active_post_types(){
		
		if( is_null($this->active_post_types) ){

			$valid = get_option( $this->_base . 'language_post_types');

			foreach( $valid as  $e => $post_type ){
				
				if( !$this->is_valid_post_type($post_type) ){
					
					unset( $valid[$e] );
				}
			}
			
			$this->active_post_types = $valid;
		}
		
		return $this->active_post_types;
	}
	
	public function get_active_languages(){
		
		if( is_null($this->active_languages) ){
			
			$default = substr( get_bloginfo ( 'language' ), 0, 2 );
			
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
		
		if( !empty( $this->everywhere->valid_taxonomies ) ){
			
			$valid = $this->everywhere->valid_taxonomies;
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

		if( !empty( $this->everywhere->valid_post_types ) ){
			
			$valid = $this->everywhere->valid_post_types;
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
	
	public function add_language_switcher_post_type_field($fields){
		
		$fields[]=array(
		
			"metabox" =>
				array('name'=> "language_switcher"),
				'type'				=> 'language_switcher',
				'id'				=> 'language_switcher',
				//'default'			=> get_permalink($_REQUEST['post']),
				'description'		=> '',
		);
		
		return $fields;	
	}	
	
	public function save_language_taxonomy($term_id){
		
		if( isset($_REQUEST['language_switcher']) ){
		
			update_term_meta($term_id,'language_switcher',$_REQUEST['language_switcher']);
		}

		if( isset($_REQUEST['language_switcher']['main']) ){
		
			update_term_meta($term_id,$this->_base . 'main_language',$_REQUEST['language_switcher']['main']);
		}	
	}	
	
	public function save_language_post_type( $post_id, $post, $update ) {
		
		if( isset($_REQUEST['language_switcher']['main']) ){

			update_post_meta($post_id,$this->_base . 'main_language',$_REQUEST['language_switcher']['main']);
		}
	}
	
	public function get_language_switcher( $display = 'button' ){
		
		$active_languages = $this->get_active_languages();
		
		$languages = $this->get_languages();
		
		$default_urls = get_option( $this->_base . 'default_language_urls' );
		
		// get language urls
		
		$urls = array();
		
		foreach($active_languages as $iso){
			
			$urls[$iso]['language'] = $languages[$iso];
			
			if( !empty($this->language['urls'][$iso]) ){
				
				$urls[$iso]['url'] = $this->language['urls'][$iso];
			}
			elseif( $this->language['main'] != $iso ){
				
				if( !empty($default_urls[$iso]) ){
				
					$urls[$iso]['url'] = $default_urls[$iso];
				}
				else{
					
					$urls[$iso]['url'] = add_query_arg( array('lang' => $this->language['main']), home_url( $_SERVER['REQUEST_URI'] ) );
				}
			}
			else{
				
				$urls[$iso]['url'] = home_url( $_SERVER['REQUEST_URI'] );
			}
		}

		// output dropdown
		
		$id = uniqid();
		
		$title = __('Language','language-switcher');
		
		foreach( $urls as $iso => $data ){
			
			if( $this->language['main'] == $iso ){
				
				$title = __($data['language'],'language-switcher');
			}
		}
		
		if( $display == 'list' ){
			
			echo '<div id="jq-list-'.$id.'" class="jq-list">';
				
				echo '<ul class="jq-list-menu">';
				
					foreach( $urls as $iso => $data ){

						echo '<li'.( $this->language['main'] == $iso ? ' style="font-weight:bold;"' : '' ).'><a href="'.$data['url'].'">'.$data['language'].'</a></li>';

					}
					
				echo '</ul>';
			
			echo '</div>';				
		}
		else{
			
			echo'<a class="language-switcher-btn" href="#" data-jq-dropdown="#jq-dropdown-'.$id.'">'.$title.'</a>';

			// add switcher for inclusion in footer
			
			$this->switchers[$id] = '<div id="jq-dropdown-'.$id.'" class="jq-dropdown jq-dropdown-tip">';
				
				$this->switchers[$id] .= '<ul class="jq-dropdown-menu">';
				
					foreach( $urls as $iso => $data ){

						$this->switchers[$id] .= '<li'.( $this->language['main'] == $iso ? ' style="font-weight:bold;"' : '' ).'><a href="'.$data['url'].'">'.$data['language'].'</a></li>';
						
						//echo'<li class="jq-dropdown-divider"></li>';
					}
					
				$this->switchers[$id] .= '</ul>';
			
			$this->switchers[$id] .= '</div>';			
		}
	}
	
	public function add_switchers(){
		
		if( !empty($this->switchers) ){
			
			foreach( $this->switchers as $id => $switcher ){
				
				echo $switcher;
			}
		}
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
		
		//wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend-1.0.1.css', array(), $this->_version );
		//wp_enqueue_style( $this->_token . '-frontend' );		

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
		
		//wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend.js', array( 'jquery' ), $this->_version );
		//wp_enqueue_script( $this->_token . '-frontend' );	
		
		wp_register_script( $this->_token . '-dropdown', esc_url( $this->assets_url ) . 'js/jquery.dropdown.min.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-dropdown' );	
		
	} // End enqueue_scripts ()

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
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin.js', array( 'jquery' ), $this->_version );
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

