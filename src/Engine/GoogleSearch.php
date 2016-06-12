<?php

namespace SerpScraper\Engine;

use SerpScraper\Engine\SearchEngine;
use SerpScraper\SearchResponse;
use SerpScraper\Captcha\CaptchaSolver;
use GuzzleHttp\Exception\RequestException;

class GoogleSearch extends SearchEngine {

	private $last_captcha_url = '';
	
	function __construct(){
		parent::__construct();
		
		// sometimes google routes the connection through IPv6 which just makes this more difficult to deal with - force it to always use IPv4
		$this->client->setDefaultOption('config/curl', array(
			CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
		));
		
		// Set default preferences
		$this->preferences = array(
			'results_per_page' => 100,
			'google_domain' => 'google.com',
			'hl' => 'en', // web interface language code
			'lr' => 'lang_en', // search language code
			'ie' => 'utf-8', // character encoding scheme for query string.
			'oe' => 'utf-8', // character encoding scheme decode XML results
			'gbv' => 1, // no javascript
			'pws' => 0, // do not personalize my search results
			'nfrpr' => 1, // do not auto correct my search queries
			'complete' => 0, // 0 to disable instant search and enable more than 10 results
		);

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
			'gbv' => $gbv, // no javascript
			'complete' => $complete, // 0 to disable instant search and enable more than 10 results
			'num' => $results_per_page, // number of results
			'pws' => $pws, // do not personalize my search results
			'nfrpr' => $nfrpr, // do not auto correct my search queries
			'ie' => $ie, // character encoding scheme for query string.
			'oe' => $oe, // character encoding scheme decode XML results
			'hl' => $hl, // web interface language code
			'lr' => $lr // search language code
		);
		
		if(isset($this->preferences['date_range'])){
		
			$str = substr($this->preferences['date_range'], 0, 1);
			
			if(in_array($str, array('h', 'd', 'w', 'm', 'y'))){
				$vars['tbs'] = 'qdr:'.$str;
			}
		}
		
		// do query building ourselves to get the url
		$url = 'http://www.'.$google_domain.'/search?'.http_build_query($vars, '', '&');
		
		return $url;
	}
	
	// visits a special URL that disables ALL country redirects 
	function ncr(){
	
		// check if /ncr cookie has already been set
		$ncr = false;
		
		// the cookie we're looking for should have these options: FF=0:LD=en:CR=2
		$cookies = $this->client->getDefaultOption('cookies')->toArray();
		
		foreach($cookies as $cookie){
			
			if($cookie['Domain'] == '.google.com' && $cookie['Name'] == 'PREF' && strpos($cookie['Value'], 'CR=2') !== false){
				$ncr = true;
				break;
			}
		}
		
		if(!$ncr){
			$this->client->get('http://www.google.com/ncr');
		}
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
					$this->last_captcha_url = $response->getEffectiveUrl();
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
	
	public function solveCaptcha(CaptchaSolver $solver){
		
		// once request is made, a new LAST_CAPTCHA_URL must be generated for it to work
		if(!$this->last_captcha_url){
			return false;
		}
		
		$captcha_html = $this->client->get($this->last_captcha_url, array('exceptions' => false));
		
		// TODO: check to make sure we're really on a captcha page
		
		// extract form values for submission
		if(preg_match('/<img src="([^"]+)"/', $captcha_html, $matches)){
		
			// assumine PROTOCOL and HOST stay the same
			$img_url = "http://ipv4.google.com/".htmlspecialchars_decode($matches[1]);
			
			// extract additional data from image query string
			$query = parse_url($img_url, PHP_URL_QUERY);
			
			$vars = array();
			parse_str($query, $vars);
			
			// download captcha image
			$response = $this->client->get($img_url, array('exceptions' => false));
			
			// get raw bytes
			$img_bytes = $response->getBody();
			
			// read text from image
			$text = $solver->solve($img_bytes);
			
			// form data
			$data = array(
				'q' => $vars['q'],
				'continue' => $vars['continue'],
				'id' => $vars['id'],
				'captcha' => $text,
				'submit' => 'Submit'
			);
			
			// submit form... hopefully this will set a cookie that will let you search again without throwing captcha
			// GOOGLE_ABUSE_EXEMPTION lasts 3 hours
			$response = $this->client->get('http://ipv4.google.com/sorry/CaptchaRedirect?'.http_build_query($data), array('exceptions' => false));
		
			return $response->getStatusCode() == 200;
		}
		
		return false;
	}
}

?>
