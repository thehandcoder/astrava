<?php	

	// Add a 5 minute cron schedule
	add_filter('cron_schedules', 
				function ($schedules){
				    if(!isset($schedules["5min"])){
				        $schedules["5min"] = array(
				            'interval' => 5*60,
				            'display' => __('Once every 5 minutes'));
				    }
				    return $schedules;
				});


	// Add the cron event if it doesn't exist
	// 
	if(!wp_get_schedule('astrava_import')){
	    add_action('init', 
	    			function (){
	    				wp_schedule_event(time(), '5min', 'astrava_import');
					}, 10);
	}

	// Add Astrava cron
	add_action('astrava_import', 'astrava_import_events');
	
	/**
	 * The astrava import cron
	 * @param  array  $opts Any cron options that are passed
	 * @return void
	 */
	function astrava_import_events($opts = array()) {
		$gen_settings = get_option('astrava_gen_settings');

		// Check if import is turned on
		if ($gen_settings['auto_create_post'] == 1) {

			// Get the date of the last activity imported
			$last_activity_imported = get_option('astrava_last_activity_imported');
			if ($last_activity_imported) {
				$api_settings 			= get_option('astrava_api_settings');

				$strava     = new StravaApi($api_settings['strava_client_id'], 
	                                        $api_settings['strava_client_secret'],
	                                        $api_settings['strava_oauth']);
	            
	            $activities = $strava->getActivities(array('after' => $last_activity_imported));
	            $activity_time = $last_activity_imported;

	            // We have new activities
	            if (count($activities) > 0) {
	            	foreach($activities as $activity) {
	            		// Check to see if there's already a post for this ID
	            		$posts = get_posts(array(
								    'meta_key'   => 'strava_id',
								    'meta_value' => $activity->id,
								 ));

	            		if (count($posts) > 0) {
	            			if (!get_transient('overwrite-strava-posts')) {
	            				// if we don't have an overwrite, go to the next post
	            				continue;
	            			} else {
	            				// delete the current post and replace it with a new post
	            				wp_delete_post( $posts[0]->ID, true);
	            			}
	            		}

	       				// Render the shortcode so we actually have all the content
	            		$post_content = '[astrava activity="' . $activity->id . '" noname useMapEmbed]';
	            		$post_content = do_shortcode($post_content);

		            	// Create a new post for the activity
		            	// The user is always the initial site user
		            	$post_id = wp_insert_post(array(
				            						'post_content' 	=> $post_content ,
				            						'post_title'   	=> $activity->name,
				            						'post_date'		=> $activity->start_date,
				            						'post_status'   => 'publish',
				            						'post_author'	=> 1,
				            						'post_category' => $gen_settings['auto_create_post_cat']
				            					  ));

		            	// Set it to the proper category
		            	wp_set_post_categories($post_id, $gen_settings['auto_create_post_cat']);

		            	// Add the activity ID
		            	add_post_meta($post_id, 'strava_id', $activity->id, true);

		            	// If this post has a newer data update the transient for the next time we look
		            	if (strtotime($activity->start_date) > $activity_time) {
		            		$activity_time = strtotime($activity->start_date);	
		            	}
	            	}
	            	
	            	// Update the option
	            	update_option('astrava_last_activity_imported', $activity_time);
	            }

			} else {
				update_option('astrava_last_activity_imported', time());
			}
		}
	}