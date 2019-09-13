<?php
namespace AsyncWeb\Text;
use AsyncWeb\DB\DB;
use AsyncWeb\Text\Texts;

class CSV2DB{
    
    public static function Process($text,$table,$config,$add = [],$renameColumns = [],$columnHandlers = []){
        $i = 0;
        $delimiter = ",";
        
        $line = strtok($text, "\n");
        $semi = count(explode(";",$line));
        $comma = count(explode(",",$line));
        $tab = count(explode("\t",$line));
        if($semi > $comma && $semi > $tab) $delimiter = ";";
        if($comma > $semi && $comma > $tab) $delimiter = ",";
        if($tab > $semi && $tab > $comma) $delimiter = "\t";

        
        $handle = fopen('data://text/plain;base64,' . base64_encode($text),'r');
        if(!$handle) return false;
        while (($data = fgetcsv($handle, 1024*16, $delimiter)) !== FALSE) {
            $i++;
            if($i==1){
                // header

                foreach($data as $k=>$col){
                    $col = Texts::clear($col);
                    if($col == "id") $col = "identifier";
                    if($col == "id2") $col = "identifier2";
                    if($col == "od") $col = "od_od";
                    if($col == "do") $col = "do_do";
                    if(isset($renameColumns[$col])){
                        $col = $renameColumns[$col];
                    }
                    $n2k[$k] = $col;
                }
                
                continue;
            }
            

            if(count($data) < 3) continue;
            $update = [];
            $id = "";
            foreach($data as $k=>$value){
                if(!isset($n2k[$k]) || !$n2k[$k]) continue;
                
                $update[$n2k[$k]] = $value;
                
                $id = md5($id.$k.$value);
            }
            // process columnHandlers only after all data is processed
            foreach($data as $k=>$value){
                if(!isset($n2k[$k]) || !$n2k[$k]) continue;
                if(isset($columnHandlers[$n2k[$k]])){
                    $update = $columnHandlers[$n2k[$k]]($update);
                }
            }
            foreach($add as $c=>$v){
                $update[$c] = $v;
            }
            DB::u($table,$id,$update,$config);
        
        }
        
        
    }
}
