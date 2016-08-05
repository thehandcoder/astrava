<?php
	/*
		Plugin Name: Astrava Plugin
		Plugin URI: http://jesustaketheheels.com
		Description: Integrates the Strava api with wordpress.  Allow user to embed strava events into posts.  Can select between strava iframe and custom templates.
		Version: 0.2
		Author: thehandcoder
		Author URI: http://jesustaketheheels.com
	*/
	
	/**
	 * Define the constants
	 */
	define('ASTRAVA_PLUGIN_DIR', plugin_dir_path(__FILE__));
	define('ASTRAVA_PLUGIN_URL', plugin_dir_url(__FILE__));
	define('ASTRAVA_VERSION', 0.2);
	
	/**
	 * Add the astrava clases
	 */
	require_once(ASTRAVA_PLUGIN_DIR . 'classes/StravaApi.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'classes/Template.php');

	/**
	 * Add the astrava pieces
	 */
	require_once(ASTRAVA_PLUGIN_DIR . 'astrava-install.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'astrava-crons.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'astrava-shortcodes.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'astrava-ajax.php');

	if (is_admin()) {
		require_once(ASTRAVA_PLUGIN_DIR . 'astrava-admin.php');
	}


    add_filter('pre_get_posts', 
           function($query) {
                $gen_options = get_option('astrava_gen_settings');

                if ($query->is_home 
                	&& $query->is_main_query() 
                	&& $gen_options['exclude_auto_cat'] == 1) {

                    $query->set('cat', '-' . $gen_options['auto_create_post_cat']);
                }

                return $query;
            });

	