<?php
use \Dandelionmood\LastFm\LastFm;

/**
	* A simple test to see if the minimum is working
*/
class LastFmTest extends \PHPUnit_Framework_TestCase
{
	public function testInitialisation()
	{
		$r = $this->_lfm()->album_getShouts(array(
			'artist' => 'cher',
			'album' => 'believe'
		));
		$this->assertObjectHasAttribute( 'shouts', $r );
	}
	
	public function _lfm()
	{
		return new LastFm(
			'your api key',
			'your api secret'
		);
	}
}