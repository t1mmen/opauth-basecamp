<?php
/**
 * Basecamp strategy for Opauth
 *
 * Based on work by U-Zyn Chua (http://uzyn.com)
 *
 * More information on Opauth: http://opauth.org
 *
 * @copyright    Copyright © 2014 Timm Stokke (http://timm.stokke.me)
 * @link         http://opauth.org
 * @package      Opauth.BasecampStrategy
 * @license      MIT License
 */

/**
 * Basecamp strategy for Opauth
 *
 * @package			Opauth.Basecamp
 */
class BasecampStrategy extends OpauthStrategy {

	/**
	 * Compulsory config keys, listed as unassociative arrays
	 */
	public $expects = array('client_id', 'client_secret');

	/**
	 * Optional config keys, without predefining any default values.
	 */
	public $optionals = array('redirect_uri', 'scope', 'state');

	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}oauth2callback'
	);

	/**
	 * Auth request
	 */
	public function request() {
		$url = 'https://launchpad.37signals.com/authorization/new';
		$params = array(
			'type' => 'web_server',
			'client_id' => $this->strategy['client_id'],
			'redirect_uri' => $this->strategy['redirect_uri']
		);

		foreach ($this->optionals as $key) {
			if (!empty($this->strategy[$key])) $params[$key] = $this->strategy[$key];
		}

		$this->clientGet($url, $params);
	}

	/**
	 * Internal callback, after OAuth
	 */
	public function oauth2callback() {
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])) {
			$code = $_GET['code'];
			$url = 'https://launchpad.37signals.com/authorization/token';

			$params = array(
				'type' => 'web_server',
				'code' => $code,
				'client_id' => $this->strategy['client_id'],
				'client_secret' => $this->strategy['client_secret'],
				'redirect_uri' => $this->strategy['redirect_uri'],
			);
			if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];

			$response = $this->serverPost($url, $params, null, $headers);
			$results = json_decode($response, true);

			if (!empty($results) && !empty($results['access_token'])) {
				$user = $this->user($results['access_token']);

				$this->auth = array(
					'uid' => $user['identity']['id'],
					'info' => array(
						'name' => $user['identity']['first_name'].' '.$user['identity']['last_name']
						),
					'credentials' => array(
						'token' => $results['access_token'],
						'refresh_token' =>  $results['refresh_token'],
						'expires_in' =>  $results['expires_in']
					),
					'raw' => $user
				);

				$this->mapProfile($user, 'identity.first_name', 'info.first_name'); // look into setting full name here
				$this->mapProfile($user, 'identity.last_name', 'info.last_name');
				$this->mapProfile($user, 'identity.email_address', 'info.email');

				// Associated accounts:
				foreach ($user['accounts'] as $k => $account) {
					$this->mapProfile($user, 'accounts.'.$k.'.href', 'info.urls.'.$account['product'].'-'.$account['id']);
				}

				$this->callback();
			}
			else {
				$error = array(
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => array(
						'response' => $response,
						'headers' => $headers
					)
				);

				$this->errorCallback($error);
			}
		}
		else {
			$error = array(
				'code' => 'oauth2callback_error',
				'raw' => $_GET
			);

			$this->errorCallback($error);
		}
	}

	/**
	 * Queries Basecamp API for user info
	 *
	 * @param string $access_token
	 * @return array Parsed JSON results
	 */
	private function user($access_token) {
		$user = $this->serverGet('https://launchpad.37signals.com/authorization.json', array('access_token' => $access_token), null, $headers);

		if (!empty($user)) {
			return $this->recursiveGetObjectVars(json_decode($user));
		}
		else {
			$error = array(
				'code' => 'userinfo_error',
				'message' => 'Failed when attempting to query Basecamp API for user information',
				'raw' => array(
					'response' => $user,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
}
