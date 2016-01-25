<?php
namespace AsyncWeb\Async\Ratchet;
use AsyncWeb\Frontend\BlockManagement;

class RTemplate{
	public function getData($id,$data){
		$msg = "";
		try{	
			echo "getData: ".$data["template"].",".$data["tid"]."\n";
			if($block = BlockManagement::get($data["template"],$data["tid"])){
				return array("msg"=>"result","id"=>$id,"result"=>array("data"=>$block->getData()));
			}
		}catch(\Exception $exc){
			$msg = "\n".$exc->getMessage();
		}
		return array("msg"=>"result","id"=>$id,"error"=>array("error"=>404,"reason"=>"Block not found","message"=>"Block not found [404]  0x015211".$msg,"errorType"=>"Meteor.Error"));
	}
	public function getTemplate($id,$template){
		$msg = "";
		try{
		echo "getTemplate:";var_dump($template);
			if($block = BlockManagement::get($template,"")){
				return array("msg"=>"result","id"=>$id,"result"=>array("template"=>$block->getTemplate(),"vars"=>$block->getUsesParams()));
			}
		}catch(\Exception $exc){
			$msg = "\n".$exc->getMessage();
		}
		return array("msg"=>"result","id"=>$id,"error"=>array("error"=>404,"reason"=>"Block not found","message"=>"Block not found [404]  0x025211".$msg,"errorType"=>"Meteor.Error"));
	}
}