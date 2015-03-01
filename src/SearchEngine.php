<?php

namespace SerpScraper;

use SerpScraper\Engine\GoogleSearch;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;

abstract class SearchEngine {

	public $client;
	
	// let the SearchEngine class handle everything about profiles
	private $profile_id;
	
	// cookie stuff
	private $cookie_prefix = 'se_cookie_';
	private $cookie_dir = '';
	
	protected $preferences = array();
	
	protected $agents = array(
		"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0.1",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_4) AppleWebKit/534.57.2 (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2",
		"Opera/9.80 (Windows NT 5.1; U) Presto/2.10.229 Version/11.60",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)",
		"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
		"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)",
		"Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6",
		"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
		"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)",
		"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)"
	);
	
	function __construct(){
	
		// init guzzle client!
		$this->client = new Client();
		
		// request options
		$options = array();

		// user-agent will be set by setProfileID
		$options['headers'] = array(
			'Accept' => '*/*',
			'Accept-Encoding' => 'gzip, deflate',
			'Connection' => 'Keep-Alive'
		);
		
		// init options
		foreach($options as $key => $val){
			$this->client->setDefaultOption($key, $val);
		}
		
		$this->setCookieDir(sys_get_temp_dir());
		
		// this will create an empty cookie
		$this->setProfileID('default');
	}
	
	public abstract function search($keywords, $page_num);
	
	public function setPreference($name, $value){
		$this->preferences[$name] = $value;
	}
	
	// proxy must be in username:password@IP:Port format
	final public function setProxy($proxy, $new_profile = true){
		
		$this->client->setDefaultOption('proxy', $proxy_str);
		
		// do we want to use a different cookie profile for this proxy?
		if($new_profile){
			$this->setProfileID($proxy['host']);
		}
	}
	
	final public function disableProxy(){
		$this->client->setDefaultOption('proxy', false);
	}
	
	final public function setCookieDir($cookie_dir){
	
		// validate cookie dir
		if(!is_dir($cookie_dir)){
			throw new \InvalidArgumentException('Cookie directory is invalid or non-existant!');
		} else if(!is_writable($cookie_dir)){
			throw new \InvalidArgumentException('Cookie directory: '.$cookie_dir.' is not writable! Chmod it to 777 and try again.');
		}
		
		// cookie_dir is valid?
		$this->cookie_dir = $cookie_dir;
	}
	
	// each profile uses different cookie file and user-agent
	final public function setProfileID($id){
		$this->profile_id = $id;
		
		// generate random user agent using profile_id as salt
		$hash = md5($id);
		$hash = substr($hash, 0, 8);
		
		$rand_index = hexdec($hash) % count($this->agents);

		// set it
		$agent = $this->agents[$rand_index];
		$this->client->setDefaultOption('headers/User-Agent', $agent); 
		
		// generate cookie file based on profile_id
		$cookie_file = $this->cookie_dir.$this->cookie_prefix.$this->profile_id.'.json';
		
		// cookies will be stored here
		$jar = new FileCookieJar($cookie_file);
		
		$this->client->setDefaultOption('cookies', $jar);
	}
}

?>