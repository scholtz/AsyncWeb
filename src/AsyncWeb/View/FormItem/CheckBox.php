<?php
namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;
class CheckBox extends \AsyncWeb\View\FormItem\NullableValue {
    public function TagName() {
        return "checkbox";
    }
    public function Validate($input = null) {
        $input = \AsyncWeb\Frontend\URLParser::v($input);
        if ($input === null) return null;
        return $input || $input;
    }
    public function InsertForm($SubmittedValue = null) {
        $Template = "View_FormItem_InputBox";
        if (isset($this->item["form"]["template"]) && $this->item["form"]["template"]) $Template = Language::get($this->item["form"]["template"]);
        $ID = $this->MakeItemId();
        $LabelText = false;
        if (isset($this->item["texts"]["name"]) && $this->item["texts"]["name"]) $LabelText = Language::get($this->item["texts"]["name"]);
        if (!$LabelText) if (isset($this->item["name"]) && $this->item["name"]) $LabelText = Language::get($this->item["name"]);
        $Help = false;
        if (isset($this->item["texts"]["help"]) && $this->item["texts"]["help"]) $Help = Language::get($this->item["texts"]["help"]);
        $Placeholder = false;
        if (isset($this->item["texts"]["placeholder"]) && $this->item["texts"]["placeholder"]) $Placeholder = Language::get($this->item["texts"]["placeholder"]);
        if (!$Placeholder) $Placeholder = $Help;
        $After = false;
        if (isset($this->item["texts"]["after"]) && $this->item["texts"]["after"]) $After = Language::get($this->item["texts"]["after"]);
        $Prepend = false;
        if (isset($this->item["texts"]["prepend"]) && $this->item["texts"]["prepend"]) $Prepend = Language::get($this->item["texts"]["prepend"]);
        $AddClass = false;
        if (isset($this->item["form"]["class"]) && $this->item["form"]["class"]) $AddClass = $this->item["form"]["class"];
        $Type = "checkbox";
        $Value = false;
        if ($SubmittedValue !== null) {
            $Value = $SubmittedValue;
        } else {
            if (isset($this->item["texts"]["default"]) && $this->item["texts"]["default"] && $this->item["texts"]["default"] !== "false") {
                $Value = Language::get($this->item["texts"]["default"]);
            }
        }
        $DataPlacement = false;
        if ($Help) $DataPlacement = "bottom";
        $Editable = true;
        if (isset($this->item["editable"]) && !$this->item["editable"]) {
            $Editable = false;
        }
        $Name = $ID;
        if (isset($this->item["data"]["var"]) && $this->item["data"]["var"]) $Name = $this->item["data"]["var"];
        $DataValidation = $this->DataValidation();
        return \AsyncWeb\Text\Template::loadTemplate($Template, array("LabelText" => $LabelText, "Prepend" => $Prepend, "After" => $After, "ID" => $ID, "AddClass" => $AddClass, "Type" => $Type, "Name" => $Name, "Checked" => $Value, "Title" => $Help, "Placeholder" => $Placeholder, "DataContent" => $Help, "DataPlacement" => $DataPlacement, "Disabled" => !$Editable, "BT_SIZE" => $this->item["BT_SIZE"], "BT_WIDTH_OF_LABEL" => $this->item["BT_WIDTH_OF_LABEL"], "BT_WIDTH_9" => $this->item["BT_WIDTH_9"], "BT_WIDTH_10" => $this->item["BT_WIDTH_10"], "DataValidation" => $DataValidation,), false, false);
    }
}
