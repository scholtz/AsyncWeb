<?php

namespace AsyncWeb\DefaultBlocks;
use \BT\Base;$load = new \BT\Base;

class UserManagement extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	public static $DICTIONARY = array(
		"sk-SK"=>array(
			"Text"=>"TextSK",
		),
		"en-US"=>array(
			"Text"=>"TextEN",
		),
	);
	protected $usesparams = array();
	protected function initTemplate(){
		$c = new \BT\Container(new \BT\Row(new \BT\ColMd6(new \BT\PanelPrimary(new \BT\PanelHeading("heading"),new \BT\PanelBOdy("my <b>template{{Text}}.</b>"))),new \BT\ColMd6("col2")));
		
		$this->template = $c->show();
	}
	public function init(){
		
	}
}