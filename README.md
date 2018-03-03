SerpScraper
===========

###  --- reCAPTCHA V2 -- ~~Feb 10, 2018~~ -- Fixed on March 3, 2018


~~Google Search no longer uses its image-based captcha.~~  
~~It has now moved on to its new reCAPTCHA v2 which makes it very difficult for robots and scripts to bypass.~~  
~~We're looking for a solution. Stay tuned.~~



The purpose of this library is to provide an easy, undetectable, and captcha resistant way to extract data
from all major search engines such as Google and Bing.

## Extracting Search Results From Google

```php

use SerpScraper\Engine\GoogleSearch;

$page = 1;
	
$google = new GoogleSearch();

// all available preferences for Google
$google->setPreference('results_per_page', 100);
//$google->setPreference('google_domain', 'google.lt');
//$google->setPreference('date_range', 'hour');

$results = array();

do {

	$response = $google->search("how to scrape google", $page);
	
	// error field must be empty otherwise query failed
	if($response->error == false){
	
		$results = array_merge($results, $response->results);
		$page++;
	
	} else if($response->error == 'captcha'){
	
		// assuming you have a subscription  to this captcha solving service: http://www.deathbycaptcha.com
		$status = $google->solveCaptcha("dbc_username", "dbc_password");
		
		if($status){
			$page++;
		}
		
		continue;
		
	}

} while ($response->has_next_page);

```

## Extract Search Results from Bing

```php

use SerpScraper\Engine\BingSearch;

$bing = new BingSearch();
$results = array();

for($page = 1; $page < 10; $page++){
	
	$response = $bing->search("search bing using php", $page);
	if($response->error == false){
		$results = array_merge($results, $response->results);
	}
	
	if($response->has_next_page == false){
		break;
	}
}

var_dump($results);

```


## Installation

The recommended way to install this is via Composer:

```bash
composer require athlon1600/serpscraper
```
