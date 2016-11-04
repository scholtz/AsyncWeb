<?php

namespace AsyncWeb\Api\REST;

class APIResult{
	protected $data = array();
	protected $pointer = 0;
	public function __construct($data = array()){
		$this->data = $data;
	}
	public function Count(){
		return count($this->data);
	}
	public function getNext(){
		if(!$this->data) return false;
		return array_shift($this->data);
	}
	public function Free(){
		$this->data = array();
	}
}
