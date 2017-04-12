<?php
namespace AsyncWeb\View;
class FormItemInstance implements \AsyncWeb\View\FormItemInterface {
    protected $item = array();
    protected $data = array();
    public function __construct(&$ItemConfig, &$TableConfig) {
        $this->item = $ItemConfig;
        $this->data = $TableConfig;
    }
    public function TagName() {
        return null;
    }
    public function Validate($input = null) {
        return $input;
    }
    public function IsDictionary() {
        return isset($this->item["data"]["dictionary"]) && $this->item["data"]["dictionary"];
    }
    public function InsertForm($SubmittedValue = null) {
        return "";
    }
    public function UpdateForm($SubmittedValue = null) {
        return $this->InsertForm($SubmittedValue);
    }
    protected function MakeItemId() {
        if (!isset($this->item["data"]["col"])) return $this->data["uid"] . "-0";
        return $this->data["uid"] . "_" . $this->item["data"]["col"];
    }
    protected function encodeEntities($str) {
        //$str = htmlentities($str,ENT_COMPAT, 'UTF-8');// gets converted in URLParser::v()
        $str = str_replace("{", "&#123;", $str); // so that templates are not executed when editing
        return $str;
    }
    protected function GetInnerDBColConfig(&$row, &$colsettings) {
        if (is_array($colsettings)) {
            $ret = "";
            foreach ($colsettings as $setting) {
                if (is_array($setting)) {
                    switch ($setting["type"]) {
                        case "data":
                            $ret.= $setting["value"];
                        break;
                        case "col":
                            $ret.= $row[$setting["value"]];
                        break;
                    }
                } else {
                    $ret.= $row[$setting];
                }
            }
            return $ret;
        } else {
            return $row[$colsettings];
        }
    }
    protected function DataValidation() {
        $ret = array();
        if (isset($this->item["data"]["validation"])) {
            if (isset($this->item["data"]["validation"]["Modules"])) $ret["Modules"] = $this->item["data"]["validation"]["Modules"];
            if (isset($this->item["data"]["validation"]["Format"])) $ret["Format"] = $this->item["data"]["validation"]["Format"];
            if (isset($this->item["data"]["validation"]["Allowing"])) $ret["Allowing"] = $this->item["data"]["validation"]["Allowing"];
            if (isset($this->item["data"]["validation"]["Qty"])) $ret["Qty"] = $this->item["data"]["validation"]["Qty"];
            if (isset($this->item["data"]["validation"]["Regexp"])) $ret["Regexp"] = $this->item["data"]["validation"]["Regexp"];
            if (isset($this->item["data"]["validation"]["Optional"])) $ret["Optional"] = $this->item["data"]["validation"]["Optional"];
            if (isset($this->item["data"]["validation"]["Ignore"])) $ret["Ignore"] = $this->item["data"]["validation"]["Ignore"];
            if (isset($this->item["data"]["validation"]["Suggestion"])) $ret["Suggestion"] = $this->item["data"]["validation"]["Suggestion"];
            if (isset($this->item["data"]["validation"]["Strength"])) $ret["Strength"] = $this->item["data"]["validation"]["Strength"];
            if (isset($this->item["data"]["validation"]["MaxSize"])) $ret["MaxSize"] = $this->item["data"]["validation"]["MaxSize"];
            if (isset($this->item["data"]["validation"]["ErrorMessage"])) $ret["ErrorMessage"] = $this->item["data"]["validation"]["ErrorMessage"];
            if (isset($this->item["data"]["validation"]["RequiredMessage"])) $ret["RequiredMessage"] = $this->item["data"]["validation"]["RequiredMessage"];
        } else {
            $modules = array();
            if ($MinLength > 0) {
                $modules["Required"] = true;
            }
            $MinLength = null;
            if (isset($this->item["data"]["minlength"]) && $this->item["data"]["minlength"]) $MinLength = $this->item["data"]["minlength"];
            $MaxLength = null;
            if (isset($this->item["data"]["maxlength"]) && $this->item["data"]["maxlength"]) $MaxLength = $this->item["data"]["maxlength"];
            $Min = null;
            if (isset($this->item["data"]["minnum"]) && $this->item["data"]["minnum"]) $Min = $this->item["data"]["minnum"];
            $Max = null;
            if (isset($this->item["data"]["maxnum"]) && $this->item["data"]["maxnum"]) $Max = $this->item["data"]["maxnum"];
            $Step = null;
            if (isset($this->item["data"]["step"]) && $this->item["data"]["step"]) $Step = $this->item["data"]["step"];
            if (isset($this->item["data"]["datatype"]) && ($this->item["data"]["datatype"] == "email")) {
                $modules["email"] = true;
            } else if (isset($this->item["data"]["datatype"]) && ($this->item["data"]["datatype"] == "url")) {
                $modules["url"] = true;
            } else if (isset($this->item["data"]["datatype"]) && ($this->item["data"]["datatype"] == "date")) {
                //$modules["date"] = true;
                if (isset($this->item["data"]["format"])) {
                    $format1 = $this->item["data"]["format"];
                } else {
                    $format1 = "Y-m-d";
                }
                //$ret["Format"] = $format1;
                
            } else if (isset($Min) || isset($Max) || isset($Step)) {
                $modules["number"] = true;
                if (isset($Step) && (strpos($Step, ".") !== false || strpos($Step, ",") !== false)) {
                    $ret["Allowing"] = "float";
                } elseif (isset($Step) && isset($Min) && isset($Max)) {
                    $ret["Allowing"] = "range[$Min;$Max]";
                }
                if ($Min < 0 || $Max < 0) {
                    if (isset($ret["Allowing"])) $ret["Allowing"].= ",";
                    $ret["Allowing"].= "negative";
                }
            }
            if (isset($MinLength) && isset($MaxLength)) {
                $ret["Length"] = $MinLength . "-" . $MaxLength;
                $modules["length"] = true;
            } elseif (isset($MinLength)) {
                $ret["Length"] = "min" . $MinLength;
                $modules["length"] = true;
            } elseif (isset($MaxLength)) {
                $ret["Length"] = "max" . $MaxLength;
                $modules["length"] = true;
            }
            $ret["Modules"] = "";
            foreach ($modules as $module => $true) {
                if ($ret["Modules"]) $ret["Modules"].= " ";
                $ret["Modules"].= $module;
            }
        }
        return $ret;
    }
}
