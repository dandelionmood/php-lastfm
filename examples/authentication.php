<?php
/**
	* This small Slim application will show you how to *authenticate*
	* a connected user with the Last.fm API.
	*
	* As you can see in the first lines, you'll need to set two constants
	* containing the API key and secret to make it work.
*/

use \Dandelionmood\LastFm\LastFm;
use \Slim\Slim;

$app = new Slim();

/**
 * First page to call, it will redirect the user to Last.fm to
 * let him authorize the application.
*/
$app->get('/auth/connect', function() use($app) {
	$lastfm = new LastFm( LASTFM_API_KEY, LASTFM_API_SECRET );
	
	// We need to compute a callback URL that will be called when the user
	// allows the application.
	$callback_url = $lastfm->auth_get_url(
		$app->request()->getUrl()
			.$app->urlFor('auth_callback')
	);
	
	$app->redirect( $callback_url );
});

/**
	* Second page that will be called by Last.fm. The token to get a session
	* will be given through a GET variable.
*/
$app->get('/auth/callback', function() use($app) {
	
	$lastfm = new LastFm( LASTFM_API_KEY, LASTFM_API_SECRET );
	$token = $app->request()->get('token');
	
	try {	
		// We try to get a session using the token we're given
		$session = $lastfm->auth_get_session( $token );
		// Once we get the session, we'll try to use it to shout on a wall,
		// see below !
		$app->redirect(
			$app->urlFor('shout', array('session_key' => $session->session->key))
		);
	} catch( Exception $e ) {
		echo "Sorry, something went wrong : ".$e->getMessage();
	}
	
})->name('auth_callback');

/**
	* Final page, at this stage, we now the session key that could be
	* put in $_SESSION or wherever you want to.
*/
$app->get('/shout/:session_key', function($session_key) use($app) {

	// This time, note that we pass a third parameter, which is the session
	// key. This will allow us to call methods that need authentication.
	$lastfm = new LastFm( LASTFM_API_KEY, LASTFM_API_SECRET, $session_key );
	
	// We try to publish something on my wall.
	// Note the «true» in the last parameter, this tells the class that it's
	// a call that need authentication (session_key + signature are added).
	$r = $lastfm->user_shout(array(
		'user' => 'dandelionmood',
		'message' => "I just installed your Last.fm API wrapper :) !"
	), true);
	
	// We print a message to let know everything worked out allright !
	echo "A message has been successfully posted to 'dandelionmood' wall :) !<br/>";
	echo '<code>'.print_r($r, true).'</code>';
	
})->name('shout');

$app->run();