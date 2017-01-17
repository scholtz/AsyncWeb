<?php

namespace AsyncWeb\Date;

class Timer1{
	private static $time = 0;
	private static $last = 0;
	public static function start(){
		self::$time = self::$last = microtime(true);
	}
	public static function show(){
		if(!self::$time) self::start();
		$now = microtime(true);
		$ret= sprintf("%0.4f",($now-self::$time))."L:".sprintf("%0.4f",($now-self::$last))." ";
		self::$last = $now;
		return $ret;
	}
}
