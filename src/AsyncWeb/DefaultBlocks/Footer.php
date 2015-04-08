<?php
namespace AsyncWeb\DefaultBlocks;


class Footer extends \AsyncWeb\Frontend\Block{
	public static $USE_BLOCK = true;
	protected function initTemplate(){
		if(\AsyncWeb\Security\Auth::userId()){
			$content = 'Your email is: '.\AsyncWeb\Objects\User::getEmail().' <a href="/logout=1">Log out</a>';
		}else{
			$content = '<a href="/go=Google">Login using Google if properly set up</a>';
		}
		$this->template = '<footer><div class="container"><div class="well">'.$content.'</div></div></footer>';
	}
}