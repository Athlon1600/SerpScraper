<?php

namespace SerpScraper\Engine;

use SerpScraper\SearchEngine;
use SerpScraper\SearchResponse;

use GuzzleHttp\Exception\RequestException;

class GoogleSearch extends SearchEngine {
	
	function __construct(){
		parent::__construct();
		
		// sometimes google routes the connection through IPv6 which just makes this more difficult to deal with - force it to always use IPv4
		$this->client->setDefaultOption('config/curl', array(
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
		));
		
		$this->preferences['results_per_page'] = 100;
		$this->preferences['google_domain'] = 'google.com';
	}
	
	private function getNextPage($html){
	
		if(preg_match('/<td class="b[^>]*>\s*<a[^>]+href="(.*?)"/', $html, $matches)){
			$url = 'https://www.'.$this->preferences['google_domain'].''.$matches[1];			
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
	
	private function extractResults($raw_html){
	
		// must contain http otherwise it's a relative link to google which we're not interested in
		if(preg_match_all('/<h3 class="r"><a href="([^"]*http[^"]*)"/i', $raw_html, $matches) > 0){
		
			// depending on user agent, links returned are sometimes prefixed with /url?q= ... remove it
			$arr = @array_map(array($this, 'decodeLink'), $matches[1]);
			
			return $arr;
		}
		
		return array();
	}
	
	private function prepare_url($query, $page){
	
		// idea... do this per class and use pref_ prefix
		extract($this->preferences);
		
		$vars = array(
			'q' => $query,
			'start' => ($page-1)*$results_per_page,
			'client' => 'navclient', // probably useless
			'gbv' => 1, // no javascript
			'complete' => 0, // 0 to disable instant search and enable more than 10 results
			'num' => $results_per_page, // number of results
			'pws' => 0, // do not personalize my search results
			'nfrpr' => 1, // do not auto correct my search queries
			'ie' => 'utf-8',
			'oe' => 'utf-8'
		);
		
		// do query building ourselves to get the url
		$url = 'http://www.'.$google_domain.'/search?'.http_build_query($vars, '', '&');
		
		return $url;
	}
	
	// visits a special URL that disables ALL country redirects by setting a PREF cookie with options: FF=0:LD=en:CR=2
	function ncr(){
		$this->client->get('http://www.google.com/ncr');
	}
	 
	function search($query, $page_num = 1){
	
		$url = $this->prepare_url($query, $page_num);
		
		$sr = new SearchResponse();
		
		try {
		
			// fetch response
			$response = $this->client->get($url);
			$html = $response->getBody();
			
			$sr->html = $html;
			
			// extract urls
			$sr->results = $this->extractResults($html);
			
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
	
	public function solveCaptcha(\CaptchaSolver $solver){
		
		$captcha_html = $this->client->get('http://ipv4.google.com/sorry/IndexRedirect', array('exceptions' => false));
		
		// extract form values for submission
		if(preg_match('/image\\?id=(\\d+)&amp;/', $captcha_html, $matches) && preg_match('/name="continue" value="([^"]+)/', $captcha_html, $matches2)){
		
			$id = $matches[1];
			$img_url = "http://ipv4.google.com/sorry/image?id={$id}&hl=en";
			
			$continue = $matches2[1];
			
			// download captcha image
			$response = $this->client->get($img_url, array('exceptions' => false));
			$img_bytes = $response->getBody();
			
			// read text from image
			$text = $solver->solve($img_bytes);
			
			$vars = array(
				'continue' => $continue,
				'id' => $id,
				'captcha' => $text,
				'submit' => 'Submit'
			);
			
			// submit form... hopefully this will set a cookie that will let you search again without throwing captcha
			$response = $this->client->get('http://ipv4.google.com/sorry/CaptchaRedirect?'.http_build_query($vars));
			
			return $response->getStatusCode() == 200;
		}
		
		return false;
	}
}

?>