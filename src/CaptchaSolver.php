<?php

namespace SerpScraper;

interface CaptchaSolver {

	public function decode($bytes);
}

?>