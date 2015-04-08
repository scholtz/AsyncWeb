<?php
namespace AsyncWeb\DefaultBlocks;


class Main extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	public static $DICTIONARY = array(
		"sk-SK"=>array(
			"Your new website"=>"Vaša nová webstránka",
		),
		"en-US"=>array(
			"Your new website"=>"Your new website",
		),
	);
	protected function initTemplate(){
		$this->template = '<h1>{{Your new website}}</h1><p>This is your <b>example</b> main page with data "{{data}}". This is examle of inner block: {{{ExampleBlock}}}</p>';
	}
	
	public function init(){
		$data = array("data"=>"MY DATA");
		$this->setData($data);
	}
}