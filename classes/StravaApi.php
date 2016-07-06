<?php

class StravaApi {
	

	protected $client_id;
	protected $client_secret;
	protected $oauth_token;

	function __construct($client_id, $client_secret, $oauth_token = '') {
		$this->client_id 	 = $client_id;
		$this->client_secret = $client_secret;
		$this->oauth_token 	 = $oauth_token;
	}

	function getAuthorizeURL($redirect) {
		$fields = array(
				'client_id' 	  => $this->client_id,
				'state'			  => 'processAuthRequest',
				'response_type'   => 'code',
				'approval_prompt' => 'auto',
				'redirect_uri'    => $redirect
			);

		$url = "https://www.strava.com/oauth/authorize?" . http_build_query($fields);

		return $url;
	}


	function requstApi($endpoint, $method = 'get', $fields = array()) {


		if ($endpoint == 'token') { 
			$url = "https://www.strava.com/oauth/{$endpoint}";
		} else {
			$url = "https://www.strava.com/api/v3/{$endpoint}";
		}

		//open connection
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		switch ($method) {
			case 'post':
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, count($fields));
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
				break;
			default:
				curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($fields));
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->oauth_token));
				break;

		}

		//execute post
		$result = json_decode(curl_exec($ch));

		//close connection
		curl_close($ch);

		return $result;
	}

	function getActivities($options = array()) {
		
		if (!isset($options['per_page'])) {
			$options['per_page'] = 10;	
		}

		$activities = $this->requstApi('athlete/activities', 'get', $options);
		return $activities;
	}

	function getActivity($id) {
		$activity = $this->requstApi("activities/{$id}");
		return $activity;
	}

	function fetchOauthToken($authcode) {
		
		$fields = array(
			'client_id' 	=> $this->client_id,
			'client_secret' => $this->client_secret,
			'code' 			=> $authcode
		);

		$results = $this->requstApi('token', 'post', $fields);

		return $results->access_token;
	}
}