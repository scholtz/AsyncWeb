<?php
namespace AsyncWeb\Text;
use AsyncWeb\DB\DB;
use AsyncWeb\Text\Texts;

class CSV2DB{
    
    public static function Process($text,$table,$add){
        $i = 0;
        $delimiter = ",";
        foreach(explode("\n",$text) as $line){$i++;
            if($i==1){
                // header
                
                $semi = count(explode(";",$line));
                $comma = count(explode(",",$line));
                $tab = count(explode("\t",$line));
                if($semi > $comma && $semi > $tab) $delimiter = ";";
                if($comma > $semi && $comma > $tab) $delimiter = ",";
                if($tab > $semi && $tab > $comma) $delimiter = "\t";

                $data = str_getcsv($line,$delimiter);
                foreach($data as $k=>$col){
                    $col = Texts::clear($col);
                    if($col == "id") $col = "identifier";
                    if($col == "id2") $col = "identifier2";
                    if($col == "od") $col = "od_od";
                    if($col == "do") $col = "do_do";
                    $n2k[$k] = $col;
                }
            }else{
                $data = str_getcsv($line,$delimiter);
                if(count($data) < 3) continue;
                $update = [];
                $id = "";
                foreach($data as $k=>$value){
                    $update[$n2k[$k]] = $value;
                    $id = md5($id.$k.$value);
                }
                foreach($add as $c=>$v){
                    $update[$c] = $v;
                }
                DB::u($table,$id,$update);
            }
        }
        
    }
}
