<?php
namespace AsyncWeb\Exception;
use AsyncWeb\System\Log;

class FatalException extends \Exception{
    public function __construct($message, $code = 0, Exception $previous = null) {
		AsyncWeb\System\Log::log("FatalException",$message,ML__TOP_SEC_LEVEL);
		
		// make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}


?>