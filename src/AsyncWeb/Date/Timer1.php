<?php

namespace AsyncWeb\Date;

class Timer1{
	private static $time = 0;
	public static function start(){
		Timer1::$time = Timer1::$last = \AsyncWeb\Date\Time::get();
	}
	private static $last = 0;
	public static function show(){
		if(!Timer1::$time) Timer1::start();
		$ret= sprintf("%0.4f",(\AsyncWeb\Date\Time::get()-Timer1::$time)/1e6)."L:".sprintf("%0.4f",(\AsyncWeb\Date\Time::get()-Timer1::$last)/1e6)." ";
		Timer1::$last = \AsyncWeb\Date\Time::get();
		return $ret;
	}
}
?>