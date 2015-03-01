<?php

namespace SerpScraper\Captcha;

interface CaptchaSolver {
	public function solve($bytes);
}

?>