<?php

namespace SerpScraper;

class SearchResponse
{
    // empty if everything went as expected, otherwise contains error string or 'captcha' if captcha
    public $error = '';

    // full html source from that page
    public $html = '';

    // true or false whether it has a next page
    public $has_next_page = false;

    // array starting at pos=1 of search results
    public $results = array();

    public function __construct()
    {
        // do nothing
    }

    public function __toString()
    {
        $status = $this->error ? 'error' : 'success';
        return "SearchResponse Status: {$status}, result count: " . count($this->results);
    }
}
