<?php
	/**
	 * 
	 * 
	 * File for managing shortcodes
	 * 
	 * 
	 */


	add_shortcode( 'astrava', 'parse_astrava_shortcode');
	function parse_astrava_shortcode( $atts ){
		$api_options = wp_parse_args(get_option('astrava_api_settings')); 
        $gen_options = wp_parse_args(get_option('astrava_gen_settings'), array('astrava_embed_type' => 'iframe'));
        $html_out 	 = '';

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
					$template = $gen_options['astrava_embed_template'];
					
					// Name
					$template = str_replace('[name]', $activity->name, $template);

					// Description
					$template = str_replace('[description]', $activity->description, $template);

					// Time
					$template = str_replace('[time]', gmdate('H:i:s', $activity->moving_time), $template);

					// Distance
					if ($gen_options['astrava_embed_units'] == i) {
						$distance = round($activity->distance * 0.00062137, 2) . ' miles';
					} else {
						$distance = round($activity->distance / 1000, 2) . ' k';
					}
					$template = str_replace('[distance]', $distance, $template);

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

					$template = str_replace('[speed]', $speed, $template);

					// Suffer Score
					$template = str_replace('[suffer]', $activity->suffer_score, $template);

					// Average HR
					$template = str_replace('[avg_hr]', $activity->average_heartrate, $template);

					// Max HR
					$template = str_replace('[max_hr]', $activity->max_heartrate, $template);

					// Type
					$template = str_replace('[type]', $activity->type, $template);

					// Elevation
					$template = str_replace('[elevation]', $activity->total_elevation_gain, $template);

					ob_start();
					?>
					<div id="strava_map"></div>
					<script type="text/javascript" src="http://maps.google.com/maps/api/js??key=AIzaSyAoAUyOJ-PoLBa8QGGyWNf_A1-6Mh8f84Q&libraries=geometry&amp;sensor=false">
					</script>
					<style type="text/css"> #strava_map {width:100%;height:300px;}</style> 
					<script type='text/javascript'>
				        var map = new google.maps.Map(document.getElementById('strava_map'), {
				          center: {lat: <?php echo $activity->start_latlng[0]; ?>, 
				          		   lng: <?php echo $activity->start_latlng[1]; ?>},
				          zoom: 15
				        });

				        var decodedPath = google.maps.geometry.encoding.decodePath("<?php echo addslashes($activity->map->polyline); ?>");

				        var setRegion = new google.maps.Polyline({
									        path: decodedPath,
									        strokeColor: "#FF0000",
									        strokeOpacity: 1.0,
									        strokeWeight: 2,
									        map: map
									    });

				        var points = setRegion.getPath().getArray();
					    var bounds = new google.maps.LatLngBounds();
					    for (var n = 0; n < points.length ; n++){
					        bounds.extend(points[n]);
					    }
					    
					    center = bounds.getCenter();
					    map.setCenter(center);
					</script>
					<?php
					$map = ob_get_clean();
					
					ob_end_flush();

					// Google Map
					$template = str_replace('[map]', $map, $template);

					$html_out = $template;
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

	// add_shortcode( 'astrava_map', 'parse_astrava_map_shortcode');
	// function parse_astrava_map_shortcode($atts){

	// }
