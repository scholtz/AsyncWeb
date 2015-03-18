<?php

namespace AsyncWeb\Article;

class ArticleInstance{
	public $category = "";
	public $text = "";
	public $type = "";
	public $logintype = "all";
	public $group = null;
	public function __construct($category="",$text="",$type=""){
		$this->category = $category;
		$this->text = $text;
		$this->type = $type;
	}
	public function category(){
		return $this->category;
	}
	public function show(){
		return $this->text;
	}
	public function getType(){
		return $this->type;
	}
	public function getRow(){
		return array("type"=>$this->type,"text"=>$this->text,"category"=>$this->category,"logintype"=>$this->logintype,"group"=>$this->group);
	}
}