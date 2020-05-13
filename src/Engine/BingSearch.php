<?php

namespace SerpScraper\Engine;

use SerpScraper\SearchResponse;

class BingSearch extends SearchEngine
{
    protected function setResultsPerPage($count)
    {
        $count_allowed = array(10, 15, 30, 50);

        if (!in_array($count, $count_allowed)) {
            throw new \InvalidArgumentException('Invalid number!');
        }

        // open up the bing options page
        $form_html = $this->browser->get("https://www.bing.com/account/general")->body;

        // parse various session values from that page
        preg_match_all('/<input[^>]*name="\b(guid|sid|ru|uid)\b"[^>]*value="(.*?)"/i', $form_html, $matches, PREG_SET_ORDER);

        if ($matches) {

            // change some of them
            $options = array(
                'rpp' => $count,
                'pref_sbmt' => 1,
            );

            foreach ($matches as $match) {
                $options[$match[1]] = $match[2];
            }

            // submit the form and get the cookie that determines the number of results per page
            $this->browser->get("https://www.bing.com/account/?" . http_build_query($options));
        }
    }

    // en-us, en-gb, it-IT, ru-RU...
    protected function setSearchMarket($search_market)
    {
        $body = $this->browser->get("https://www.bing.com/account/general")->body;

        if (preg_match('/<a href="([^"]*setmkt=' . $search_market . '[^"]*)"/i', $body, $matches)) {

            $url = htmlspecialchars_decode($matches[1]);

            // this will set the session cookie
            $this->browser->get($url);
        }
    }

    // override
    public function setPreference($name, $value)
    {
        parent::setPreference($name, $value);

        if ($name == 'search_market') {
            $this->setSearchMarket($value);
        }

        if ($name == 'results_per_page') {
            $this->setResultsPerPage($value);
        }
    }

    function extractResults($html)
    {
        // ads ID=SERP,5417.1,Ads	ID=SERP,5106.1
        // bing local ID=SERP,5079.1
        // bing local ID=SERP,5486.1

        // news ID=SERP,5371.1

        // result ID=SERP,5167.1
        // result ID=SERP,5151.1

        preg_match_all('/<h2><a href="([^"]+)"\s*h="ID=SERP,[0-9]{4}\.1"/', $html, $matches);

        return $matches ? $matches[1] : array();
    }

    function search($query, $page = 1)
    {
        $start = ($page - 1) * $this->getOption('results_per_page', 10) + 1;

        $query = rawurlencode($query);
        $response = $this->browser->get("https://www.bing.com/search?q={$query}&first={$start}");

        $sr = new SearchResponse($response);

        if ($response->error) {
            $sr->error = $response->error;
        } else {

            $body = $response->body;

            $sr->results = $this->extractResults($body);
            $sr->has_next_page = strpos($body, "\"sw_next\">Next") !== false;
        }

        return $sr;
    }
}
