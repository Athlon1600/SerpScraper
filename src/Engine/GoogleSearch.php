<?php

namespace SerpScraper\Engine;

use SerpScraper\SearchResponse;

class GoogleSearch extends SearchEngine
{
    public $last_captcha_url = '';

    function __construct()
    {
        parent::__construct();

        $this->preferences['results_per_page'] = 100;
        $this->preferences['google_domain'] = 'google.com';
    }

    private function getNextPage($html)
    {
        if (preg_match('/<td class="b[^>]*>\s*<a[^>]+href="(.*?)"/', $html, $matches)) {
            $url = 'https://www.' . $this->preferences['google_domain'] . '' . $matches[1];
            return htmlspecialchars_decode($url);
        }

        return false;
    }

    private function decodeLink($link)
    {
        if (preg_match('/\\/url\\?q=(.*?)&amp;/', $link, $matches) == 1) {
            $link = $matches[1];
        } else if (preg_match('/interstitial\\?url=(.*?)&amp;/', $link, $matches) == 1) {
            $link = $matches[1];
        }

        $link = htmlspecialchars_decode($link);

        return rawurldecode($link);
    }

    private function extractResults($raw_html)
    {
        // TODO: maybe do it in blocks? extract result blocks first? <div class="g">
        if (isset($this->preferences['detailed_results'])) {

            $ret = array();

            if (preg_match_all('/<h3 class="r"><a href="([^"]*http[^"]+)"[^>]*>(.*?)<\/a.*?<span class="st">(.*?)<\/span>/is', $raw_html, $matches) > 0) {

                for ($i = 0; $i < count($matches[0]); $i++) {

                    $ret[] = array(
                        'url' => $this->decodeLink($matches[1][$i]),
                        'title' => $matches[2][$i],
                        'snippet' => $matches[3][$i]
                    );
                }

                return $ret;
            } else {
                return array();
            }
        }

        // must contain http otherwise it's a relative link to google which we're not interested in
        if (preg_match_all('/<a href="([^"]*http[^"]*)"/i', $raw_html, $matches) > 0) {

            // depending on user agent, links returned are sometimes prefixed with /url?q= ... remove it
            $urls = $matches[1];
            $results = array();

            foreach ($urls as $url) {

                // these are Google-specific links
                if (strpos($url, 'ved=2') === false) {
                    continue;
                }

                $url = $this->decodeLink($url);

                $results[] = $url;
            }

            return $results;
        }

        return array();
    }

    private function prepare_url($query, $page)
    {
        $results_per_page = $this->preferences['results_per_page'];
        $google_domain = $this->preferences['google_domain'];

        $vars = array(
            'q' => $query,
            'start' => ($page - 1) * $results_per_page,
            'client' => 'navclient', // probably useless
            'gbv' => 1, // no javascript
            'complete' => 0, // 0 to disable instant search and enable more than 10 results
            'num' => $results_per_page, // number of results
            'pws' => 0, // do not personalize my search results
            'nfpr' => 1, // do not auto correct my search queries
            'ie' => 'utf-8',
            'oe' => 'utf-8'
        );

        $vars = @array_merge($vars, (array)$this->preferences['query_params']);

        if (isset($this->preferences['date_range'])) {

            $str = substr($this->preferences['date_range'], 0, 1);

            if (in_array($str, array('h', 'd', 'w', 'm', 'y'))) {
                $vars['tbs'] = 'qdr:' . $str;
            }
        }

        // do query building ourselves to get the url
        $url = 'http://www.' . $google_domain . '/search?' . http_build_query($vars, '', '&');

        return $url;
    }

    // visits a special URL that disables ALL country redirects
    function ncr()
    {

        return;

        /*
        // check if /ncr cookie has already been set
        $ncr = false;

        // the cookie we're looking for should have these options: FF=0:LD=en:CR=2
        $cookies = $this->client->getDefaultOption('cookies')->toArray();

        foreach($cookies as $cookie){

            if($cookie['Domain'] == '.google.com' && $cookie['Name'] == 'PREF' && strpos($cookie['Value'], 'CR=2') !== false){
                $ncr = true;
                break;
            }
        }

        if(!$ncr){
            $this->client->get('http://www.google.com/ncr');
        }
        */
    }

    function search($query, $page_num = 1)
    {
        $url = $this->prepare_url($query, $page_num);

        $sr = new SearchResponse();

        // fetch response
        $response = $this->client->get($url);

        if ($response && $response->getStatusCode() == 200) {

            $html = $response->getBody();

            $sr->html = $html;

            // extract urls
            $sr->results = $this->extractResults($html);

            // is there another page of results for this query?
            $sr->has_next_page = $this->getNextPage($html) == true;

        } else if ($response && $response->getStatusCode() == 503) {

            $info = $response->getCurlInfo();

            $sr->error = 'captcha';
            $this->last_captcha_url = $info['url'];

        } else {

            $sr->html = $response->getBody();

            // http timeout - host not found type errors...
            $sr->error = $this->client->error;
        }

        return $sr;
    }

    public function solveCaptcha($dbc_username, $dbc_password)
    {
        // once request is made, a new LAST_CAPTCHA_URL must be generated for it to work
        if (!$this->last_captcha_url) {
            return false;
        }

        $response = $this->client->get($this->last_captcha_url);
        $body = $response->getBody();

        if (preg_match('/sitekey="([^"]+)"/', $body, $matches)) {
            $key = $matches[1];

            $client = new \DeathByCaptcha_HttpClient($dbc_username, $dbc_password);
            $client->is_verbose = false;

            $data = array(
                'googlekey' => $key,
                'pageurl' => $this->last_captcha_url
            );

            $extra = array(
                'type' => 4,
                'token_params' => json_encode($data)
            );

            $captcha = $client->decode(null, $extra);

            // we wait?
            sleep(mt_rand(1, 3));

            if ($text = $client->get_text($captcha['captcha'])) {
                //echo "CAPTCHA {$captcha['captcha']} solved: {$text}\n";

                $q = '';
                if (preg_match("/name='q'\s*value='([^']+)/is", $body, $matches)) {
                    $q = $matches[1];
                }

                $post_data = array(
                    'continue' => 'https://www.google.com/search?q=dragon',
                    'g-recaptcha-response' => $text,
                    'q' => $q,
                    'submit' => 'Submit'
                );

                $res = $this->client->post('http://ipv4.google.com/sorry/index', $post_data);
                return true;
            }
        }

        return false;
    }
}
