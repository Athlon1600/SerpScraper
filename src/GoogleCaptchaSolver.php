<?php

namespace SerpScraper;

use CaptchaSolver\TwoCaptcha;
use CaptchaSolver\Utils;

class GoogleCaptchaSolver
{
    protected $solver;

    /** @var Browser */
    private $browser;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
    }

    protected function getFormParams($html)
    {
        return array(
            'continue' => htmlspecialchars_decode(Utils::getInputValueByName($html, 'continue')),
            'q' => Utils::getInputValueByName($html, 'q')
        );
    }

    public function solveUsingTwoCaptcha(SearchResponse $response, $key, $timeout = 90)
    {
        $solver = new TwoCaptcha\Client([
            'key' => $key,
            'proxy' => $this->browser->getProxy()
        ]);

        $curl = $response->getCurlResponse();

        $site_key = Utils::findSiteKey($curl->body);

        // not on captcha page
        if ($site_key) {

            $request = new TwoCaptcha\InRequest();
            $request->key = $key;
            $request->googlekey = $site_key;
            $request->pageurl = $curl->info->url;
            $request->data_s = Utils::findDataSVariable($curl->body);

            $solution = $solver->solveReCaptchaV2($request, $timeout);

            $form_data = $this->getFormParams($curl->body);
            $form_data['g-recaptcha-response'] = $solution;

            return $this->browser->post('https://www.google.com/sorry/index', $form_data);
        }

        return null;
    }
}