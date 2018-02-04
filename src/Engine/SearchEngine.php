<?php

namespace SerpScraper\Engine;

use SerpScraper\Curl;

abstract class SearchEngine {
	
	protected $client;
	protected $preferences = array();
	
	// default request options to be used with each client request
	protected $default_options = array();
	
	function __construct(){
		
		// we use it!
		$this->client = new Curl();
		
		// where should we store the cookies for this search client instance? get_current_user()
		$this->client->setCookieDir(sys_get_temp_dir());
		
		$headers = array(
			'Accept' => '*/*',
			'Accept-Encoding' => 'gzip, deflate',
			'Connection' => 'Keep-Alive'
		);
		
		// let's put some timeouts in case of slow proxies
		$curl = array(
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT => 15,
			// sometimes google routes the connection through IPv6 which just makes this more difficult to deal with - force it to always use IPv4
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
		);
		
		$this->client->setHeader($headers);
		$this->client->setCurlOption($curl);
	}
	
	public function setProxy($proxy){
		$this->client->setProxy($proxy);
	}
	
	public abstract function search($query, $page_num);
	
	public function setPreference($name, $value){
		$this->preferences[$name] = $value;
	}

}

?>