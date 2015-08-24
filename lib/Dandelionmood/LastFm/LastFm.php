<?php
namespace Dandelionmood\LastFm;

use \Buzz\Browser;

/**
	* Dead-simple LastFm API wrapper class.
	* This class allows you to connect to the Last.fm API with ease.
	* 
	* The idea is that once you'll have understood how this class works, you
	* should be able to use Last.fm documentation directly to do whatever you
	* want … Hopefully ;) !
	*
	* You can make both standard and authenticated calls, see
	* {@link self::auth_get_url()} and {@link self::auth_get_session()}
	* to get a session.
	*
	* @see http://www.lastfm.fr/api
*/
class LastFm
{
	protected $_api_key = null;
	protected $_api_secret = null;
	protected $_session_key = null;
	
	const API_URL = 'http://ws.audioscrobbler.com/2.0/';
	const AUTH_URL = 'http://www.last.fm/api/auth/';
	
	/**
		* The API requires at last an api_key — if you want to gain access to
		* protected methods (requiring session or a api_sig), you'll have to
		* specify an api_secret as well.
		*
		* See here to get those :
		* @link http://www.lastfm.fr/api/accounts
		* 
		* @param string API Key
		* @param string API Secret (optionnal but required for authenticated calls)
		* @param string Session key (optional but again required for
		* 	authenticated calls)
	*/
	public function __construct( $api_key, $api_secret = null,
		$session_key = null )
	{
		$this->_api_key = $api_key;
		$this->_api_secret = $api_secret;
		$this->_session_key = $session_key;
	}
	
	/**
		* This is the *first* step of the authentication process.
		* @see http://www.lastfm.fr/api/webauth
		*
		* @param string callback URL on your website that will be called by Last.fm
		* @return string URL to use in a popup or an iframe.
	*/
	public function auth_get_url( $callback_url )
	{
		$url = self::AUTH_URL.'?'.http_build_query(array(
			'api_key' => $this->_api_key,
			'cb' => $callback_url
		));
		
		return $url;
	}
	
	/**
		* *Second* step of the authentication process.
		* @see http://www.lastfm.fr/api/webauth
		*
		* @param string the token you were given through $_GET in the callback page.
		* @return object the session object
	*/
	public function auth_get_session( $token )
	{
		return $this->auth_getSession(
			array(
				'token' => $token
			),
			true
		);
	}
	
	
	/**
		* The magic happens here : as the Last.fm API is very well thought-of,
		* this method can infer what method you're trying to get and transmit
		* the parameters to {@link _make_request()}.
		*
		* This method is not meant to be called directly, but PHP will use
		* it if you're trying to call a method which is not defined here.
		*
		* @param string method name (replace «.» by «_»).
		* @param array parameters of the method.
		* @return the result of {@link self::_make_request()}
	*/
	public function __call( $method, $parameters = array() )
	{
		$method = str_replace('_','.',$method);
		$params = isset($parameters[0]) ? $parameters[0] : array();
		$do_request_auth = isset($parameters[1]) ? $parameters[1] : false;
		return $this->_make_request( $method, $params, $do_request_auth );
	}
	
	/**
		* Performs a request to the the API in JSON
		* @param string Last.fm method name
		* @param array parameters that will be added (optional)
		* @param boolean does the request needs to be authenticated ?
		* 	(default false)
		* @return object depends on what you asked for.
		* @throws Exception if something goes wrong.
	*/
	private function _make_request( $method, $parameters = array(),
		$do_request_auth = false )
	{
		// We automatically append a few parameters here.
		$parameters = array_merge(array(
			'method' => $method,
			'format' => 'json',
			'api_key' => $this->_api_key
		), $parameters);
		
		// Do we need to authenticate the request ?
		if( $do_request_auth ) {
			
			// We add the session key if it's been given
			if( !empty($this->_session_key) ) 
				$parameters['sk'] = $this->_session_key;
			
			// Known bug : you have to get rid of format parameter to compute
			// the api_sig parameter.
			// http://www.lastfm.fr/group/Last.fm+Web+Services/forum/21604/_/428269/1#f18907544
			$parameters_without_format = $parameters;
			unset($parameters_without_format['format']);
			
			// What follows is well-documented here :
			// http://www.lastfm.fr/api/webauth#6
			ksort($parameters_without_format);
			$signature = '';
			foreach( $parameters_without_format as $k => $v ) $signature .= "$k$v";
			$parameters['api_sig'] = md5($signature.$this->_api_secret);
			
		}
		
		// We have everything we need, let's query the API
		$browser = new Browser();
		$response = $browser->post(
			self::API_URL,
			array('content-type' => 'application/x-www-form-urlencoded'),
			http_build_query( $parameters )
		);
		$json = json_decode( $response->getContent() );
		
		// The JSON couldn't be decoded …
		if( $json === NULL )
			throw new \Exception("JSON response seems incorrect.");
		
		// An error has occurred …
		if( !empty($json->error) )
			throw new \Exception("[{$json->error}|{$json->message}] "
				.implode(', ', $json->links)."\n".http_build_query( $parameters ));
		
		return $json;
	}
}
