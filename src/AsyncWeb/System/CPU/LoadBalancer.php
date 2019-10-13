<?php

namespace AsyncWeb\System\CPU;

class LoadBalancer {
    private static $inst = null;
    public static function get($tier3Delay = 2000000, $tier2Delay = null, $tier1Delay=null, $tier3 = 1, $tier2 = 0.8, $tier1 = 0.5 ){
        if($inst == null){
            $inst = new LoadBalancer();
        }
        if($tier1Delay !== null) $inst->tier1Delay = $tier1Delay;
        if($tier2Delay !== null) $inst->tier2Delay = $tier2Delay;
        if($tier3Delay !== null) $inst->tier3Delay = $tier3Delay;
        if($tier3 !== null) $inst->tier3 = $tier3;
        if($tier2 !== null) $inst->tier2 = $tier2;
        if($tier1 !== null) $inst->tier1 = $tier1;
        return $inst;
    }
    
    public $tier1 = 0.5;
    public $tier2 = 0.8;
    public $tier3 = 1;
    public $tier1Delay = null;
    public $tier2Delay = null;
    public $tier3Delay = null;
    public $cores = 1;
    
    public function __construct($tier1 = 0.5, $tier1Delay=null, $tier2 = 0.8 ,$tier2Delay = null, $tier3 = 1, $tier3Delay = 2000000){
        
        $this->cores = substr_count(@file_get_contents('/proc/cpuinfo'),"\nprocessor")+1;
        
        if($tier1Delay !== null) $this->tier1Delay = $tier1Delay;
        if($tier2Delay !== null) $this->tier2Delay = $tier2Delay;
        if($tier3Delay !== null) $this->tier3Delay = $tier3Delay;
        if($tier3 !== null) $this->tier3 = $tier3;
        if($tier2 !== null) $this->tier2 = $tier2;
        if($tier1 !== null) $this->tier1 = $tier1;
        $inst = $this;
    }
    
    public function wait($showmsg = false){
        $load = sys_getloadavg();
        if($showmsg){
            echo ($load[0]/$this->cores)." ".$this->tier1Delay." ".$this->tier1;
        }
        if($this->tier1Delay && $load[0]/$this->cores > $this->tier1){
            if($showmsg){
                echo "1";
            }
            usleep($this->tier1Delay);
        }
        if($this->tier2Delay && $load[0]/$this->cores > $this->tier2){
            if($showmsg){
                echo "2";
            }
            usleep($this->tier2Delay);
        }

        while($this->tier3Delay && $load[0]/$this->cores > $this->tier3){
            if($showmsg){
                echo "3";
            }
            usleep($this->tier3Delay);
            $load = sys_getloadavg();
        }
    }
}
