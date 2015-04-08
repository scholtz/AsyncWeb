<?php
namespace AsyncWeb\Exceptions;
use AsyncWeb\Storage\Log;

class FatalException extends \Exception{
    public function __construct($message, $code = 0, Exception $previous = null) {
		Log::log("FatalException",$message,ML__TOP_SEC_LEVEL);
		
		// make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
	public static function registerFatalHandler(){
		register_shutdown_function( "\\AsyncWeb\Exceptions\\FatalException::fatal_handler" );
	
	}
	
	public static function fatal_handler() {
	  $errfile = "unknown file";
	  $errstr  = "shutdown";
	  $errno   = E_CORE_ERROR;
	  $errline = 0;

	  $error = error_get_last();

	   
	  
	  
	  if( $error !== NULL) {
		if(
			$error["type"] == E_ERROR || 
			$error["type"] == E_PARSE || 
			$error["type"] == E_CORE_ERROR || 
			$error["type"] == E_COMPILE_ERROR || 
			$error["type"] == E_USER_ERROR
		){
			

		  if(function_exists("xdebug_is_enabled") && xdebug_is_enabled()){
			  echo "<div>XDEBUG:";
			  echo " Called @ ".
				xdebug_call_file().
				":".
				xdebug_call_line().
				" from ".
				xdebug_call_function()."</div>\n";
				var_dump(ini_get("xdebug.show_exception_trace"));
		  }else{
			  echo "<div>X-Debug is not enabled</div>";
		  }			
			
			$errno   = $error["type"];
			$errfile = $error["file"];
			$errline = $error["line"];
			$errstr  = $error["message"];

			
			
			echo \AsyncWeb\Exceptions\FatalException::format_error( $errno, $errstr, $errfile, $errline);exit;
		}
	  }
	}
	
	function FriendlyErrorType($type)
	{
		switch($type)
		{
			case E_ERROR: // 1 //
				return 'E_ERROR';
			case E_WARNING: // 2 //
				return 'E_WARNING';
			case E_PARSE: // 4 //
				return 'E_PARSE';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_COMPILE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';
			case E_COMPILE_WARNING: // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return "";
	} 
	public static function showTrace(){
	  $trace = print_r( debug_backtrace( false ), true );

	  $content  = "<table><thead bgcolor='#c8c8c8'><th>Item</th><th>Description</th></thead><tbody>";
	  $content .= "<tr valign='top'><td><b>Trace</b></td><td><pre>$trace</pre></td></tr>";
	  $content .= '</tbody></table>';
	  return $content;
		
	}
	public static function format_error( $errno, $errstr, $errfile, $errline ) {
    
	  $trace = print_r( debug_backtrace( false ), true );

	  $content  = "<table><thead bgcolor='#c8c8c8'><th>Item</th><th>Description</th></thead><tbody>";
	  $content .= "<tr valign='top'><td><b>Error</b></td><td><pre>$errstr</pre></td></tr>";
	  $content .= "<tr valign='top'><td><b>Errno</b></td><td><pre>$errno</pre></td></tr>";
	  $content .= "<tr valign='top'><td><b>File</b></td><td>$errfile</td></tr>";
	  $content .= "<tr valign='top'><td><b>Line</b></td><td>$errline</td></tr>";
	  $content .= "<tr valign='top'><td><b>Trace</b></td><td><pre>$trace</pre></td></tr>";
	  $content .= '</tbody></table>';
	  return $content;
	}
}


?>