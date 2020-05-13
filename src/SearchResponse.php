<?php

namespace SerpScraper;

use Curl\Response;

class SearchResponse
{
    // empty if everything went as expected, otherwise contains error string or 'captcha' if captcha
    public $error = '';

    // full html source from that page
    protected $curl_response = '';

    // true or false whether it has a next page
    public $has_next_page = false;

    // array starting at pos=1 of search results
    public $results = array();

    /**
     * SearchResponse constructor.
     * @param Response $curl_response
     */
    public function __construct($curl_response = null)
    {
        $this->curl_response = $curl_response;
    }

    public function getCurlResponse()
    {
        return $this->curl_response;
    }

    public function __toString()
    {
        $status = $this->error ? 'error' : 'success';
        return "SearchResponse Status: {$status}, result count: " . count($this->results);
    }
}
