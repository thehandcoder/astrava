<?php
	/**
	 * 
	 * 
	 * File for managing shortcodes
	 * 
	 * 
	 */
	add_shortcode( 'astrava', 'parse_astrava_shortcode');
	add_shortcode( 'astrava_map', 'parse_astrava_map_shortcode');
	
	/**
	 * Add the google mapls api
	 */
	add_action( 'wp_enqueue_scripts', 
				function () {
	    			wp_enqueue_script( 'google_maps', $src = '//maps.google.com/maps/api/js?key=AIzaSyAoAUyOJ-PoLBa8QGGyWNf_A1-6Mh8f84Q&libraries=geometry&amp;sensor=false');
				});

	/**
	 * Shortcode processor for [astrava]
	 * Options:
	 *  [astrava
	 * 	 	activity=###  (Requried) The strava activity id
	 * 	 	iframe     	  (Optional) Force use of iframe instead of template
	 * 	 	noname		  (Optional) Exclude name from template output
	 * 	 	nodescription (Optional) Exclude description from template output
	 *  ]
	 *  
	 * @param  array $atts attributes passed into the shortcode
	 * 
	 * @return string Formated HTML
	 */
	function parse_astrava_shortcode($atts){
		$api_options 	   = wp_parse_args(get_option('astrava_api_settings')); 
        $gen_options 	   = wp_parse_args(get_option('astrava_gen_settings'), array('embed_type' => 'iframe'));
        $astrava_templates = get_option('astrava_templates');

        $html_out 	 	   = '';
        $template 	 	   = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (!empty($api_options['strava_oauth'])){
			$strava = new StravaApi($api_options['strava_client_id'], 
                                 	$api_options['strava_client_secret'],
                                 	$api_options['strava_oauth']);

			//Get the Activity details for the id
			// create a hash from the params
            // check for a transient If none was found then make the request
            $activity = get_transient('astrava-activity-id'. $atts['activity']);
            
            if (!$activity) {
				$activity = $strava->getActivity($atts['activity']);
				set_transient('astrava-activity-id'. $atts['activity'], $activity, 60 * 60);
			} 
			
			if (isset($activity->id)) {
				if ($gen_options['embed_type'] == 'iframe' or isset($atts['iframe'])) {
					$html_out = '<iframe height="405" width="100%"" frameborder="0" allowtransparency="true" scrolling="no" src="https://www.strava.com/activities/' . $atts['activity'] . '/embed/' . $activity->embed_token . '"></iframe>';
				} else {

					if (isset($astrava_templates[strtolower($activity->type)])) {
						$strava_template = $astrava_templates[strtolower($activity->type)];
					} else {
						$strava_template = $astrava_templates['default'];
					}
					
					// Name
					$activity_name = ((in_array('noname', $atts)) ? '' : $activity->name);
					$strava_template = str_replace('[name]', $activity_name, $strava_template);

					// Description
					$activity_desc = ((in_array('nodescription', $atts)) ? '' : $activity->description);
					$strava_template = str_replace('[description]', $activity_desc, $strava_template);

					// Time
					$strava_template = str_replace('[time]', gmdate('H:i:s', $activity->moving_time), $strava_template);

					// Distance
					if ($gen_options['embed_units'] == i) {
						$distance = round($activity->distance * 0.00062137, 2) . ' miles';
					} else {
						$distance = round($activity->distance / 1000, 2) . ' k';
					}
					$strava_template = str_replace('[distance]', $distance, $strava_template);

					// Speed
					if ($gen_options['embed_units'] == i) {
						if (strtolower($activity->type) == 'run' && $gen_options['embed_pace']) {
							$speed   = 60/round($activity->average_speed * 2.23693629, 2);
							$hours   = floor($speed);
							$minutes = round(($speed - $hours) * 60);
							$speed   = $hours . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
							$speed  .= '/mile';
						} else {
							$speed = round($activity->average_speed * 2.23693629, 2) . ' mph';
						}
					} else {
						if (strtolower($activity->type) == 'run' && $gen_options['embed_pace']) {
							$speed   = 60/round($activity->average_speed * 3.6, 2);
							$hours   = floor($speed);
							$minutes = round(($speed - $hours) * 60);
							$speed   = $hours . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
							$speed  .= '/k';
						} else {
							$speed = round($activity->average_speed * 3.6, 2) . ' kph';
						}
					}

					$strava_template = str_replace('[speed]', $speed, $strava_template);

					// Suffer Score
					$strava_template = str_replace('[suffer]', $activity->suffer_score, $strava_template);

					// Average HR
					$strava_template = str_replace('[avg_hr]', $activity->average_heartrate, $strava_template);

					// Max HR
					$strava_template = str_replace('[max_hr]', $activity->max_heartrate, $strava_template);

					// Type
					$strava_template = str_replace('[type]', $activity->type, $strava_template);

					// Elevation
					$strava_template = str_replace('[elevation]', $activity->total_elevation_gain, $strava_template);

					if (in_array('useMapEmbed', $atts)) {
						$map = '[astrava_map id="' . $activity->id 
								. '" polyline="' . base64_encode(addslashes($activity->map->polyline))
								. '" lat="' . $activity->start_latlng[0] 
								. '" lng="' . $activity->start_latlng[1] . '"]';
					} else {
						// Google Map
						$map = $template->render('google-map', 
												  array('id'		=> $activity->id,
												  	    'lat' 		=> $activity->start_latlng[0],
												  	    'lng' 		=> $activity->start_latlng[1],
												  	    'polyline' => addslashes($activity->map->polyline)
												  	    ));
					}

					
					
					$strava_template = str_replace('[map]', $map, $strava_template);

					// Photo
					if ($activity->total_photo_count > 0) {
						// find the largest available
						$largest_size = 0;
						foreach($activity->photos->primary->urls as $size => $url) {	
							if ($size > $largest_size) {
								$photo_url = $url;
								$largest_size = $size;
							}
						} 
						$photo_html = $template->render('image', array('photo_url' => $photo_url));
					} else {
						$photo_html = '';
					}

					$strava_template = str_replace('[photo]', $photo_html, $strava_template);

					// photoOrMap
					$photo_or_map = (($activity->total_photo_count > 0) ? $photo_html : $map); 
					$strava_template = str_replace('[photoOrMap]', $photo_or_map, $strava_template);

					$html_out = $strava_template;
				}

			} else {
				$html_out = "[No Activity Found]";
			}
		
		}

		return $html_out;
	}

	/**
	 * Shortcode processor for [astrava]
	 * Options:
	 *  [astrava
	 * 	 	id=###   (Requried) The strava activity id
	 * 	 	polyline (Optional) If polyline, lat and lng are specified, the activity won't be retrieved from strava
	 * 	 	lat		 (Optional) If polyline, lat and lng are specified, the activity won't be retrieved from strava
	 * 	 	lng 	 (Optional) If polyline, lat and lng are specified, the activity won't be retrieved from strava
	 *  ]
	 *  
	 * @param  array $atts attributes passed into the shortcode
	 * 
	 * @return string Formated HTML
	 */
	function parse_astrava_map_shortcode($atts){
		$api_options 	   = wp_parse_args(get_option('astrava_api_settings')); 
        $gen_options 	   = wp_parse_args(get_option('astrava_gen_settings'), array('embed_type' => 'iframe'));
        $astrava_templates = get_option('astrava_templates');

        $html_out 	 	   = '[No Map Found]';
        $template 	 	   = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (!empty($api_options['strava_oauth'])){
			$strava = new StravaApi($api_options['strava_client_id'], 
                                 	$api_options['strava_client_secret'],
                                 	$api_options['strava_oauth']);

			//Get the Activity details for the id
			if (!isset($atts['polyline'])) {
				$activity = get_transient('astrava-activity-id'. $atts['activity']);
            
	            if (!$activity) {
					$activity = $strava->getActivity($atts['activity']);
					set_transient('astrava-activity-id'. $atts['activity'], $activity, 60 * 60);
				} 	

				$map_opts = array('id'		=> $activity->id,
								  'lat' 	=> $activity->start_latlng[0],
								  'lng' 	=> $activity->start_latlng[1],
								  'polyline' => addslashes($activity->map->polyline));
			} else {
				$map_opts = array('id'		 => $atts['id'],
								  'lat' 	 => $atts['lat'],
								  'lng' 	 => $atts['lng'],
								  'polyline' => base64_decode($atts['polyline']));
			}

			if (isset($map_opts)) {
				// Google Map
				$map = $template->render('google-map', $map_opts);
				
				$html_out = $map;
			}
		
		}

		return $html_out;
	}

