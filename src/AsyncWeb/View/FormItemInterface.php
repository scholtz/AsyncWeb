<?php
namespace AsyncWeb\View;

interface FormItemInterface{
	/**
	 Identifies tag how it is marked in form configuration
	*/
	public function TagName();
	/**
	 Validates input 
	 @return Returns the filtered input
	*/
	public function Validate($input = null);
	/**
	 Indicates that Validated value should be stored as dictionary item
	 @return bool true = use Language::set for saving this value
	*/
	public function IsDictionary();
	/**
	 Renders item to show in insert form.
	 @return string rendered item
	*/
	public function InsertForm($SubmittedValue = null);
	/**
	 Renders item to show in update form.
	 @return string rendered item
	*/
	public function UpdateForm($SubmittedValue = null);
	
}