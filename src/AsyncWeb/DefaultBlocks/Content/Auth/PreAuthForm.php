<?php

namespace AsyncWeb\DefaultBlocks\Content_Auth;
use \BT\Base;$load = new \BT\Base;
use \AsyncWeb\System\Language;

class PreAuthForm extends \AsyncWeb\Frontend\Block{
	protected function initTemplate(){
		$c = new \BT\Container(new \BT\Row(new \BT\ColMd12(new \BT\PanelPrimary(new \BT\PanelHeading(Language::get("System requires additional security confirmation")),$b=new \BT\PanelBOdy()))));
		
		$b->a(\AsyncWeb\Security\Auth::showControllerForm());
		
		$this->template = $c->show();
	}
	public function init(){
		
	}
}