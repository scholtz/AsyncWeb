<?php
namespace AsyncWeb\DefaultBlocks\Layout;

class Form extends \AsyncWeb\Frontend\Block{
	protected $formSettings = array();
	protected $showType = "ALL";
	public function setFormSettings(Array $settings){
		$this->formSettings = $settings;
	}
	public function initTemplate(){
		$this->initTemplateForm();
	}
	protected function initTemplateForm(){
		$ret = "";
		$form = new \AsyncWeb\View\MakeForm($this->formSettings);
		$ret .= $form->show($this->showType);
		$this->template = $ret;
	}
	public function init(){
		
	}
}