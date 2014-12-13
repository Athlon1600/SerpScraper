<?php

namespace SerpScraper;

use SerpScraper\SearchEngine;
use SerpScraper\Exception\LimitException;

class SearchEngineScraper {
	
	// search engine instance
	private $search_engine;
	
	// how long to wait between requests
	private $min_wait = 1;
	private $max_wait = 4;
	
	// absolute maximum amount of times a search engine will be queried for results
	private $max_query_count = 15;
	private $max_retry_count = 2;
	
	// data collected so far
	private $serp_urls = array('dummy string pos => 0');
	private $serp_html = array();
	
	// custom vars from user
	private $success_callback;
	private $error_callback;
	
	// init this
	function __construct(SearchEngine $search_engine){
		$this->search_engine = $search_engine;
	}
	
	function scrape($keyword, $max_results = 50){
	
		// scraping ends based on these variables
		$page_num = 1;
		$has_next_page = false;
		
		// maintain counters
		$query_count = 0;
		$retry_count = 0;
		
		do {
		
			$response = $this->search_engine->search($keyword, $page_num);
			$query_count++;
			
			if(!$response->error){
			
				// reset retry counter upon each successful query
				$retry_count = 0;
				
				$results = $response->results;
				$html = $response->page_html;
				
				// append to list
				$this->serp_urls = array_merge($this->serp_urls, $results);
				$this->serp_html[$page_num] = $html;
				
				// call the function, send the results with proper positions as indexes
				// for page 2 = array(11 => url, 12 => url)...
				if(is_callable($this->success_callback)){
					$count = count($results);
					$results_pos = array_slice($this->serp_urls, -1 * $count, $count, true);
					call_user_func_array($this->success_callback, array($results_pos));
				}
				
				// if has next page then continue
				$has_next_page = $response->has_next_page;
				
				if($has_next_page){
					$page_num++;
				}
				
			} else {
				
				// notify of error if desired
				if(is_callable($this->error_callback)){
					call_user_func_array($this->error_callback, array($response->error));
				}
				
				$retry_count++;
			}
			
			// sleep a little
			$this->sleepRand($this->min_wait, $this->max_wait);
			
		} while(
		count($this->serp_urls) < ($max_results * 0.95) && 
		$has_next_page && 
		$retry_count <= $this->max_retry_count && 
		$query_count <= $this->max_query_count
		);
		
		// what has caused us to break from the loop?
		if($retry_count > $this->max_retry_count){
			throw new LimitException("Too Many retries. The limit of ".$this->max_retry_count."	was exceeded.");
		}
		
		if($query_count > $this->max_query_count){
			throw new LimitException("Too many queries. The limit of ".$this->max_query_count." was exceeded");
		}
		
		// if here than that implies that everything went fine?
		// value at 0 is a dummy string therefore results start at pos 1
		unset($this->serp_urls[0]);
		
		return array('urls' => $this->serp_urls, 'html' => $this->serp_html);
	}
	
	function setWaitInterval($min, $max){
		$this->min_wait = min($min, $max);
		$this->max_wait = max($min, $max);
	}
	
	function setSuccessCallback($callback){
		if(is_callable($callback)){
			$this->success_callback = $callback;
		}
	}
	
	function setErrorCallback($callback){
		if(is_callable($callback)){
			$this->error_callback = $callback;
		}
	}
	
	private function sleepRand($min, $max){
		$rand = mt_rand($min, $max);
		return sleep($rand);
	}
}

?>