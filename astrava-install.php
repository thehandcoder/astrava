<?php
	add_action('plugins_loaded', 'astrava_update_check');
	function astrava_update_check() {
		$current_version = get_option('astrava_version');
    	
    	if (get_option($current_version) != ASTRAVA_VERSION) {
        	switch(true) {
        		case $current_version < 0.2:
        			$gen_options = get_option('astrava_gen_settings');
        			$template = $gen_options['astrava_embed_template'];
        			unset($gen_options['astrava_embed_template']);
        			update_option('astrava_gen_settings', $gen_options);
        			update_option('astrava_templates', array('default' => $template));
        			// Move the template from general to templates
        		default:
        			update_option('astrava_version', ASTRAVA_VERSION);
        	}
    	}
	}


	// Add activiation/deactivation hooks
	register_activation_hook(__FILE__, 'astrava_activate');
	register_deactivation_hook(__FILE__, 'astrava_deactivation');

	function astrava_activate() {
		// Store default option values
		$options = array(
    					 'astrava_embed_type' => 'template',
    					 'display_astrava_auto_create' => No,
    					 'astrava_auto_create_post_cat' => '',
    					 'astrava_embed_units' => 'i',
    					 'astrava_embed_pace' => 1);
		
		update_option('astrava_gen_settings', $options);

		$options = array('default' => '<h3>[name]</h3>
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

		update_option('astrava_templates', $options);


		// Create cron event for activity imports
		if (!wp_next_scheduled('astrava_import')) {
			wp_schedule_event(time(), 'hourly', 'astrava_import');
	    }
	}


	function astrava_deactivation() {
		delete_option('astrava_gen_settings');
		delete_option('astrava_api_settings');
		delete_option('astrava_version');
		wp_clear_scheduled_hook('astrava_import');
	}