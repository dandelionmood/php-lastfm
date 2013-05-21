# Last.fm API PHP Wrapper

## Introduction

When I looked for an API to work with Last.fm API, I could only find either
incomplete implementations or very complicated ones. I love simple things
so I decided to take my chance and design a simple class that could do
everything in the most simple way.

*The key idea is that the [official documentation](http://www.lastfm.fr/api)
is very good, and you shoudn't need anything else to work with the API.*

## Installation

You should install it through Composer / Packagist, because …
[Well, it's awesome](http://getcomposer.org/doc/00-intro.md) !

The package is available
[here on Packagist](https://packagist.org/packages/dandelionmood/lastfm).

## Standard methods (no authentication needed)

Let's dive into the meat of this project. First, you need to
[register your application](http://www.lastfm.fr/api/accounts)
to key an API key and secret.

Once it's done, here's how you get an instance to work with :

```php
// The secret is only needed if you want to access authenticated methods
$lastfm = new \Dandelionmood\LastFm\LastFm( $lastfm_api_key, $lastfm_api_secret );
```

Now let's say you want to get info on a given artist ? If you look into the
API documentation, you can find the method ```artist.getInfo``` that will
give us what we need ([see here](http://www.lastfm.fr/api/show/artist.getInfo)).

```php
// Note that the dot is replaced by an underscore in the PHP API.
$pink_floyd = $lastfm->artist_getInfo(
	array(
		'artist' => 'Pink Floyd'
	)
);
```

What you'll get in return is a standard PHP Object.

## Authenticated methods

Some methods requires you to authenticate the user first. The PHP API gives
you two methods to do this. This is very similar to OAuth and OpenID
authentication, so if you've every implemented it before, you should feel
right at home.

### Authentication

Please look in the ```examples/authentication.php``` file to find
a Slim application implementing it. I will use portions of this file
here to guide you step by step.

First, we need to ask the user to allow our application, this is handled by
Last.fm ; they need to know what URL to call when the user says yes :

```php
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
```

Secondly, we need to handle the callback URL that will be called when the
user validates the form :

```php
$app->get('/auth/callback', function() use($app) {
	
	$lastfm = new LastFm( LASTFM_API_KEY, LASTFM_API_SECRET );
	$token = $app->request()->get('token');
	
	try {	
		// We try to get a session using the token we're given
		$session = $lastfm->auth_get_session( $token );
		echo "Yes ! The session key is : $session->session->key";
	} catch( Exception $e ) {
		echo "Sorry, something went wrong : ".$e->getMessage();
	}
	
})->name('auth_callback');
```

I decided to print out the session key, but you should keep it in a database
or the ```$_SESSION``` variable : YMMV …

### Authenticated methods

The user is now authenticated, we now know its ```session_key``` ; we
can use it as a third parameter when calling the constructor.

```php
$lastfm = new LastFm( LASTFM_API_KEY, LASTFM_API_SECRET, $session_key );
```

Here's a simple authenticated method that takes the ```session_id``` in the URL
and posts a message on my wall :

```php
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
```

You need to add a third parameter when calling the ```shout()``` function to let
the API know it's an authenticated call.

## Last words

You can generate the class documentation using the ```./apigen``` command.

The unit tests are very scarce at the moment, but they should work if you
specify your own api key and secret, look in ```tests/LastFmTest.php```. You
can launch them by calling the ```./phpunit``` command/