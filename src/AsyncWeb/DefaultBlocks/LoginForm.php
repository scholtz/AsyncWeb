<?php
namespace AsyncWeb\DefaultBlocks;


class LoginForm extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
		public static $DICTIONARY = array(
		"sk-SK"=>array(
			"Authentication succeess"=>"Úspešne ste sa prihlásili",
			"You are authenticated as"=>"Ste prihlásený ako",
			"Web requires authentication"=>"Webstránka vyžaduje prihláseného užívateľa",
		),
		"en-US"=>array(
			"Authentication succeess"=>"Authentication succeess",
			"You are authenticated as:"=>"You are authenticated as:",
			"Web requires authentication"=>"Web requires authentication",
		),
	);
	protected function initTemplate(){
		if(\AsyncWeb\Security\Auth::userId()){
			
			$ret = '<h1>{{Authentication succeess}}</h1><p>{{You are authenticated as}}: '.\AsyncWeb\Objects\User::getEmail().'.</p>';
		}else{
			$ret = '<h1>{{Web requires authentication}}</h1>';
			$ret.= \AsyncWeb\Security\Auth::loginForm();
			
		}
		$this->template = $ret;
	}
}