<?php
namespace AsyncWeb\DefaultBlocks\Layout;
class Scripts extends \AsyncWeb\Frontend\Block {
    protected function initTemplate() {
		
		$ret = "";
		if($msg = \AsyncWeb\Text\Messages::getInstance()->show()){
			$msg = str_replace(array("\t", "\r", "\n"), "", $msg);
			$msg = str_replace("\"", "\\\"", $msg);
			$msg = str_replace("\\\\", "\\", $msg);
			$ret.= '<script>';
			$ret.= '$(".messages").append("' . $msg . '");';
			$ret.= '$("html, body").animate({scrollTop: $(".messages").offset().top}, 500);';
			$ret.= '</script>';
		}
		
        $this->template = $ret;
    }
}
