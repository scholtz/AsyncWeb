<?php
/**
This class manages the callendar and holidays

28.4.2013 	modules/Time.php compliant
*/
use AsyncWeb\Date\Time;
namespace AsyncWeb\System\Date;

class Calendar{
	private $month = 1;
	private $year = 2000;
	private $id = "default";
	private $modal = false;
	private $addstyle = "";
	private $style = ".calendar_header, .calendar{font-size:smaller;width:100%;border:1px solid gray;text-align:center;} .calendar_header{border:none;} .calendar .info{text-align:center;} .calendar .mday{border:1px solid gray; text-align: center;} .calendar .mtoday{border:2px solid red; text-align: center;} .calendar .nmday{text-align:center; background-color:#eeeeee;}";
	private $dates = array();
	public static $format = "natural";// standard | natural
	public function registerDate($date,$type,$text){
		$this->dates[$date][] = array("type"=>$type,"text"=>$text);
	}
	private static $holidays = array();
	public static function getHolidays(){
		if(Calendar::$holidays) return Calendar::$holidays;
		$holidays = array(

		 Time::get(mktime(0,0,0,1,1,2012)) =>"Deň vzniku Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,1,6,2012)) => "Zjavenie Pána (Traja králi) - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,4,6,2012)) => "Veľký piatok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,4,9,2012)) => "Veľkonočný pondelok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,1,2012)) => "Sviatok práce - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,8,2012)) => "Deň víťazstva nad fašizmom - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,7,5,2012)) => "Sviatok svätého Cyrila a Metoda - štátny sviatok",
		 Time::get(mktime(0,0,0,8,29,2012)) => "Výročie SNP - štátny sviatok",
		 Time::get(mktime(0,0,0,9,1,2012)) => "Deň Ústavy Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,9,15,2012)) => "Sedembolestná Panna Mária - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,1,2012)) => "Sviatok všetkých svätých - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,17,2012)) =>"Deň boja za slobodu a demokraciu - štátny sviatok",
		 Time::get(mktime(0,0,0,12,24,2012)) => "Štedrý deň - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,25,2012)) => "Prvý sviatok vianočný - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,26,2012)) => "Druhý sviatok vianočný - deň pracovného pokoja",
		 
		 Time::get(mktime(0,0,0,1,1,2013)) =>"Deň vzniku Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,1,6,2013)) => "Zjavenie Pána (Traja králi) - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,3,29,2013)) => "Veľký piatok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,4,1,2013)) => "Veľkonočný pondelok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,1,2013)) => "Sviatok práce - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,8,2013)) => "Deň víťazstva nad fašizmom - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,7,5,2013)) => "Sviatok svätého Cyrila a Metoda - štátny sviatok",
		 Time::get(mktime(0,0,0,8,29,2013)) => "Výročie SNP - štátny sviatok",
		 Time::get(mktime(0,0,0,9,1,2013)) => "Deň Ústavy Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,9,15,2013)) => "Sedembolestná Panna Mária - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,1,2013)) => "Sviatok všetkých svätých - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,17,2013)) =>"Deň boja za slobodu a demokraciu - štátny sviatok",
		 Time::get(mktime(0,0,0,12,24,2013)) => "Štedrý deň - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,25,2013)) => "Prvý sviatok vianočný - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,26,2013)) => "Druhý sviatok vianočný - deň pracovného pokoja",
		 
		 Time::get(mktime(0,0,0,1,1,2014)) =>"Deň vzniku Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,1,6,2014)) => "Zjavenie Pána (Traja králi) - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,4,18,2014)) => "Veľký piatok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,4,21,2014)) => "Veľkonočný pondelok - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,1,2014)) => "Sviatok práce - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,5,8,2014)) => "Deň víťazstva nad fašizmom - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,7,5,2014)) => "Sviatok svätého Cyrila a Metoda - štátny sviatok",
		 Time::get(mktime(0,0,0,8,29,2014)) => "Výročie SNP - štátny sviatok",
		 Time::get(mktime(0,0,0,9,1,2014)) => "Deň Ústavy Slovenskej republiky - štátny sviatok",
		 Time::get(mktime(0,0,0,9,15,2014)) => "Sedembolestná Panna Mária - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,1,2014) )=> "Sviatok všetkých svätých - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,11,17,2014)) =>"Deň boja za slobodu a demokraciu - štátny sviatok",
		 Time::get(mktime(0,0,0,12,24,2014)) => "Štedrý deň - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,25,2014)) => "Prvý sviatok vianočný - deň pracovného pokoja",
		 Time::get(mktime(0,0,0,12,26,2014)) => "Druhý sviatok vianočný - deň pracovného pokoja",
		);
		Calendar::$holidays = $holidays;
		return Calendar::$holidays;
	}
	public static function holidaysBetweenTimes($time1,$time2,$forward=true){
		$time1 = Time::get($time1);
		$time2 = Time::get($time2);
		
		$holidays = Calendar::getHolidays();
		$ret = 0;
		if($time1 == $time2) return $ret;
		if($forward){
			$time = min($time1,$time2);
			$to = max($time1,$time2);
		}else{
			$time = max($time1,$time2);
			$to = min($time1,$time2);
		}
		$time1 = Time::format($time1);
		$time2 = Time::format($time2);
		
		$d=0;
		$done = array();
		
		$date1 = getdate(Time::getUnix($time1));
		$date2 = getdate(Time::getUnix($time2));
		$pocetdni =  floor(abs(Time::get(mktime(0,0,0,$date2["mon"],$date2["mday"],$date2["year"]))-Time::get(mktime(0,0,0,$date1["mon"],$date1["mday"],$date1["year"])))/(Time::span(24*3600)));
		$zostatok = abs($time2-$time1)%(Time::span(24*3600));
		
		$days=$pocetdni;
		while($days>=0){
			$date = getdate(Time::getUnix($time));
			$d = Time::get(mktime(0,0,0,$date["mon"],$date["mday"],$date["year"]));
			
			if($date["wday"] == 6 || $date["wday"] == 0 || array_key_exists($d,$holidays)){
				$ret += Time::span(3600*24);
			}else{
				$days--;
			}
			if($forward){
				$time+=Time::span(24*3600);
			}else{
				$time-=Time::span(24*3600);
			}
		}
		if($zostatok){
			if($forward){
				$time-=Time::span(24*3600);
				$to = max($to,$time);
			}else{
				$time+=Time::span(24*3600);
				$to = min($to,$time);
			}
			$date = getdate(Time::getUnix($to));
			$d2 = Time::get(mktime(0,0,0,$date["mon"],$date["mday"],$date["year"]));
			if($d != $d2){
				$time = $to;
				while($date["wday"] == 6 || $date["wday"] == 0 || in_array($d2,$holidays)){
					$ret += Time::span(3600*24);
					if($forward){
						$time+=Time::span(24*3600);
					}else{
						$time-=Time::span(24*3600);
					}
					$date = getdate(Time::getUnix($time));
					$d2 = Time::get(mktime(0,0,0,$date["mon"],$date["mday"],$date["year"]));
				}
			}
			
			
		}

		return $ret;
	}
	public function __construct($id = "default"){
		if($retid = @$_REQUEST["retid"]){
			$this->modal = true;
			$id = $retid;
		}
		$now = getdate();
		
		if($y = \AsyncWeb\Storage\Session::get("RS__Cal_".$id."_y")){
			$this->year=$y;
		}else{
			$this->year = $now["year"];
		}
		if($m = \AsyncWeb\Storage\Session::get("RS__Cal_".$id."_m")){
			$this->month = $m;
		}else{
			$this->month = $now["mon"];
		}
		
		if(array_key_exists("RS__Cal_".$id."_m",$_REQUEST)) $this->month = (int)$_REQUEST["RS__Cal_".$id."_m"];
		if(array_key_exists("RS__Cal_".$id."_y",$_REQUEST)) $this->year = (int)$_REQUEST["RS__Cal_".$id."_y"];
		
		if($this->month == 0){
			$this->month = 12;
			$this->year --;
		}
		if($this->month >= 13){
			$this->month = 1;
			$this->year ++;
		}
		$this->id=$id;
		\AsyncWeb\Storage\Session::set("RS__Cal_".$id."_m",$this->month);
		\AsyncWeb\Storage\Session::set("RS__Cal_".$id."_y",$this->year);
		if($id == "modal") $this->modal = true;
		
	}
	public function show(){
		$ret = "";
		$ret .= $this->style();
		$ret .= $this->script();
		$ret .=$this->getMonth($this->month,$this->year);; 
		return $ret;
	}
	public function showStyle($bool=true){
		if($bool){
			$this->style = $style = ".calendar{font-size:smaller;width:100%;border:1px solid gray;}.calendar .info{text-align:center;} .calendar .mday{border:1px solid gray; text-align: center;} .calendar .mtoday{border:2px solid red; text-align: center;} .calendar .nmday{text-align:center; background-color:#eeeeee;}";
		}else{
			$this->style =	false;
		}
	}
	private function style(){
		if(!$this->style) return "";
		$ret= '<style type="text/css">'.$this->style.'</style>';
		$this->style=false;
		return $ret;
	}
	private $scriptshown = false;
	private function script(){
		if($this->scriptshown) return "";
		$this->scriptshown = true;
		
		if($this->modal){
			return '<script type="text/javascript">function RS_calendar(day){window.opener.document.getElementById(\''.$this->id.'\').value=day;window.close();}'."\n".$this->scriptMakeSelectableYear()."\n".$this->scriptMakeSelectableMonth().'</script>';
		}else{
			return '<script type="text/javascript">function RS_calendar(day){}'."\n".$this->scriptMakeSelectableYear()."\n".$this->scriptMakeSelectableMonth().'</script>';
		}
	}
	private function scriptMakeSelectableYear(){
		$ret = 'jQuery("document").ready(function() {
			$("#curyear").click(function() {
				var curyear = $("#icuryear").val();
				$("#curyear").hide();
				$("#curyearform").html(\'<select id="yearselect">';
				for($i=1900;$i<date("Y")+20;$i++) $ret.='<option>'.$i.'</option>';
				$ret.='</select>\');
				$("#yearselect").val(curyear);
				$("#yearselect").change(function() {
					var value = $("#yearselect option:selected").val();
					window.location.href = "'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_y"=>'"+value+"'),true,"?",true,false,true).'";
				});
			});
		});' ;
		return $ret;
	}
	private function scriptMakeSelectableMonth(){
		$ret = 'jQuery("document").ready(function() {
			$("#curmon").click(function() {
				var curmon = $("#icurmon").val();
				$("#curmon").hide();
				$("#curmonform").html(\'<select id="monselect">';
				for($i=1;$i<=12;$i++) $ret.='<option>'.$i.'</option>';
				$ret.='</select>\');
				$("#monselect").val(curmon);
				$("#monselect").change(function() {
					var value = $("#monselect option:selected").val();
					window.location.href = "'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_m"=>'"+value+"'),true,"?",true,false,true).'";
				});
			});
		});' ;
		return $ret;
	}
	private function getMonth($month,$year){
		if($this->modal){$add1='&amp;retid='.$_REQUEST["retid"];}else{$add1="";}
		$ret  = '<input type="hidden" id="icuryear" name="icuryear" value="'.$year.'" /><input type="hidden" id="icurmon" name="icurmon" value="'.$month.'" /><div class="calendartop"><table class="calendar_header"><tr><td>';
		$ret .= '<a href="'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_y"=>$this->year-1),true,"?").'">&lt;&lt;</a></td><td>';
		$ret .= '<a href="'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_m"=>$this->month-1),true,"?").'">&lt;</a></td><td class="info">';
		$ret .= '<div class="yinline" style="display:inline-block">'.\AsyncWeb\System\Language::get("year").': <span id="curyear">'.$year.'</span><span id="curyearform"></span></div>  <div class="minline" style="display:inline-block">'.\AsyncWeb\System\Language::get("month").': <span id="curmon">'.$month.'</span><span id="curmonform"></span></div></td><td style="text-align:right">';
		$ret .= '<a href="'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_m"=>$this->month+1),true,"?").'">&gt;</a></td><td style="text-align:right">';
		$ret .= '<a href="'.\AsyncWeb\System\Path::make(array("RS__Cal_".$this->id."_y"=>$this->year+1),true,"?").'">&gt;&gt;</a>';
		$ret .= '</td></tr></table><table class="calendar"><thead>
<tr><th>'.\AsyncWeb\System\Language::get("cal_Mo").'</th><th>'.\AsyncWeb\System\Language::get("cal_Tu").'</th><th>'.\AsyncWeb\System\Language::get("cal_We").'</th><th>'.\AsyncWeb\System\Language::get("cal_Th").'</th><th>'.\AsyncWeb\System\Language::get("cal_Fr").'</th><th>'.\AsyncWeb\System\Language::get("cal_Sa").'</th><th>'.\AsyncWeb\System\Language::get("cal_Su").'</th></tr>
</thead>
<tbody>';
		$beforefirstday = getdate(mktime(0, 0, 0, $month, 0, $year));
		$firstday =  getdate(mktime(0, 0, 0, $month, 1, $year));
		$lastday = getdate(mktime(0, 0, 0, $month+1, 0, $year));
		$ret .= '<tr>';
		$beforefirstday["wday"] = ($beforefirstday["wday"] + 6) % 7;
		$firstday["wday"] = ($firstday["wday"] + 6) % 7;
		if($firstday["wday"] != 0){
		$day = $beforefirstday["mday"] - $beforefirstday["wday"];
		for ($i = 0; $i <= $beforefirstday["wday"];$i++){
			$ret .= '<td class="nmday">'.($day++).'</td>';
		}
		}
		$day = 1;
		for($i=$firstday["wday"]; $i<7;$i++){
			
			$ret .= '<td class="';
			if(date("Y-n-j") == $this->year."-".$this->month."-".$day){
				$ret .= 'mtoday';
			}else{
				$ret .= 'mday';
			}
			$val = $this->year.'-'.sprintf("%02d",$this->month).'-'.sprintf("%02d",$day);
			if(Calendar::$format == "natural") $val = $day.".".$this->month.".".$this->year;
			
			$ret .= '" style="'.$this->addstyle.'" onclick="RS_calendar(\''.$val.'\')">'.($day++).'</td>';
		}
		$ret .='</tr>';
		$nday = 1;
		while($day <= $lastday["mday"]){
			$ret .='<tr>';
			for($i=0;$i<7;$i++){
				if($day <= $lastday["mday"]){
					$ret .= '<td class="';
			if(date("Y-n-j") == $this->year."-".$this->month."-".$day){
				$ret .= 'mtoday';
			}else{
				$ret .= 'mday';
			}
			$val = $this->year.'-'.sprintf("%02d",$this->month).'-'.sprintf("%02d",$day);
			if(Calendar::$format == "natural") $val = $day.".".$this->month.".".$this->year;
			
			$ret .= '" style="'.$this->addstyle.'" onclick="RS_calendar(\''.$val.'\')">'.($day++).'</td>';
				}else{
					$ret .= '<td class="nmday">'.($nday++).'</td>';
				}

			}
			$ret .='</tr>';
		}
		
		$ret .= '
</tbody>
</table></div>';
		return $ret;
	}
	public function form_dialog_select_date(){
		//window.opener.document.getElementById(\'image\').value=\''.$file.'\';window.close();
		if(!$retid = @$_REQUEST["retid"])return ;
		$ret = "";
		$this->modal = true;
		$this->addstyle="cursor:pointer;";
		$ret .= $this->script();
		$ret .= $this->style();
		$ret .= $this->show();
		return $ret;
	}
	public function htmlform_dialog_select_date(){
		return  '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.\AsyncWeb\System\Language::getLang().'">
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
 <title>'.\AsyncWeb\System\Language::get("Kalendár").'</title>
 '.$this->style().'
 <script type="text/javascript" src="/js/jquery.js"></script>
 '.$this->script().'
</head>
<body>'.$this->form_dialog_select_date().'</body></html>';;
	}
}

?>