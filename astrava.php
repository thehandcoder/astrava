<?php
	/*
		Plugin Name: Astrava Plugin
		Plugin URI: http://jesustaketheheels.com
		Description: Integrates the Strava api with wordpress
		Version: 1
		Author: thehandcoder
		Author URI: http://jesustaketheheels.com
	*/
	
	// Basic Constants
	define('ASTRAVA_PLUGIN_DIR', plugin_dir_path(__FILE__));
	define('ASTRAVA_PLUGIN_URL', plugin_dir_url(__FILE__));
	
	// Require classes and other files
	require_once(ASTRAVA_PLUGIN_DIR . 'classes/StravaApi.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'classes/Template.php');
	require_once(ASTRAVA_PLUGIN_DIR . 'astrava-shortcodes.php');
	if (is_admin()) {
		require_once(ASTRAVA_PLUGIN_DIR . 'astrava-admin.php');
	}

	// Add activiation hook to fill in default option values
	register_activation_hook(__FILE__, 'default_options');
	
	function default_options() {
		$options = array(
    					 'astrava_embed_type' => 'template',
    					 'display_astrava_auto_create' => No,
    					 'astrava_auto_create_post_cat' => '',
    					 'astrava_embed_units' => 'i',
    					 'astrava_embed_pace' => 1,
    					 'astrava_embed_template' => '<h3>[name]</h3>
														[description]

														[map]
														<table style="width: 100%;">
														<tbody>
														<tr style="height: 52px;">
															<td style="width: 33%;"><strong>Time: </strong>[time]</td>
															<td style="width: 33%;"><strong>Distance: </strong>[distance]</td>
															<td style="width: 33%;"><strong>Speed: </strong>[speed]</td>
														</tr>
														<tr>
															<td><strong>Suffer Score</strong>: [suffer]</td>
															<td><strong>Max HR</strong>: [max_hr] bpm</td>
															<td><strong>Avg HR: </strong>[avg_hr] bpm</td>
														</tr>
														</tbody>
														</table>');
		
		update_option('astrava_gen_settings', $options);
	}
	