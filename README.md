SerpScraper
===========

The purpose of this library is to provide an easy, undetectable, and captcha resistant way to extract data
from all major search engines such as Google and Bing.

## Extracting Search Results From Google

```php

use SerpScraper\Engine\GoogleSearch;

use SerpScraper\Captcha\CaptchaSolver;
use SerpScraper\Captcha\DBCSolver;

$page = 1;

// assuming you have a subscription  to this captcha solving service: http://www.deathbycaptcha.com
$dbc = new DBCSolver("username", "password");
	
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
	
		$status = $google->solveCaptcha($dbc);
		
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
