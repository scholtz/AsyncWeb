<?php
namespace AsyncWeb\DefaultBlocks;


class LoginForm extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		if(\AsyncWeb\Security\Auth::userId()){
			
			$ret = '<h1>Authentication succeess</h1><p>You are authenticated. Your email is: '.\AsyncWeb\Objects\User::getEmail().'.</p>';
		}else{
			$ret = '<h1>Web requires authentication</h1>';
			$ret.= \AsyncWeb\Security\Auth::loginForm();
			
		}
		$this->template = $ret;
	}
}