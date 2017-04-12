<?php
namespace AsyncWeb\View\FormItem;
use AsyncWeb\System\Language;
class HTMLText extends \AsyncWeb\View\FormItemInstance {
    public function TagName() {
        return null;
    }
    public function Validate($input = null) {
        $ret = \AsyncWeb\Frontend\URLParser::v($input);
        if (isset($this->item["data"]["dictionary"]) && $this->item["data"]["dictionary"]) {
            if ($ret) {
                if (class_exists("DetectIntrusion")) {
                    $ret = DetectIntrusion::XSSDecode($ret);
                }
            }
        }
        if (isset($this->item["data"]["allowNull"]) && $ithis->tem["data"]["allowNull"] && !$input) {
            $ret = null;
        }
        return $ret;
    }
}
