<?php
/**
Time management class

The primary purpose of the class is to specify the microsecond time as bigint

replaces time() function with Time::get()

functions that requires unix time must convert the time object with Time::getUnix($time);

to add a time series the time must be added with $t += Time::span($seconds);

the Time class can be modified on the System::$USE_MICROTIME setting..

if System::$USE_MICROTIME == false, time() == Time::get()

*/

namespace AsyncWeb\Date;

class Time{
	public static $USE_MICROTIME = false;
	public static function get($time = null){
		if($time === null){
			if(Time::$USE_MICROTIME){
				return Time::format(floor(microtime(true)*1e6));
			}else{
				return Time::format(time());
			}
		}elseif(!$time){
			return $time;
		}elseif(ceil(log10($time)) == 10){
			if(Time::$USE_MICROTIME){
				return Time::format($time."000000");
			}else{
				return Time::format($time);
			}
		}elseif(ceil(log10($time)) == 16){
			if(Time::$USE_MICROTIME){
				return Time::format($time);
			}else{
				return Time::format(substr($time,0,10));
			}
		}else{
			if(Time::$USE_MICROTIME){
				return Time::format(floor(microtime(true)*1e6));
			}else{
				return Time::format(time());
			}
		}
	}
	public static function format($number){
		return number_format($number,0,".","");
	}
	public static function getUnix($time){
		if(strlen(Time::format($time)) == 10){
			return $time;
		}elseif(strlen(Time::format($time)) == 16){
			return substr(Time::format($time),0,10);
		}else{
			return time();
		}
	}
	public static function span($seconds){
		return $seconds*Time::getMultiplier();
	}
	public static function getMultiplier(){
		if(Time::$USE_MICROTIME){
			return 1e6;
		}
		return 1;
	}
}

?>