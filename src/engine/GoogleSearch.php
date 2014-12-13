<?php

namespace SerpScraper\Engine;

use SerpScraper\Exception\NotFoundException;
use SerpScraper\SearchEngine;
use SerpScraper\CaptchaSolver;
use SerpScraper\SearchResponse;

use GuzzleHttp\Exception\RequestException;

class GoogleSearch extends SearchEngine {

	private static $captcha_solver;
	private $domain = 'google.com';
	
	function __construct($cookie_dir = '/tmp/'){
		parent::__construct($cookie_dir);
	}
	
	private function getNextPage($html){
	
		if(preg_match('/<td class="b[^>]*>\s*<a[^>]+href="(.*?)"/', $html, $matches)){
			$url = 'https://www.'.$this->domain.''.$matches[1];			
			return htmlspecialchars_decode($url);
		}
		
		return false;
	}

	private function decodeLink($link){
		if(preg_match('/\\/url\\?q=(.*?)&amp;/', $link, $matches) == 1){
			$link = $matches[1];
		} else if(preg_match('/interstitial\\?url=(.*?)&amp;/', $link, $matches) == 1){
			$link = $matches[1];
		}
		
		$link = htmlspecialchars_decode($link);
		
		return rawurldecode($link);
	}
	
	function parseResults($raw_html){
	
		// must contain http because we don't want to match relative links as they're from google
		if(preg_match_all('/<h3 class="r"><a href="([^"]*http[^"]*)"/i', $raw_html, $matches) > 0){
		
			// depending on user agent, links returned are sometimes prefixed with /url?q= ... remove it
			$arr = @array_map(array($this, 'decodeLink'), $matches[1]);
			
			return $arr;
		}
		
		return array();
	}
	
	private function gen_url($query, $page){
	
		$vars = array(
			'q' => $query,
			'start' => ($page-1)*$this->results_per_page,
			'client' => 'navclient', // probably useless
			'gbv' => 1, // no javascript
			'complete' => 0, // 0 to disable instant search and enable more than 10 results
			'num' => $this->results_per_page, // number of results
			'pws' => 0, // do not personalize my search results
			'nfrpr' => 1, // do not auto correct my search queries
			'ie' => 'utf-8',
			'oe' => 'utf-8'
		);
		
		// do query building ourselves to get the url
		$url = 'http://www.'.$this->domain.'/search?'.http_build_query($vars, '', '&');
		
		return $url;
	}
	
	/*
	function unlock_403(){
	
		$url = "www.google.com/?gws_rd=ssl";
		
		if(rand(0, 1) == 1){
			$tb_url = "http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=84c3c1a78&features=Rank&q=info:{$url}";
		} else {
			$tb_url = "https://www.google.com/?gws_rd=ssl";
		}

		// will set a cookie
		try {
			$this->client->get($tb_url);
		} catch (Exception\RequestException $ex){
			
			echo $ex;
		}
	}
	*/
	
	// disables ALL country redirects - sets PREF cookie with options: FF=0:LD=en:CR=2
	function ncr(){
		$this->client->get('http://www.google.com/ncr');
	}
	
	function search($query, $page_num = 1){
		
		$url = $this->gen_url($query, $page_num);
		
		$sr = new SearchResponse();
		
		try {
		
			// fetch response
			$response = $this->client->get($url);
			$html = $response->getBody();
			
			$sr->page_html = $html;
			
			// extract urls
			$sr->results = $this->parseResults($html);
			
			// is there another page of results for this query?
			$sr->has_next_page = $this->getNextPage($html) == true;
		
		} catch (RequestException $ex){
			
			if($ex->hasResponse()){
				
				$response = $ex->getResponse();
				
				// status code and phrase
				$status_code = $response->getStatusCode();
				
				// captcha found!
				if($status_code == 503){
					$sr->error = 'captcha';
				} else {
					$sr->error = $status_code;
				}
				
			} else {
				// http timeout - host not found type errors...
				$sr->error = $ex->getMessage();
			}
		}

		return $sr;
	}
	
	public static function setCaptchaSolver(CaptchaSolver $captcha_solver){
		self::$captcha_solver = $captcha_solver;
	}
	
	private function getCaptchaBody(){
	
		$captcha_body = '';

		try {
			// exception will be thrown because status:503
			$this->client->get("http://ipv4.google.com/sorry/IndexRedirect?continue=".urlencode("http://www.google.com/search?q=google"));
		} catch (RequestException $ex){
			
			if($ex->hasResponse()){
				$captcha_body = $ex->getResponse()->getBody();
			}
		}
		
		return $captcha_body;
	}
	
	public function solveCaptcha(){
		
		// do we have a valid captcha solver to use?
		if(!self::$captcha_solver){
			throw new NotFoundException('Resource: CaptchaSolver was not found!');
		}
		
		// get HTML
		$body = $this->getCaptchaBody();
		
		// parse and solve image captcha
		if(preg_match('/image\\?id=(\\d+)&amp;/', $body, $matches) && preg_match('/name="continue" value="([^"]+)/', $body, $matches2)){
		
			$id = $matches[1];
			$img_url = "http://ipv4.google.com/sorry/image?id={$id}&hl=en";
			
			$continue = $matches2[1];
			
			try {
			
				// download captcha image
				$res = $this->client->get($img_url);
				$img_bytes = $res->getBody();
				
				// read text from image
				$text = self::$captcha_solver->decode($img_bytes);
				
				$vars = array(
					'continue' => $continue,
					'id' => $id,
					'captcha' => $text,
					'submit' => 'Submit'
				);
				
				// submit form - exception will be thrown if http status not valid
				$req = $this->client->get('http://ipv4.google.com/sorry/CaptchaRedirect?'.http_build_query($vars));
			
				var_dump($req->getHeaders());
				var_dump($req->getEffectiveURL());
			
			} catch (RequestException $ex){
				
				
				echo $ex->getMessage();
				
				// can throw both 5xx and 4xx
				return false;
			}
		}
	}
}

?>