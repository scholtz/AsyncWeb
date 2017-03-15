<?php

namespace AsyncWeb\View\FormItem;


class APIFile extends \AsyncWeb\View\FormItem\File{
	protected function MoveFile(){
		// file is stored on api server
	}
	protected function SaveFileInfoToDB(){
	  $content = file_get_contents($_FILES[$this->name]['tmp_name']);
	  $table = $this->item["data"]["tableForFiles"];
	  
	  $info = pathinfo($_FILES[$this->name]['name']);
	  $ext = $info["extension"];
	  
      $this->db->u($table,$pid = md5(uniqid()),array("Name"=>$this->newFilename,"Extension"=>$ext,"MimeType"=>$_FILES[$this->name]['type'],"Content"=>$content,"Owner"=>\AsyncWeb\Security\Auth::userId()));
	  if($err = $this->db->error()){
		  throw new \Exception($err);
	  }
	  if($newid = $this->db->insert_id()){
		  if($newid != $pid) $pid = $newid;
	  }
      return $pid;
	}
	
}