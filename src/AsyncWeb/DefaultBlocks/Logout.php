<?php
namespace AsyncWeb\DefaultBlocks;


class Logout extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		\AsyncWeb\HTTP\Header::s("location","/logout=1");
		exit;
	}
}