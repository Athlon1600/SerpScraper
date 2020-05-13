<?php

use PHPUnit\Framework\TestCase;
use SerpScraper\Engine\GoogleSearch;

final class GoogleTest extends TestCase
{
    public function testSearch()
    {
        $google = new GoogleSearch();
        $res = $google->search('something random');

        $this->assertGreaterThan(80, count($res->results));
    }
}

