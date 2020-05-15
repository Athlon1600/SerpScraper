<?php

namespace SerpScraper;

use CaptchaSolver\TwoCaptcha;
use CaptchaSolver\Utils;
use Curl\Client;

class GoogleCaptchaSolver
{
    protected $solver;
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    protected function getFormParams($html)
    {
        $data = array();

        if (preg_match('/"continue"\s*value="(.*?)"/', $html, $matches)) {
            $data['continue'] = htmlspecialchars_decode($matches[1]);
        }

        if (preg_match("/name='q'\s*value='([^']+)/is", $html, $matches)) {
            $data['q'] = $matches[1];
        }

        return $data;
    }

    public function solveUsingTwoCaptcha(SearchResponse $response, $key, $timeout = 90)
    {
        $utils = new Utils();

        $solver = new TwoCaptcha([
            'key' => $key
        ]);

        $curl = $response->getCurlResponse();

        $site_key = $utils->findSiteKey($curl->body);

        // not on captcha page
        if ($site_key) {

            $solution = $solver->solveReCaptchaV2($site_key, $curl->info->url, $timeout);

            $form_data = $this->getFormParams($curl->body);
            $form_data['g-recaptcha-response'] = $solution;

            return $this->client->post('https://www.google.com/sorry/index', $form_data);
        }

        return null;
    }
}