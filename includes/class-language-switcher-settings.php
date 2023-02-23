<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Language_Switcher_Settings {

	/**
	 * The single instance of Language_Switcher_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		
		$this->parent = $parent;

		$this->base = 'lsw_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init' , array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_setting_page' ) );
		add_action( 'admin_menu' , array( $this, 'add_menu_items' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ) , array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings () {
		
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add Setting SubPage
	 *
	 * add Setting SubPage to wordpress administrator
	 *
	 * @return array validate input fields
	 */
	public function add_setting_page() {

		// language panel
		
		$position = apply_filters( 'lsw_plugins_menu_item_position', '60' );
		
		add_menu_page( 'lsw_plugin_panel', 'Languages', 'nosuchcapability', 'lsw_plugin_panel', NULL, 'dashicons-translation', $position );	
		
		remove_submenu_page( 'lsw_plugin_panel', 'lsw_plugin_panel' );
		
		add_submenu_page( 'lsw_plugin_panel', 'Settings', 'Settings', 'manage_options', 'language-switcher', array( $this, 'settings_page' ) );
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
	
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets () {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		wp_enqueue_style( 'farbtastic' );
    	wp_enqueue_script( 'farbtastic' );

    	// We're including the WP media scripts here because they're needed for the image upload field
    	// If you're not including an image upload then you can leave this function call out
    	wp_enqueue_media();

    	wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
    	wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links
	 * @return array 		Modified links
	 */
	public function add_settings_link ( $links ) {
		
		$settings_link = '<a href="admin.php?page=' . $this->parent->_token . '">' . __( 'Settings', 'language-switcher' ) . '</a>';
  		array_push( $links, $settings_link );
		
		$addon_link = '<a href="admin.php?page=' . $this->parent->_token . '&tab=addons">' . __( 'Addons', 'language-switcher' ) . '</a>';
  		array_push( $links, $addon_link );
		
  		return $links;
	}
	
	public function get_nav_menus(){
		
		$options = array();
		
		if( $menus = wp_get_nav_menus() ){
			
			foreach( $menus as $menu ){
				
				$options[$menu->slug] = $menu->name;
			}
		}
		
		return $options;
	}
	
	public function get_title_options(){
		
		$options = array(

			'selected_lang'	=> 'The currently selected language - Full',
			'selected_iso'	=> 'The currently selected language - ISO',
			'selected_nat'	=> 'The currently selected language - Native',
			'language_title'=> 'The word "Language" translated in the current language',
			'custom_title'	=> $this->parent->admin->display_field( array(
				
				'id' 			=> $this->parent->_base . 'custom_title',
				'label'			=> '',
				'description'	=> '',
				'type'			=> 'text',
				'placeholder'	=> 'Custom Title',
				
			),false,false),
		);
		
		return $options;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['languages'] = array(
			'title'					=> __( 'Languages', 'language-switcher' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'active_languages',
					'label'			=> __( 'Languages' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'language_checkbox_multi',
					'data'			=> $this->parent->get_active_languages(),					
					'options'		=> $this->parent->get_language_labels(),
					'default'		=> '',
				),		
			) 
		);	
	
		$settings['settings'] = array(
			'title'					=> __( 'Settings', 'language-switcher' ),
			'description'			=> '',
			'fields'				=> array(
				array(
					'id' 			=> 'language_post_types',
					'label'			=> __( 'Post Types' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'object_checkbox_multi',
					'options'		=> $this->parent->get_post_types(),
					'object'		=> 'post_types',
					'default'		=> '',
				),		
				array(
					'id' 			=> 'language_taxonomies',
					'label'			=> __( 'Taxonomies' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'object_checkbox_multi',
					'options'		=> $this->parent->get_taxonomies(),
					'object'		=> 'taxonomies',
					'default'		=> '',
				),
				array(
					'id' 			=> 'default_language_urls',
					'label'			=> __( 'Default URLs' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'default_language_urls',
					'default'		=> '',
				),
				array(
					'id' 			=> 'add_switcher_to_menus',
					'label'			=> __( 'Show in Navigation Menu' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox_multi',
					'options'		=> $this->get_nav_menus(),
					'default'		=> '',
				),
				array(
					'id' 			=> 'switcher_title',
					'label'			=> __( 'Switcher Title' , 'language-switcher' ),
					'description'	=> 'Use the CSS classes <code>.lsw-iso</code> <code>.lsw-language</code> <code>.lsw-native</code> for additional customizations or <a href="https://code.recuweb.com/get/language-switcher/" target="_blank">contact us</a>',
					'type'			=> 'radio',
					'options'		=> $this->get_title_options(),
					'default'		=> 'selected_lang',
				),
				array(
				
					'id' 			=> 'custom_title',
					'label'			=> '',
					'description'	=> '',
					'type'			=> 'none',
				),
				array(
					'id' 			=> 'switcher_flags',
					'label'			=> __( 'Switcher Flags' , 'language-switcher' ),
					'description'	=> 'To enable flags copy, paste and customize the above piece of style into Theme > Additional CSS',
					'type'			=> 'code',
					'data'			=> ".flag{display:inline-block;position: relative;width:16px;height:11px;background:url('" . $this->parent->assets_url . "images/flags.png') no-repeat}.flag.flag-gu{background-position:-96px -55px}.flag.flag-mn{background-position:-208px -88px}.flag.flag-va{background-position:-48px -154px}.flag.flag-tibet{background-position:-32px -143px}.flag.flag-fo{background-position:-64px -44px}.flag.flag-th{background-position:-16px -143px}.flag.flag-tr{background-position:-144px -143px}.flag.flag-tl{background-position:-80px -143px}.flag.flag-kz{background-position:-144px -77px}.flag.flag-zm{background-position:-16px -165px}.flag.flag-uz{background-position:-32px -154px}.flag.flag-dk{background-position:-64px -33px}.flag.flag-scotland{background-position:-176px -121px}.flag.flag-gi{background-position:-224px -44px}.flag.flag-gy{background-position:-128px -55px}.flag.flag-bj{background-position:-112px -11px}.flag.flag-fr{background-position:-80px -44px}.flag.flag-mo{background-position:-224px -88px}.flag.flag-ir{background-position:-112px -66px}.flag.flag-io{background-position:-80px -66px}.flag.flag-tm{background-position:-96px -143px}.flag.flag-ch{background-position:-96px -22px}.flag.flag-mt{background-position:-32px -99px}.flag.flag-nl{background-position:-240px -99px}.flag.flag-gp{background-position:-16px -55px}.flag.flag-im{background-position:-48px -66px}.flag.flag-tv{background-position:-176px -143px}.flag.flag-mu{background-position:-48px -99px}.flag.flag-pe{background-position:-96px -110px}.flag.flag-vi{background-position:-112px -154px}.flag.flag-hn{background-position:-176px -55px}.flag.flag-ss{background-position:-128px -132px}.flag.flag-ae{background-position:-16px 0}.flag.flag-td{background-position:-240px -132px}.flag.flag-pw{background-position:0 -121px}.flag.flag-nu{background-position:-32px -110px}.flag.flag-bt{background-position:-208px -11px}.flag.flag-ms{background-position:-16px -99px}.flag.flag-cv{background-position:-240px -22px}.flag.flag-es{background-position:-224px -33px}.flag.flag-mh{background-position:-144px -88px}.flag.flag-la{background-position:-160px -77px}.flag.flag-vn{background-position:-128px -154px}.flag.flag-py{background-position:-16px -121px}.flag.flag-br{background-position:-176px -11px}.flag.flag-ye{background-position:-224px -154px}.flag.flag-ie{background-position:0 -66px}.flag.flag-gh{background-position:-208px -44px}.flag.flag-cg{background-position:-80px -22px}.flag.flag-cu{background-position:-224px -22px}.flag.flag-hu{background-position:-224px -55px}.flag.flag-sg{background-position:-224px -121px}.flag.flag-at{background-position:-176px 0}.flag.flag-lk{background-position:-224px -77px}.flag.flag-vu{background-position:-144px -154px}.flag.flag-bo{background-position:-160px -11px}.flag.flag-jo{background-position:-208px -66px}.flag.flag-er{background-position:-208px -33px}.flag.flag-za{background-position:-256px -154px}.flag.flag-rs{background-position:-80px -121px}.flag.flag-nr{background-position:-16px -110px}.flag.flag-ls{background-position:-256px -77px}.flag.flag-jm{background-position:-192px -66px}.flag.flag-tz{background-position:-208px -143px}.flag.flag-ki{background-position:-16px -77px}.flag.flag-sj{background-position:0 -132px}.flag.flag-cz{background-position:-16px -33px}.flag.flag-pg{background-position:-128px -110px}.flag.flag-lv{background-position:-32px -88px}.flag.flag-do{background-position:-96px -33px}.flag.flag-lu{background-position:-16px -88px}.flag.flag-no{background-position:-256px -99px}.flag.flag-kw{background-position:-112px -77px}.flag.flag-mx{background-position:-96px -99px}.flag.flag-yt{background-position:-240px -154px}.flag.flag-ly{background-position:-48px -88px}.flag.flag-cy{background-position:0 -33px}.flag.flag-ph{background-position:-144px -110px}.flag.flag-my{background-position:-112px -99px}.flag.flag-sm{background-position:-48px -132px}.flag.flag-et{background-position:-240px -33px}.flag.flag-ru{background-position:-96px -121px}.flag.flag-tj{background-position:-48px -143px}.flag.flag-ai{background-position:-64px 0}.flag.flag-pl{background-position:-176px -110px}.flag.flag-kp{background-position:-64px -77px}.flag.flag-uy{background-position:-16px -154px}.flag.flag-gb{background-position:-112px -44px}.flag.flag-gs{background-position:-64px -55px}.flag.flag-kurdistan{background-position:-96px -77px}.flag.flag-rw{background-position:-112px -121px}.flag.flag-ec{background-position:-128px -33px}.flag.flag-mm{background-position:-192px -88px}.flag.flag-pa{background-position:-80px -110px}.flag.flag-wales{background-position:-160px -154px}.flag.flag-kg{background-position:-256px -66px}.flag.flag-ve{background-position:-80px -154px}.flag.flag-tk{background-position:-64px -143px}.flag.flag-ca{background-position:-16px -22px}.flag.flag-is{background-position:-128px -66px}.flag.flag-ke{background-position:-240px -66px}.flag.flag-ro{background-position:-64px -121px}.flag.flag-gq{background-position:-32px -55px}.flag.flag-pt{background-position:-256px -110px}.flag.flag-tf{background-position:-256px -132px}.flag.flag-ad{background-position:0 0}.flag.flag-sk{background-position:-16px -132px}.flag.flag-pm{background-position:-192px -110px}.flag.flag-om{background-position:-64px -110px}.flag.flag-an{background-position:-112px 0}.flag.flag-ws{background-position:-192px -154px}.flag.flag-sh{background-position:-240px -121px}.flag.flag-mp{background-position:-240px -88px}.flag.flag-gt{background-position:-80px -55px}.flag.flag-cf{background-position:-64px -22px}.flag.flag-zanzibar{background-position:0 -165px}.flag.flag-mw{background-position:-80px -99px}.flag.flag-catalonia{background-position:-32px -22px}.flag.flag-ug{background-position:-240px -143px}.flag.flag-je{background-position:-176px -66px}.flag.flag-km{background-position:-32px -77px}.flag.flag-in{background-position:-64px -66px}.flag.flag-bf{background-position:-48px -11px}.flag.flag-mc{background-position:-80px -88px}.flag.flag-sy{background-position:-192px -132px}.flag.flag-sn{background-position:-64px -132px}.flag.flag-kr{background-position:-80px -77px}.flag.flag-eu{background-position:-256px -33px}.flag.flag-bn{background-position:-144px -11px}.flag.flag-st{background-position:-144px -132px}.flag.flag-en{background-position:-192px -33px}.flag.flag-lc{background-position:-192px -77px}.flag.flag-dm{background-position:-80px -33px}.flag.flag-be{background-position:-32px -11px}.flag.flag-ni{background-position:-224px -99px}.flag.flag-ua{background-position:-224px -143px}.flag.flag-mz{background-position:-128px -99px}.flag.flag-pf{background-position:-112px -110px}.flag.flag-tn{background-position:-112px -143px}.flag.flag-ee{background-position:-144px -33px}.flag.flag-xk{background-position:-208px -154px}.flag.flag-sx{background-position:-176px -132px}.flag.flag-sd{background-position:-192px -121px}.flag.flag-gd{background-position:-128px -44px}.flag.flag-ci{background-position:-112px -22px}.flag.flag-sz{background-position:-208px -132px}.flag.flag-cl{background-position:-144px -22px}.flag.flag-fi{background-position:0 -44px}.flag.flag-ga{background-position:-96px -44px}.flag.flag-jp{background-position:-224px -66px}.flag.flag-cs{background-position:-16px -33px}.flag.flag-de{background-position:-32px -33px}.flag.flag-np{background-position:0 -110px}.flag.flag-re{background-position:-48px -121px}.flag.flag-bg{background-position:-64px -11px}.flag.flag-sc{background-position:-160px -121px}.flag.flag-ng{background-position:-208px -99px}.flag.flag-qa{background-position:-32px -121px}.flag.flag-mk{background-position:-160px -88px}.flag.flag-aw{background-position:-208px 0}.flag.flag-kn{background-position:-48px -77px}.flag.flag-al{background-position:-80px 0}.flag.flag-bw{background-position:-240px -11px}.flag.flag-um{background-position:-256px -143px}.flag.flag-ky{background-position:-128px -77px}.flag.flag-tt{background-position:-160px -143px}.flag.flag-so{background-position:-80px -132px}.flag.flag-lt{background-position:0 -88px}.flag.flag-by{background-position:-256px -11px}.flag.flag-bb{background-position:0 -11px}.flag.flag-us{background-position:0 -154px}.flag.flag-md{background-position:-96px -88px}.flag.flag-ag{background-position:-48px 0}.flag.flag-hm{background-position:-160px -55px}.flag.flag-as{background-position:-160px 0}.flag.flag-eg{background-position:-160px -33px}.flag.flag-sv{background-position:-160px -132px}.flag.flag-sl{background-position:-32px -132px}.flag.flag-fk{background-position:-32px -44px}.flag.flag-am{background-position:-96px 0}.flag.flag-ck{background-position:-128px -22px}.flag.flag-tw{background-position:-192px -143px}.flag.flag-kh{background-position:0 -77px}.flag.flag-to{background-position:-128px -143px}.flag.flag-se{background-position:-208px -121px}.flag.flag-cd{background-position:-48px -22px}.flag.flag-pn{background-position:-208px -110px}.flag.flag-gr{background-position:-48px -55px}.flag.flag-id{background-position:-256px -55px}.flag.flag-vc{background-position:-64px -154px}.flag.flag-somaliland{background-position:-96px -132px}.flag.flag-bi{background-position:-96px -11px}.flag.flag-pk{background-position:-160px -110px}.flag.flag-pr{background-position:-224px -110px}.flag.flag-bd{background-position:-16px -11px}.flag.flag-co{background-position:-192px -22px}.flag.flag-fm{background-position:-48px -44px}.flag.flag-bm{background-position:-128px -11px}.flag.flag-ar{background-position:-144px 0}.flag.flag-bv{background-position:-224px -11px}.flag.flag-sb{background-position:-144px -121px}.flag.flag-mq{background-position:-256px -88px}.flag.flag-eh{background-position:-176px -33px}.flag.flag-bh{background-position:-80px -11px}.flag.flag-it{background-position:-144px -66px}.flag.flag-hr{background-position:-192px -55px}.flag.flag-sa{background-position:-128px -121px}.flag.flag-mv{background-position:-64px -99px}.flag.flag-mg{background-position:-128px -88px}.flag.flag-dz{background-position:-112px -33px}.flag.flag-gg{background-position:-192px -44px}.flag.flag-gm{background-position:-256px -44px}.flag.flag-af{background-position:-32px 0}.flag.flag-li{background-position:-208px -77px}.flag.flag-sr{background-position:-112px -132px}.flag.flag-vg{background-position:-96px -154px}.flag.flag-cr{background-position:-208px -22px}.flag.flag-tc{background-position:-224px -132px}.flag.flag-ao{background-position:-128px 0}.flag.flag-ma{background-position:-64px -88px}.flag.flag-mr{background-position:0 -99px}.flag.flag-gn{background-position:0 -55px}.flag.flag-ne{background-position:-176px -99px}.flag.flag-nf{background-position:-192px -99px}.flag.flag-wf{background-position:-176px -154px}.flag.flag-hk{background-position:-144px -55px}.flag.flag-gf{background-position:-160px -44px}.flag.flag-ps{background-position:-240px -110px}.flag.flag-ic{background-position:-240px -55px}.flag.flag-cw{background-position:-256px -22px}.flag.flag-ml{background-position:-176px -88px}.flag.flag-ax{background-position:-224px 0}.flag.flag-gl{background-position:-240px -44px}.flag.flag-dj{background-position:-48px -33px}.flag.flag-cn{background-position:-176px -22px}.flag.flag-ht{background-position:-208px -55px}.flag.flag-lr{background-position:-240px -77px}.flag.flag-tg{background-position:0 -143px}.flag.flag-ba{background-position:-256px 0}.flag.flag-ge{background-position:-144px -44px}.flag.flag-bz{background-position:0 -22px}.flag.flag-au{background-position:-192px 0}.flag.flag-iq{background-position:-96px -66px}.flag.flag-cm{background-position:-160px -22px}.flag.flag-gw{background-position:-112px -55px}.flag.flag-az{background-position:-240px 0}.flag.flag-na{background-position:-144px -99px}.flag.flag-fj{background-position:-16px -44px}.flag.flag-zw{background-position:-32px -165px}.flag.flag-bs{background-position:-192px -11px}.flag.flag-il{background-position:-16px -66px}.flag.flag-nz{background-position:-48px -110px}.flag.flag-me{background-position:-112px -88px}.flag.flag-si{background-position:-256px -121px}.flag.flag-nc{background-position:-160px -99px}.flag.flag-lb{background-position:-176px -77px}",
				),
				/*
				array(
					'id' 			=> 'detect_browser_language',
					'label'			=> __( 'Detect Browser Language' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				*/
				array(
					'id' 			=> 'disable_switch_to_locale',
					'label'			=> __( 'Disable Locale Switcher' , 'language-switcher' ),
					'description'	=> 'For single site implementation the switched language is used to set WPLANG and in translate() but it can be disabled in multi-site implementations.',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				array(
					'id' 			=> 'disable_posts_query_filter',
					'label'			=> __( 'Disable Posts Query Filter' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				array(
					'id' 			=> 'disable_terms_query_filter',
					'label'			=> __( 'Disable Terms Query Filter' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				array(
					'id' 			=> 'disable_menus_query_filter',
					'label'			=> __( 'Disable Menus Query Filter' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				/*				
				array(
					'id' 			=> 'disable_comments_query_filter',
					'label'			=> __( 'Disable Comments Query Filter' , 'language-switcher' ),
					'description'	=> '',
					'type'			=> 'checkbox',
					'default'		=> '',
				),
				*/
			) 
		);
		
		$settings['addons'] = array(
			'title'					=> __( 'Addons', 'language-switcher' ),
			'description'			=> '',
			'class'					=> 'pull-right',
			'logo'					=> $this->parent->assets_url . '/images/recuweb-icon.png',
			'fields'				=> array(
				array(
					'id' 			=> 'addon_plugins',
					'label' 		=> '',
					'type'			=> 'addon_plugins',
					'description'	=> ''
				)				
			),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;	
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings () {
		
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			
			$current_section = '';
			
			if( isset( $_POST['tab'] ) ) {
				
				$current_section = sanitize_text_field($_POST['tab']);
			} 
			elseif( isset( $_GET['tab'] ) ) {
					
				$current_section = sanitize_text_field($_GET['tab']);
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section != $section ) continue;

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {
					
					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) break;
			}
		}
		
		
		//get addons
	
		$this->addons = array(
			
			'language-switcher-everywhere' 	=> array(
			
				'title' 		=> 'Languages Everywhere',
				'addon_link' 	=> 'https://code.recuweb.com/get/language-switcher-everywhere/',
				'addon_name' 	=> 'language-switcher-everywhere',
				'source_url' 	=> '',
				'logo_url' 		=> 'https://code.recuweb.com/c/u/3a09f4cf991c32bd735fa06db67889e5/2018/07/language-switcher-everywhere-squared-300x300.png',
				'description'	=> 'Extends Language Switcher to add languages to custom post types and taxonomies like WooCommerce products or tags',
				'author' 		=> 'Code Market',
				'author_link' 	=> 'https://code.recuweb.com/about-us/',
			),
			/*
			'language-switcher-synchronizer' 	=> array(
			
				'title' 		=> 'Languages Synchronizer',
				'addon_link' 	=> 'https://code.recuweb.com/get/language-switcher-synchronizer/',
				'addon_name' 	=> 'language-switcher-synchronizer',
				'source_url' 	=> '',
				'logo_url' 		=> 'https://code.recuweb.com/c/u/3a09f4cf991c32bd735fa06db67889e5/2018/07/language-switcher-synchronizer-squared-300x300.png',
				'description'	=> 'Extends Language Switcher to automatically synchronize language urls from one page to another',
				'author' 		=> 'Code Market',
				'author_link' 	=> 'https://code.recuweb.com/about-us/',
			),
			*/
		);
	}

	public function settings_section ( $section ) {
		
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		
		echo wp_kses_normalize_entities($html);
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page () {
		
		$plugin_data = get_plugin_data( $this->parent->file );
		
		// Build page HTML
		
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
			
			$html .= '<h1>' . __( $plugin_data['Name'] , 'language-switcher' ) . '</h1>' . "\n";

			$tab = '';
			
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				
				$tab .= sanitize_text_field($_GET['tab']);
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				
				foreach($this->settings as $section => $data ) {

					// Set tab class
					
					$class = 'nav-tab';
					
					if ( !isset( $_GET['tab'] ) ) {
						
						if ( 0 == $c ) {
							
							$class .= ' nav-tab-active';
						}
					}
					elseif ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							
						$class .= ' nav-tab-active';
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . ( !empty($data['logo']) ? '<img src="'.$data['logo'].'" alt="" style="margin-top: 4px;margin-right: 7px;float: left;">' : '' ) . wp_kses_normalize_entities($data['title']) . '</a>' . "\n";

					++$c;
				}

				$html .= '</h2>' . "\n";
			}
			
			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

				// Get settings fields
				
				ob_start();
				
				settings_fields( $this->parent->_token . '_settings' );
				
				if( isset($_GET['tab']) && $_GET['tab'] == 'addons' ){
					
					$this->do_settings_sections( $this->parent->_token . '_settings' );
				}
				else{
					
					do_settings_sections( $this->parent->_token . '_settings' );
				}
				
				$html .= ob_get_clean();
				
				if( isset($_GET['tab']) && $_GET['tab'] == 'addons' ){
					
					//do nothing
				}
				elseif( count($this->settings) > 1 ){

					$html .= '<p class="submit">' . "\n";
						
						$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
						
						$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings' , 'user-session-synchronizer' ) ) . '" />' . "\n";
					
					$html .= '</p>' . "\n";
				}
				
			$html .= '</form>' . "\n";
		
		$html .= '</div>';

		echo wp_kses_normalize_entities($html);
	}

	public function do_settings_sections($page) {
		
		global $wp_settings_sections, $wp_settings_fields;

		if ( !isset($wp_settings_sections) || !isset($wp_settings_sections[$page]) )
			return;

		foreach( (array) $wp_settings_sections[$page] as $section ) {
			
			echo '<h3 style="margin-bottom:25px;">' . wp_kses_normalize_entities($section['title']) . '</h3>' . PHP_EOL;
			
			call_user_func($section['callback'], $section);
			
			if ( !isset($wp_settings_fields) ||
				 !isset($wp_settings_fields[$page]) ||
				 !isset($wp_settings_fields[$page][$section['id']]) )
					continue;
					
			echo '<div class="settings-form-wrapper" style="margin-top:25px;">';

				$this->do_settings_fields($page, $section['id']);
			
			echo '</div>';
		}
	}

	public function do_settings_fields($page, $section) {
		
		global $wp_settings_fields;

		if ( !isset($wp_settings_fields) ||
			 !isset($wp_settings_fields[$page]) ||
			 !isset($wp_settings_fields[$page][$section]) )
			return;

		foreach ( (array) $wp_settings_fields[$page][$section] as $field ) {
			
			echo '<div class="settings-form-row row">';

				if ( !empty($field['title']) ){
			
					echo '<div class="col-xs-3" style="margin-bottom:15px;">';
					
						if ( !empty($field['args']['label_for']) ){
							
							echo '<label class="lsw-active" for="' . esc_attr($field['args']['label_for']) . '">' . wp_kses_normalize_entities($field['title']) . '</label>';
						}
						else{
							
							echo '<b>' . wp_kses_normalize_entities($field['title']) . '</b>';		
						}
					
					echo '</div>';
					echo '<div class="col-xs-9" style="margin-bottom:15px;">';
						
						call_user_func($field['callback'], $field['args']);
							
					echo '</div>';
				}
				else{
					
					echo '<div class="col-xs-12" style="margin-bottom:15px;">';
						
						call_user_func($field['callback'], $field['args']);
							
					echo '</div>';					
				}
					
			echo '</div>';
		}
	}

	/**
	 * Main Language_Switcher_Settings Instance
	 *
	 * Ensures only one instance of Language_Switcher_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Language_Switcher()
	 * @return Main Language_Switcher_Settings instance
	 */
	public static function instance ( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
