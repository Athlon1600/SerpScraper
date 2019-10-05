<?php

use PHPUnit\Framework\TestCase;
use SerpScraper\Engine\BingSearch;

final class BingTest extends TestCase
{
    public function testSearch()
    {
        $bing = new BingSearch();
        $bing->setPreference('results_per_page', 50);

        $res = $bing->search('bing');

        $this->assertEquals(10, count($res->results));
    }
}

