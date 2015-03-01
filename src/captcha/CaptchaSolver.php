<?php

namespace SerpScraper;

interface CaptchaSolver {
	public function solve($bytes);
}

?>