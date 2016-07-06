<?php

add_action( 'wp_ajax_get_strava_activities', 'get_strava_activities' );

function get_strava_activities() {

	display_astrava_admin_meta();

	wp_die(); // this is required to terminate immediately and return a proper response
}