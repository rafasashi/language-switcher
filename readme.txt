=== Language Switcher ===
Contributors: rafasashi
Tags: language, languages, switcher, internationalization, internationalisation, multi-language, multilanguage, translation
Donate link: https://code.recuweb.com/get/language-switcher/
Requires at least: 4.6
Tested up to: 5.3
Stable tag: 3.1.5
License: GPLv3
License URI: https://code.recuweb.com/product-licenses/

Add a Language Switcher to Post Types and Taxonomies.

== Description ==

Language Switcher allows you to map urls of alternative languages for Post Types and Taxonomies. Additionally it allows you to filter archive pages by language.

= Free Features =

– STANDALONE - No Wordpress Multisite required
– MIXED SOURCES - map internal or external urls
– POST TYPE LANGUAGE - Add a main language selector to post types and map urls of alternative languages
– TAXONOMY LANGUAGE  - Add a main language selector to terms and map urls of alternative languages
– HREFLANG LINKS - Add hreflang links into the head of each page
– MENU LANGUAGE - Add a main language selector to the menu settings and switch the menu accordingly
– LANGUAGE FILTERS - Filter items by language in the main WP_Query of archive pages
– LANGUAGE WIDGET - Add the language switcher with the widget
– LANGUAGE SHORTCODE - Add the language switcher anywhere with the shortcode [language-switcher]
– LANGUAGE MENUS - Add the language switcher to your navigation menus

= Addon Features =
 
– [LANGUAGE EVERYWHERE](https://code.recuweb.com/get/language-switcher-everywhere/) –  Enable language switcher for custom post types and taxonomies such as WooCommerce Product, Order, Category and Tags
 
More information about [Language Switcher](https://code.recuweb.com/get/language-switcher/)

= Localization =

* English

= Documentation =

For all documentation on this plugin go to: [https://code.recuweb.com/get/language-switcher/](https://code.recuweb.com/download/language-switcher/)

== Installation ==

= Minimum Requirements =

* Wordpress 4.6 or higher.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install of Language Switcher, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "Language Switcher" and click Search Plugins. Once you've found my plugin extension you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now. After clicking that link you will be asked if you're sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

= Manual installation =

The manual installation method involves downloading my plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

= Switch Language before any plugins =

If the Language Switcher is loaded after other Plugins add the following code to wp-config.php before requesting wp-settings.php

`if( !empty($_COOKIE['lsw_main_lang']) ){

	$locale = $_COOKIE['lsw_main_lang'] . '_' . strtoupper($_COOKIE['lsw_main_lang']);

	if(!defined('WPLANG'))
		define ('WPLANG', $locale);
}`

= Upgrading =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Screenshots ==

1. The language switcher
2. Edit post type language
3. Edit tag language
4. Select activate languages
5. Select activate post types and taxonomies
6. Add the language switcher with the widget

== Changelog ==

= 3.0.9 - 9/12/2018 =

* Macrolanguages such as Arabic added

= 3.0.8 - 26/11/2018 =

* New settings to disable query filters

= 3.0.7 - 23/11/2018 =

* Redirection issue fixed

= 3.0.6 - 19/11/2018 =

* Query language taxonomy fixed

= 3.0.5 - 5/11/2018 =

* Startup errors fixed

= 3.0.4 - 10/10/2018 =

* Defaut language from cookie fixed

= 3.0.3 - 4/10/2018 =

* Defaut language fixed

= 3.0.2 - 27/9/2018 =

* Edit term language fixed

= 3.0.1 - 21/9/2018 =

* Switch Language before any plugins 

= 3.0.0 - 2/9/2018 =

* Publicaly Released.