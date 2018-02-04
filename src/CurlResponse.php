<?php

namespace SerpScraper;

class CurlResponse {
	
	private $headers;
	private $body;
	private $curl_info;
	
	public function __construct($headers = array(), $body = '', $curl_info = array() ){
		$this->headers = $headers;
		$this->body = $body;
		$this->curl_info = $curl_info;
	}
	
	public function __toString(){
		return $this->body;
	}
	
	public function getStatusCode(){
		return $this->curl_info['http_code'];
	}
	
	public function getCurlInfo(){
		return $this->curl_info;
	}
	
	public function getBody(){
		return $this->body;
	}
}

?>