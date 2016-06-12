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

//$google->setPreference('google_domain', 'google.com');
//$google->setPreference('date_range', 'hour');

//$google->setPreference('hl', 'en'); // web interface language code
//$google->setPreference('lr', 'lang_en'); // search language code

//$google->setPreference('ie', 'utf-8'); // character encoding scheme for query string.
//$google->setPreference('oe', 'utf-8'); // character encoding scheme decode XML results

//$google->setPreference('gbv', 1); // no javascript
//$google->setPreference('pws', 0); // do not personalize my search results
//$google->setPreference('nfrpr', 1); // do not auto correct my search queries
//$google->setPreference('complete', 0); // 0 to disable instant search and enable more than 10 results

$results = array();

do {

	$response = $google->search("how to scrape google", $page);
	
	// error field must be empty otherwise query failed
	if($response->error == false){
	
		$results[] = $response->results;
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


## Installation

The recommended way to install this is via Composer:

```bash
composer require athlon1600/serpscraper:dev-master
```
