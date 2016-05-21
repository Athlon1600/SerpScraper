<?php

namespace SerpScraper\Captcha;

use SerpScraper\Captcha\CaptchaSolver;

class DeathByCaptcha implements CaptchaSolver {

	private $client;
	
	function __construct($username, $password){
		$this->client = new \DeathByCaptcha_SocketClient($username, $password);
	}
	
	function solve($bytes){

		// let's create a random file somewhere
		$file = tempnam(sys_get_temp_dir(), 'captcha_');
		
		// write bytes to that file
		file_put_contents($file, $bytes);
		
		// dbc has function for decoding files already
		$response = $this->client->decode($file);
		
		// remove temp file
		unlink($file);
		
		return $response['text'];
	}
}

?>