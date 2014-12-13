<?php

namespace SerpScraper\Engine;

use SerpScraper\SearchEngine;

class BingSearch extends SearchEngine {

	private $rpp_allowed = array(10, 15, 30, 50);
	
	function __construct(){
		parent::__construct();
	}
	
	// en-us, en-gb, it-IT, ru-RU...
	function setMarket($search_market){
	
		try {
		
			$body = $this->client->get("http://www.bing.com/account/worldwide")->getBody();
			
			if(preg_match('/<a href="([^"]*setmkt='.$search_market.'[^"]*)"/i', $body, $matches)){

				$url = htmlspecialchars_decode($matches[1]);
				
				// this will set the session cookie
				$this->client->get($url);
			}
		
		} catch (RequestException $ex){
			// do nothing
		}
	}
	
	
	function setResultsPerPage($num){
	
		if(!in_array($num, $this->rpp_allowed)){
			throw new InvalidArgumentException('Invalid number!');
		}
	
		try {
		
			// open up the bing options page
			$html_form = $this->client->get("http://www.bing.com/account/web")->getBody();
			
			// parse various session values from that page
			preg_match_all('/<input[^>]*name="\b(guid|sid|ru|uid)\b"[^>]*value="(.*?)"/i', $html_form, $matches, PREG_SET_ORDER);
			
			if($matches){
				
				// change some of them
				$options = array(
					'rpp'		=> $num,
					'pref_sbmt'	=> 1,
				);
				
				foreach($matches as $match){
					$options[$match[1]] = $match[2];
				}
				
				// submit the form and get the cookie that determines the number of results per page
				$this->client->get("http://www.bing.com/account/web", array('query' => $options), array());
			}
		
		} catch (RequestException $ex){
			// do nothing?
		}
		
		// call parent to update
		parent::setResultsPerPage($num);
		
		return true;
	}
	

	function parseResults($html){
	
		// ads ID=SERP,5417.1,Ads	ID=SERP,5106.1
		// bing local ID=SERP,5079.1 
		// bing local ID=SERP,5486.1
		
		// news ID=SERP,5371.1
		
		// result ID=SERP,5167.1
		// result ID=SERP,5151.1	
		
		preg_match_all('/<h3><a href="([^"]+)" h="ID=SERP,[0-9]{4}\.1"/', $html, $matches);
		
		return $matches ? $matches[1] : array();
	}
	
	function search($query, $page = 1){
		
		$sr = new SearchResponse();
		$start = ($page-1) * $this->results_per_page + 1;
		
		try {
		
			$response = $this->client->get("http://www.bing.com/search?q={$query}&first={$start}");
			// get HTML body
			$body = $response->getBody();
			$sr->raw_html = $body;
			
			$sr->results = $this->parseResults($body);
			
			$sr->has_next_page = strpos($body, "\"sw_next\">Next") !== false;
		
		} catch (RequestException $ex){
			$sr->error = $ex->getMessage();
		}
		
		return $sr;
	}
}
?>