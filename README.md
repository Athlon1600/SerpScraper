SerpScraper
===========

The purpose of this library is to provide an easy, undetectable, and captcha resistant way to extract data
from all major search engines such as Google and Bing.

## Installation

The recommended way to install this is via Composer:

```bash
composer require athlon1600/serpscraper:dev-master
```

## Extracting Search Results From Google

```php

use SerpScraper\Engine\GoogleSearch;

use SerpScraper\Captcha\CaptchaSolver;
use SerpScraper\Captcha\DBCSolver;

$page = 1;

// assuming you have a subscription  to this captcha solving service: http://www.deathbycaptcha.com
$dbc = new DBCSolver("username", "password");
	
$google = new GoogleSearch();

$results = array();

do {

	$response = $google->search("how to scrape google", $page);
	
	// error field must be empty otherwise query failed
	if($response->error == false){
	
		$results[] = $response->results;
		$page++;
	
	} else if($response->error == 'captcha'){
	
		$status = $google->solveCaptcha($dbc);
		
		continue;
		
	}

} while ($response->has_next_page);

```

### Country based Google scraping

```php
// google domain / tld
$google->setPreference('google_domain', 'google.se'); 

// web interface language code
$google->setPreference('hl', 'sv');

// search language code
$google->setPreference('lr', 'lang_sv');
```

##### List of Google language codes
https://sites.google.com/site/tomihasa/google-language-codes

### Other preferences / query string parameters
```php
// number of results per page
$google->setPreference('results_per_page', 100);

// select date range
$google->setPreference('date_range', 'hour');

// character encoding scheme for query string
$google->setPreference('ie', 'utf-8');

// character encoding scheme decode XML results
$google->setPreference('oe', 'utf-8');

// no javascript
$google->setPreference('gbv', 1);

// do not personalize my search results
$google->setPreference('pws', 0);

// do not auto correct my search queries
$google->setPreference('nfrpr', 1);

// 0 to disable instant search and enable more than 10 results
$google->setPreference('complete', 0);
```
