<?php

	// Add activiation/deactivation hooks
	register_activation_hook(__FILE__, 'astrava_activate');
	register_deactivation_hook(__FILE__, 'astrava_deactivation');

	function astrava_activate() {
		// Store default option values
		$options = array(
    					 'embed_type' => 'template',
    					 'display_astrava_auto_create' => No,
    					 'auto_create_post_cat' => '',
    					 'embed_units' => 'i',
    					 'embed_pace' => 1);
		
		update_option('astrava_gen_settings', $options);

		// Add the Templates
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

		// Import marker for furture imports
		update_option('astrava_last_activity_imported', time());

	}


	function astrava_deactivation() {
		delete_option('astrava_gen_settings');
		delete_option('astrava_api_settings');
		delete_option('astrava_templates');
		delete_option('astrava_version');
		delete_option('astrava_last_activity_imported');
		wp_clear_scheduled_hook('astrava_import');
	}