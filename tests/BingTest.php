<?php

use PHPUnit\Framework\TestCase;
use SerpScraper\Engine\BingSearch;

final class BingTest extends TestCase
{
    public function testSearch()
    {
        $bing = new BingSearch();
        $response = $bing->search('google or bing');

        $this->assertGreaterThan(0, count($response->results));
    }

    public function testSearchWithMoreResults()
    {
        $bing = new BingSearch();
        $bing->setPreference('results_per_page', 50);

        $response = $bing->search("bing versus google");

        $this->assertGreaterThan(40, count($response->results));
    }
}

