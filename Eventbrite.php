<?php
/**
 * From https://github.com/ryanjarvinen/eventbrite.php
 *
 * Note: this class makes use of PHP sessions.  If your server does not support sessions,
 * then authentication will fail.
 *
 * @author Everett Griffiths everett@craftsmancoding.com
 */
class Eventbrite {
	/**
	 * Eventbrite API endpoint
	 *
	 * @package
	 */


	public $api_endpoint = 'https://www.eventbrite.com/json/';

	public $key_values = array();
	
	// For information about available API methods, see: http://developer.eventbrite.com/doc/
	/**
	 * This function is really what hits the API.
	 *
	 * @param string $method
	 * @param mixed $args
	 * @return mixed
	 */
	function __call( $method, $args ) {
		// Unpack our arguments
		if ( is_array( $args ) && array_key_exists( 0, $args ) && is_array( $args[0]) ) {
			$params = $args[0];
		}else {
			$params = array();
		}

		// Add authentication tokens to querystring
		if (!isset($this->auth_tokens['access_token'])) {
			$params = array_merge($params, $this->auth_tokens);
		}

		// Build our request url, urlencode querystring params
		$request_url = $this->api_url['scheme']."://".$this->api_url['host'].$this->api_url['path'].$method.'?'.http_build_query( $params, '', '&');

		// Call the API
		if (!isset($this->auth_tokens['access_token'])) {
			$resp = file_get_contents( $request_url );
		}else {
			$options = array(
				'http'=>array( 'method'=> 'GET',
					'header'=> "Authorization: Bearer " . $this->auth_tokens['access_token'])
			);
			$resp = file_get_contents( $request_url, false, stream_context_create($options));
		}

		// parse our response
		if ($resp) {
			$resp = json_decode($resp, true);
			//die(print_r($resp, true));

			if (isset($resp['error']['error_message']) ) {
				//throw new modxException( $resp->error->error_message ); // no need for this... no Events?  That's not
				// worthy of an error or exception.
				return $resp['error']['error_message'];
			}
		}
		return $resp;
	}


	/**
	 * Eventbrite API key (REQUIRED)
	 *    http://www.eventbrite.com/api/key/
	 * Eventbrite user_key (OPTIONAL, only needed for reading/writing private user data)
	 *     http://www.eventbrite.com/userkeyapi
	 *
	 * Alternate authorization parameters (instead of user_key):
	 *   Eventbrite user email
	 *   Eventbrite user password
	 *
	 * @param array $tokens   (optional)
	 * @param unknown $user     (optional)
	 * @param unknown $password (optional)
	 */
	function __construct( $tokens = null, $user = null, $password = null ) {
		$this->api_url = parse_url($this->api_endpoint);
		$this->auth_tokens = array();
		if (is_array($tokens)) {
			if (array_key_exists('access_code', $tokens)) {
				$this->auth_tokens = $this->oauth_handshake( $tokens );
			}else {
				$this->auth_tokens = $tokens;
			}
		}else {
			$this->auth_tokens['app_key'] = $tokens;
			if ( $password ) {
				$this->auth_tokens['user'] = $user;
				$this->auth_tokens['password'] = $password;
			}
			else {
				$this->auth_tokens['user_key'] = $user;
			}
		}
	}


	/**
	 *
	 *
	 * @param array $tokens
	 * @return unknown
	 */
	public function oauth_handshake( $tokens ) {
		$params = array(
			'grant_type'=>'authorization_code',
			'client_id'=> $tokens['app_key'],
			'client_secret'=> $tokens['client_secret'],
			'code'=> $tokens['access_code'] );

		$request_url = $this->api_url['scheme'] . "://" . $this->api_url['host'] . '/oauth/token';

		// TODO: Replace the cURL code with something a bit more modern -
		//$context = stream_context_create(array('http' => array(
		//    'method'  => 'POST',
		//    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		//    'content' => http_build_query($params))));
		//$json_data = file_get_contents( $request_url, false, $context );

		// CURL-POST implementation -
		// WARNING: This code may require you to install the php5-curl package
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_URL, $request_url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$json_data = curl_exec($ch);
		$resp_info = curl_getinfo($ch);
		curl_close($ch);

		$response = get_object_vars(json_decode($json_data));
		if ( !array_key_exists('access_token', $response) || array_key_exists('error', $response) ) {
			// throw new modxException( $response['error_description'] );
			global $modx;
			$modx->log(xPDO::LOG_LEVEL_ERROR, $response['error_description']);
		}
		return array_merge($tokens, $response);
	}

	
	/**
	 * Take a deeply nested array (i.e. a data stucture) and collapse it to simple key->value pairs
	 * so that it can be passed to a parsing function.
	 *
	 * @param array $array to be collapsed
	 * @param string $prefix the string to prepend bits of the array.
	 */
	public function collapse_array($array, $prefix='') {
		if (!is_array($array)) {
			return $array;
		}
		
		foreach($array as $k => $v) {
			// Is this node its own array?
			if (is_array($v)) {
				
			}
			else {
				$this->key_values[$prefix.$k] = $v;
			}
		}
	}

};
/*EOF*/