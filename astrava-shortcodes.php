<?php
	/**
	 * 
	 * 
	 * File for managing shortcodes
	 * 
	 * 
	 */


	add_shortcode( 'astrava', 'parse_astrava_shortcode');

	/**
	 * Shortcode processor for [astrava]
	 * Options:
	 *  [astrava
	 * 	 	activity=  (The strava activity id)
	 * 	 	iframe     (Force use of iframe instead of template)
	 *  ]
	 * @param  array $atts attributes passed into the shortcode
	 * @return string Formated HTML
	 */
	function parse_astrava_shortcode( $atts ){
		$api_options = wp_parse_args(get_option('astrava_api_settings')); 
        $gen_options = wp_parse_args(get_option('astrava_gen_settings'), array('astrava_embed_type' => 'iframe'));
        $html_out 	 = '';
        $template 	 = new Template(ASTRAVA_PLUGIN_DIR . 'templates/');

        //Check to see if the options are avaialble
        if (!empty($api_options['astrava_strava_oauth'])){
            $strava          = new StravaApi($api_options['astrava_strava_client_id'], 
                                             $api_options['astrava_strava_client_secret'],
                                             $api_options['astrava_strava_oauth']);
			//Get the Activity details for the id
			$activity = $strava->getActivity($atts['activity']);
			if (isset($activity->id)) {
				if ($gen_options['astrava_embed_type'] == 'iframe' or isset($atts['iframe'])) {
					$html_out = '<iframe height="405" width="100%"" frameborder="0" allowtransparency="true" scrolling="no" src="https://www.strava.com/activities/' . $atts['activity'] . '/embed/' . $activity->embed_token . '"></iframe>';
				} else {
					$strava_template = $gen_options['astrava_embed_template'];
					
					// Name
					$strava_template = str_replace('[name]', $activity->name, $strava_template);

					// Description
					$strava_template = str_replace('[description]', $activity->description, $strava_template);

					// Time
					$strava_template = str_replace('[time]', gmdate('H:i:s', $activity->moving_time), $strava_template);

					// Distance
					if ($gen_options['astrava_embed_units'] == i) {
						$distance = round($activity->distance * 0.00062137, 2) . ' miles';
					} else {
						$distance = round($activity->distance / 1000, 2) . ' k';
					}
					$strava_template = str_replace('[distance]', $distance, $strava_template);

					// Speed
					if ($gen_options['astrava_embed_units'] == i) {
						if (strtolower($activity->type) == 'run' && $gen_options['astrava_embed_pace']) {
							$speed   = 60/round($activity->average_speed * 2.23693629, 2);
							$hours   = floor($speed);
							$minutes = round(($speed - $hours) * 60);
							$speed   = $hours . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
							$speed  .= '/mile';
						} else {
							$speed = round($activity->average_speed * 2.23693629, 2) . ' mph';
						}
					} else {
						if (strtolower($activity->type) == 'run' && $gen_options['astrava_embed_pace']) {
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

					// Google Map
					$map = $template->render('google-map', array('lat' 		=> $activity->start_latlng[0],
																 'lng' 		=> $activity->start_latlng[1],
																 'polyline' => addslashes($activity->map->polyline)));
					
					$strava_template = str_replace('[map]', $map, $strava_template);

					// Elevation
					$strava_template = str_replace('[photo]', $activity->total_elevation_gain, $strava_template);

					// Elevation
					$strava_template = str_replace('[photoOrMap]', $activity->total_elevation_gain, $strava_template);

					$html_out = $strava_template;
				}
				//} else {
					//user a template
				//}

			} else {
				$html_out = "[No Activity Found]";
			}
		
		}

		return $html_out;
	}
	