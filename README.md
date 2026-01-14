[![Static Analysis](https://github.com/Athlon1600/SerpScraper/actions/workflows/static.yml/badge.svg)](https://github.com/Athlon1600/SerpScraper/actions/workflows/static.yml)
![](https://img.shields.io/github/last-commit/Athlon1600/SerpScraper.svg)
![PHP Versions](https://img.shields.io/badge/PHP%20Versions-%3E%3D7.3%20%7C%7C%20%5E8.0-blue)


SerpScraper
===========

The purpose of this library is to provide an easy, undetectable, and captcha resistant way to extract search results
from popular search engines like Google and Bing.

## Installation

The recommended way to install this is via Composer:

```bash
composer require athlon1600/serpscraper "^4.0"
```

## Extracting Search Results From Google

```php
<?php

use SerpScraper\Engine\GoogleSearch;

$page = 1;

$google = new GoogleSearch();

// all available preferences for Google
//$google->setPreference('google_domain', 'google.lt');
//$google->setPreference('date_range', 'hour');

$results = array();

do {

	$response = $google->search("how to scrape google", $page);
	
	// error field must be empty otherwise query failed
	if(empty($response->error)){
	
		$results = array_merge($results, $response->results);
		$page++;
		
	} else if($response->error == 'captcha'){
	    
	    // read below
	    break;
	}

} while ($response->has_next_page);
```


## Solve Google Search captchas automatically

For this to work, you will need to register for 2captcha.com services, and get an API key.
It is also highly recommended to use a proxy server.  
Install a private proxy server on your own VPS here:  
https://github.com/Athlon1600/useful#squid

```php
<?php

use SerpScraper\Engine\GoogleSearch;
use SerpScraper\GoogleCaptchaSolver;

$google = new GoogleSearch();

$browser = $google->getBrowser();
$browser->setProxy('PROXY:IP');

$solver = new GoogleCaptchaSolver($browser);

while(true){
    $response = $google->search('famous people born in ' . mt_rand(1500, 2020));
    
    if ($response->error == 'captcha') {

        echo "Captcha detected!" . PHP_EOL;
        
        $temp = $solver->solveUsingTwoCaptcha($response, '2CAPTCHA_API_KEY', 90);

        if ($temp->status == 200) {
            echo "Captcha solved successfully!" . PHP_EOL;
        } else {
            echo 'Solving captcha has failed...' . PHP_EOL;
        }

    } else {
        echo "OK. ";
    }
    
    sleep(2);
}
```



## Extract Search Results from Bing

```php
<?php

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
