<?php
use \Dandelionmood\LastFm\LastFm;

use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
	* A simple test to see if the minimum is working
*/
class LastFmTest extends \PHPUnit\Framework\TestCase
{
	public function testInitialisation()
	{
		$r = $this->_getAlbumInfo();
		$this->assertObjectHasAttribute( 'album', $r );
	}

	public function testBuzzClientSwitch()
	{
		$lfm = $this->_lfm();
		$lfm->set_http_client(new Curl(new Psr17Factory()));
		$r = $this->_getAlbumInfo();
		$this->assertObjectHasAttribute( 'album', $r );
	}

	public function testBuzzClientTimeoutChange()
	{
		$lfm = $this->_lfm();
		$lfm->set_http_request_options(['timeout' => 1]);
		$r = $this->_getAlbumInfo();
		$this->assertObjectHasAttribute( 'album', $r );
	}

	private function _getAlbumInfo()
	{
		return $this->_lfm()->album_getInfo(array(
			'artist' => 'cher',
			'album' => 'believe'
		));
	}

	private function _lfm()
	{
		return new LastFm(
			'YOUR API KEY',
			'YOUR API SECRET'
		);
	}
}
