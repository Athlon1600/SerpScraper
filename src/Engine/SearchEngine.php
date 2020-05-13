<?php

namespace SerpScraper\Engine;

use SerpScraper\Browser;

abstract class SearchEngine
{
    protected $browser;
    protected $preferences = array();

    // default request options to be used with each client request
    protected $default_options = array();

    function __construct()
    {
        $this->browser = new Browser();
    }

    public abstract function search($query, $page_num);

    /**
     * @return Browser
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    public function setPreference($name, $value)
    {
        $this->preferences[$name] = $value;
    }

    // ALIAS
    public function setOption($name, $value)
    {
        $this->setPreference($name, $value);
    }

    public function getOption($name, $default = null)
    {
        return array_key_exists($name, $this->preferences) ? $this->preferences[$name] : $default;
    }
}
