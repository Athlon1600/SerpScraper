<?php

namespace SerpScraper;

use Curl\BrowserClient;

class Browser extends BrowserClient
{
    public function __construct()
    {
        parent::__construct();

        // let's put some timeouts in case of slow proxies
        $this->options[CURLOPT_CONNECTTIMEOUT] = 10;
        $this->options[CURLOPT_TIMEOUT] = 15;

        // sometimes google routes the connection through IPv6 which just makes this more difficult to deal with - force it to always use IPv4
        $this->options[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
    }
}