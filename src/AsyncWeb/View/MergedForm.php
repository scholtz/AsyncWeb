<?php
namespace AsyncWeb\View;
class MergedForm {
    private $forms = array();
    private $formDisplayed = false;
    public function formDisplayed() {
        $this->formDisplayed = true;
    }
    public function checkUpdate() {
        $doo = true;
        foreach ($this->forms as $form) {
            if (!$form->check_update()) $doo = false;
        }
        if (!$doo) {
            foreach ($this->forms as $form) {
                if (isset($form->exception)) {
                    \AsyncWeb\Text\Messages::getInstance()->error($form->exception->getMessage());
                }
            }
            return false;
        }
        foreach ($this->forms as $form) {
            if (!$form->performAction()) $doo = false;
        }
    }
    public function add($data) {
        $this->forms[] = new MakeForm($data, $this);
    }
    public function show($what = "ALL") {
        $ret = '<form action="' . Path::make(array()) . '" method="post"';
        if ($this->enctype) {
            $ret.= 'enctype="' . $this->enctype . '"';
        }
        $ret.= '>';
        foreach ($this->forms as $form) {
            $ret.= $form->show($what);
        }
        if (!$this->formDisplayed) {
            $data = array("col" => array(array("form" => array("type" => "submitReset"), "texts" => array("insert" => "MF_insert", "update" => "MF_update", "delete" => "MF_delete", "reset" => "MF_reset"), "usage" => array("MFi", "MFu", "MFd")),), "uid" => "MF-submitreset", "allowInsert" => true, "allowUpdate" => true, "allowDelete" => true, "useForms" => true,);
            $button = new MakeForm($data, $this);
            $ret.= $button->show($what);
        }
        return $ret;
    }
}
