<?php
namespace AsyncWeb\DefaultBlocks;
class Form extends \AsyncWeb\Frontend\Block {
    protected $formSettings = array();
    protected $showType = "ALL";
    protected $type = "MakeForm";
    public function setFormSettings(Array $settings) {
        $this->formSettings = $settings;
    }
    public function initTemplate() {
        $this->initTemplateForm();
    }
    protected function initTemplateForm() {
        $ret = "";
        if ($this->type == "ApiForm") {
            $form = new \AsyncWeb\View\ApiForm($this->formSettings);
        } else {
            $form = new \AsyncWeb\View\MakeForm($this->formSettings);
        }
        $ret.= $form->show($this->showType);
        $this->template = $ret;
    }
    public function init() {
    }
}
