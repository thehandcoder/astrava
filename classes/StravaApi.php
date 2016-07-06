<?php
/**
 * A basic class for accessing the strava API
 */
class StravaApi {
	/**
	 * The Strava ClientId
	 * @var string
	 */
	protected $client_id;

	/**
	 * The Strava Client Secret
	 * @var string
	 */
	protected $client_secret;

	/**
	 * The Bearer token provided via a auth request
	 * @var [type]
	 */
	protected $oauth_token;

	/**
	 * ichy-ichy-ptong
	 * @param string $client_id     The Strava client id
	 * @param string $client_secret The Strava client secret
	 * @param string $oauth_token   (Optional) The bearer token provided by Strava auth request
	 */
	function __construct($client_id, $client_secret, $oauth_token = '') {
		$this->client_id 	 = $client_id;
		$this->client_secret = $client_secret;
		$this->oauth_token 	 = $oauth_token;
	}

	/**
	 * Get a authorize url for creating a authorization request
	 * 
	 * @param  string $redirect The url to redirect to after the completed request
	 * 
	 * @return string The fully formated authorize url
	 */
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

	/**
	 * Make a request of the Strava API
	 * 
	 * @param  string $endpoint Endpoint to request
	 * @param  string $method   (Optional) Type of request
	 * @param  array  $fields   An addition fields to pass to the request
	 * 
	 * @return stdClass The object created by decoding the JSON
	 */
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

	/**
	 * Get a list of activities
	 * 
	 * @param  array  $options The various options available to the endpoint.  See Strava API docs for details
	 * 
	 * @return stdClass The object created by decoding the JSON
	 */
	function getActivities($options = array()) {
		
		if (!isset($options['per_page'])) {
			$options['per_page'] = 10;	
		}

		$activities = $this->requstApi('athlete/activities', 'get', $options);
		return $activities;
	}

	/**
	 * Get a single activity as specified by the id
	 * 
	 * @param  int $id The id of the Strava activity
	 * 
	 * @return stdClass The object created by decoding the JSON
	 */
	function getActivity($id) {
		$activity = $this->requstApi("activities/{$id}");
		return $activity;
	}

	/**
	 * Retrieve a bearer token after an authorization request
	 * 
	 * @param  string $authcode The authcode provided in the callback from Strava
	 * 
	 * @return string The bearer token
	 */
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
