<?php
/**
 * MakeDBView
 *
 *@author Ludovit Scholtz ludovit@scholtz.sk
 *@version 2.0.1
 *
 * 8.7.2012  BugFix -> Export did not support DB selection
 *
 * 1.4.2012  BugFix -> sort is working now
 *
 * 21.11.2011 MakeDBView -> MakeDBView
 *             zrusena podpora tabuliek ktore nie su od-do
 *
 * 17.1.2010 Pridana podpora pre kontrolu struktury db MakeDBView::tryRepair($data);
 *    if(!MakeDBView::$repair) return;
 *
 * Pridana podpora pre
 *
 *   pridana podpora pre od do
 *
 *  	15.11.07 "brokerska_spolocnost"=>array("name"=>"Number","table"=>"brokerska_spolocnost","refalias"=>"br0","refid"=>"id2","refcol"=>array(
 *		  array("type"=>"col","value"=>"kod_brokerskej_spol","od"=>"od","do"=>"do"),
 *		)),
 *
 * 11.7.2011, pridany filter pre DB: "filter"=>array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select");
 * 11.7.2011, pridany filter pre or: "filter"=>array("type"=>"or","filters"=>array(..));
 *
 *
 */
namespace AsyncWeb\View;
use AsyncWeb\System\Path;
use AsyncWeb\View\TableView;
use AsyncWeb\DB\DB;
use AsyncWeb\System\Language;
use AsyncWeb\Frontend\URLParser;
class MakeDBView {
    private static $form = array();
    public static function getMFResults($data = array()) {
        if (@$data["useForms"] && (!MakeDBView::$form || !@MakeDBView::$form[$data["uid"]])) {
        }
        if (@MakeDBView::$form[$data["uid"]]) {
            return MakeDBView::$form[$data["uid"]]->show_results();
        }
    }
    public static function make($data, $form = null) {
        if (!$data) return;
        if (isset($data["rights_display"]) && $data["rights_display"]) {
            if (!\AsyncWeb\Objects\Group::isInGroupId($data["rights_display"])) return false;
        }
        $data["uid"] = \AsyncWeb\Text\Texts::clear_($data["uid"]);
        if ($form) {
            MakeDBView::$form[$data["uid"]] = $form;
        }
        if (isset($data["iter_per_page"])) $data["iter"]["per_page"] = $data["iter_per_page"];
        if (@$data["useForms"] && (!MakeDBView::$form || !@MakeDBView::$form[$data["uid"]])) {
            MakeDBView::$form[$data["uid"]] = new \AsyncWeb\View\MakeForm($data);
        }
        if (@$data["useForms"] && @$data["allowInsert"] && URLParser::v("insert_data_" . $data["uid"])) {
            $r = "";
            $r.= MakeDBView::$form[$data["uid"]]->show_results();
            $r.= MakeDBView::$form[$data["uid"]]->show("INSERT");
            return $r;
        }
        if (@$data["useForms"] && @$data["allowUpdate"] && URLParser::v($data["uid"] . "___UPDATE1")) {
            $r = "";
            $r.= MakeDBView::$form[$data["uid"]]->show_results();
            $r.= MakeDBView::$form[$data["uid"]]->show("UPDATE2");
            return $r;
        }
        return MakeDBView::makeTableView($data);
    }
    private static function getInnerDBColConfig(&$row, &$colsettings) {
        if (is_array($colsettings)) {
            $ret = "";
            foreach ($colsettings as $setting) {
                if (is_array($setting)) {
                    switch ($setting["type"]) {
                        case "data":
                            $ret.= $setting["value"];
                        break;
                        case "col":
                            $ret.= $row[$setting["value"]];
                        break;
                    }
                } else {
                    $ret.= $row[$setting];
                }
            }
            return $ret;
        } else {
            return $row[$colsettings];
        }
    }
    private static $distinctvals = array();
    private static function checkDistinct(&$row, &$data) {
        // return false if row is unique
        // return true if row is repeating
        if (!isset($data["distinct"])) return false;
        $val = "";
        foreach ($data["distinct"] as $col) {
            $val.= md5($row[$col]) . "-";
        }
        if (isset(MakeDBView::$distinctvals[$data["uid"]][$val])) {
            return true;
        }
        MakeDBView::$distinctvals[$data["uid"]][$val] = true;
        return false;
    }
    private static function makeTableView($data) {
        $ret = "";
        try {
            $cols = array();
            foreach ($data["col"] as $col => $info) {
                if (isset($info["data"]["col"])) $col = $info["data"]["col"];
                if (is_numeric($col)) {
                    $col = "C" . substr(md5($col), 0, 9);
                    $row[$col] = $col;
                    $info["data"]["col"] = $col;
                }
                TableView::$INIT = true; //init TableView
                $display = false;
                $exportable = false;
                $usg = "DBVs";
                if (isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg, $info["usage"]))) {
                    $display = true;
                } else {
                }
                $usg = "DBVe";
                if (isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg, $info["usage"]))) {
                    $exportable = true;
                } else {
                }
                if (!$display && !$exportable) continue;
                if (@$info["virtual"] && isset($info["show"]) && !$info["show"]) continue;
                if (@$info["show"] === false) continue;
                $name = Language::get($info["name"]);
                $sortable = true;
                $filterable = false;
                if (@$data["show_filter"]) $filterable = true;
                if (@$info["do_not_sort"] || @$data["disable_order"]) {
                    $sortable = false;
                }
                $datatype = @$info["data"]["type"];
                if (!$datatype) $datatype = @$info["data"]["datatype"];
                $dvc = null;
                if ($info["form"]["type"] == "checkbox") {
                    $dvc = new CheckBoxDataViewCell($info["filter"]["option"]);
                } elseif (isset($info["form"]["dvc"]) && is_a($info["form"]["dvc"],"\\AsyncWeb\\View\\DataViewCell")) {
                    $dvc = $info["form"]["dvc"];
                } elseif (@$info["data"]["datatype"] == "number") {
                    $dvc = new NumberDataViewCell();
                } elseif (@$info["filter"]["type"] == "date") {
                    $format = "d.m.Y";
                    if ($info["filter"]["format"]) $format = $info["filter"]["format"];
                    $dvc = new DateDataViewCell($format);
                } elseif (@$info["data"]["datatype"] == "date") {
                    $format = "d.m.Y";
                    $dvc = new DateDataViewCell($format);
                } elseif (@$info["data"]["fromTable"] && @$info["data"]["fromColumn"]) {
                    $dvc = new BasicDataViewCell(); //DBValueDataViewCell($info["data"]["fromTable"],$info["data"]["fromColumn"],@$info["data"]["dictionary"],@$info["data"]["where"]);
                    
                } elseif (@$info["form"]["type"] == "textarea") {
                    $dvc = new BasicDataViewCell();
                } elseif (@$info["form"]["type"] == "textbox") {
                    $dvc = new BasicDataViewCell();
                } elseif (@$info["filter"]["type"] == "option") {
                    $dvc = new SelectionDataViewCell($info["filter"]["option"]);
                } elseif (@$info["filter"]["type"] == "radio") {
                    $dvc = new SelectionDataViewCell($info["filter"]["option"]);
                } elseif (@$info["form"]["type"] == "tinyMCE") {
                    $dvc = new TextDataViewCell();
                } else {
                    $dvc = new BasicDataViewCell();
                }
                $config = new THViewCellConfig($col, $name, $display, $sortable, $filterable, $exportable, $dvc);
                $cols[] = new BasicTHViewCell($config);
            }
            $update = false;
            $delete = false;
            if (isset($data["rights"])) {
                if (isset($data["rights"]["update"])) { // ak sa vyzaduju prava na vkladanie, tak ich over
                    if ($data["rights"]["update"] == "") $update = true; // kazdy moze upravit udaje ak je nastaveny insert, ale ak ma praznu hodnotu
                    if (\AsyncWeb\Objects\Group::exists($data["rights"]["update"])) { // ak existuje dane id skupiny
                        if (\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["update"])) $update = true;
                    } else { // inak existuje nazov skupiny
                        if (\AsyncWeb\Objects\Group::userInGroup($data["rights"]["update"])) $update = true;
                    }
                } else {
                    //$update = true;
                    
                }
            }
            if (isset($data["rights"])) {
                if (isset($data["rights"]["delete"])) { // ak sa vyzaduju prava na vkladanie, tak ich over
                    if ($data["rights"]["delete"] == "") $delete = true; // kazdy moze upravit udaje ak je nastaveny insert, ale ak ma praznu hodnotu
                    if (\AsyncWeb\Objects\Group::exists($data["rights"]["delete"])) { // ak existuje dane id skupiny
                        if (\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["delete"])) $delete = true;
                    } else { // inak existuje nazov skupiny
                        if (\AsyncWeb\Objects\Group::userInGroup($data["rights"]["delete"])) $delete = true;
                    }
                } else {
                    //$delete = true;
                    
                }
            }
            if ($update) {
                $cols[] = new BasicTHViewCell(new THViewCellConfig("update", "", Language::get("Edit item"), $display = true, $sortable = false, $filterable = false, $exportable = false, new UpdateItemDataViewCell()));
            }
            if ($delete) {
                $cols[] = new BasicTHViewCell(new THViewCellConfig("delete", "", Language::get("Delete item"), $display = true, $sortable = false, $filterable = false, $exportable = false, new DeleteItemDataViewCell()));
            }
            $thr = new THDataViewRow($cols);
            $c = array("id" => $data["uid"]);
            if (isset($data["texts"]["no_data"])) $c["no_data"] = $data["texts"]["no_data"];
            if (isset($data["no_data"])) $c["no_data"] = $data["no_data"];
            if (@$data["iter"]["per_page"]) $c["rows"] = $data["iter"]["per_page"];
            $config = new ViewConfig($c);
            $sort = new DataSort();
            if (@$data["order"]) {
                foreach ($data["order"] as $col => $type) {
                    $sort->add(new ColSort($col, $type));
                }
            }
            $i = 0;
            $filtercols = array();
            $res = DB::qb($data["table"], array("where" => @$data["where"], "cols" => MakeDBView::makeCols($data), "distinct" => true));
            if (!$res) {
                $err = DB::error();
                if (MakeDBView::tryRepair($data)) {
                    $res = DB::qb($data["table"], array("where" => @$data["where"], "cols" => MakeDBView::makeCols($data), "distinct" => true));
                }
                if (!$res) {
                    \AsyncWeb\Storage\Log::log("MakeDBView Error", $err);
                    if (MakeDBView::$repair) {
                        return Language::get("Error occured 0x01010458 Table probably does not contain valid columns as defined in DBView schema.  Autofix was not able to repair the issue!");
                    } else {
                        return Language::get("Error occured 0x01010457 Table probably does not contain valid columns as defined in DBView schema.  Set MakeDBView::\$repair for autofix.");
                    }
                }
            }
            $mydata = array();
            while ($row = DB::f($res)) {
                $origrow = $row;
                foreach ($data["col"] as $col => $info) {
                    if (isset($info["data"]["col"])) $col = $info["data"]["col"];
                    if (is_numeric($col)) {
                        $col = "C" . substr(md5($col), 0, 9);
                        $row[$col] = $col;
                        $info["data"]["col"] = $col;
                    }
                    $usg = "DBVs";
                    if (isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg, $info["usage"]))) {
                    } else {
                        continue;
                    }
                    if (@$info["virtual"] && isset($info["show"]) && !$info["show"]) continue;
                    if (@$info["show"] === false) continue;
                    if (@$info["data"]["dictionary"]) {
                        $row[$col] = Language::get($row[$col]);
                    }
                    if (@$info["data"]["fromTable"] && @$info["data"]["fromColumn"]) {
                        $cols = array();
                        if (is_array($info["data"]["fromColumn"])) {
                            foreach ($info["data"]["fromColumn"] as $col1) {
                                if ($col1["type"] == "col") $cols[] = $col1["value"];
                            }
                        } else {
                            $cols[] = $info["data"]["fromColumn"];
                        }
                        $where = array();
                        $where[] = array("col" => "id2", "op" => "eq", "value" => $row[$col]);
                        if (isset($info["data"]["where"]) && is_array($info["data"]["where"])) {
                            foreach ($info["data"]["where"] as $k => $v) {
                                if (!is_numeric($k)) {
                                    $where[$k] = $v;
                                } else {
                                    $where[] = $v;
                                }
                            }
                        }
                        $r = DB::qbr($info["data"]["fromTable"], array("where" => $where, "cols" => $cols));
                        if ($r) {
                            if (is_array($info["data"]["fromColumn"])) {
                                $row[$col] = "";
                                foreach ($info["data"]["fromColumn"] as $col1) {
                                    if ($col1["type"] == "col") {
                                        $row[$col].= $r[$col1["value"]];
                                    } elseif ($col1["type"] == "data") {
                                        $row[$col].= $col1["value"];
                                    }
                                }
                                if (@$info["data"]["dictionary"]) $row[$col] = Language::get($row[$col]);
                            } else {
                                $row[$col] = $r[$info["data"]["fromColumn"]];
                                if (@$info["data"]["dictionary"]) $row[$col] = Language::get($row[$col]);
                            }
                        } else {
                            if (isset($info["texts"]["no_data"])) {
                                $row[$col] = $info["texts"]["no_data"];
                            }
                        }
                    }
                    if (@$info["filter"]) $row[$col] = MakeDBView::filter($row[$col], $info["filter"], $origrow);
                }
                $mydata[] = $row;
            }
            $menuitems = array();
            $showinsert = false;
            if (isset($data["rights_insert"])) $data["rights"]["insert"] = $data["rights_insert"];
            if (isset($data["rights"])) {
                if (isset($data["rights"]["insert"])) { // ak sa vyzaduju prava na vkladanie, tak ich over
                    if ($data["rights"]["insert"] == "") $showinsert = true; // kazdy moze upravit udaje ak je nastaveny insert, ale ak ma praznu hodnotu
                    if (\AsyncWeb\Objects\Group::exists($data["rights"]["insert"])) { // ak existuje dane id skupiny
                        if (\AsyncWeb\Objects\Group::isInGroupId($data["rights"]["insert"])) {
                            $showinsert = true;
                        }
                    } else { // inak existuje nazov skupiny
                        if (\AsyncWeb\Objects\Group::userInGroup($data["rights"]["insert"])) $showinsert = true;
                    }
                }
            }
            if (@$data["useForms"] && @$data["allowInsert"] && $showinsert) {
                if (isset($data["texts"]["newItem"])) {
                    $newItemText = $data["texts"]["newItem"];
                } else {
                    $newItemText = Language::get("New item");
                }
                $menuitems[] = new TableMenuItemIconHref(Path::make(array('insert_data_' . $data["uid"] => 1, "showmenubox" => "")), $newItemText, "/img/icons/new.png");;
            }
            if (@$data["show_export"]) {
                $menuitems[] = new TableMenuExportXML();
                $menuitems[] = new TableMenuExportCSV();
                $menuitems[] = new TableMenuExportHTML();
            }
            $datasource = new ArrayDataSource($mydata);
            if (isset($data["distinct"])) $datasource->setDistinct($data["distinct"]);
            $tv = new TableView($datasource, $config, $thr, $sort, $menuitems);
            $ret.= $tv->show();
        }
        catch(Exception $exc) {
            //var_dump($exc);
            
        }
        return $ret;
    }
    private static function makeCols(&$data) {
        $cols = array("id" => "id", "id2" => "id2", "od" => "od", "do" => "do");
        foreach ($data["col"] as $col => $info) {
            if (isset($info["virtual"]) && $info["virtual"]) continue;
            if (isset($info["data"]["col"])) $col = $info["data"]["col"];
            $display = false;
            $export = false;
            $usg = "DBVs";
            if (isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg, $info["usage"]))) {
                $display = true;
            } else {
                $display = false;
            }
            $usg = "DBVe";
            if (isset($info["usage"]) && ((isset($info["usage"][$usg]) && $info["usage"][$usg]) || in_array($usg, $info["usage"]))) {
                $export = true;
            } else {
                $export = false;
            }
            if (!$export && !$display) continue;
            $cols[$col] = $col;
        }
        if (isset($data["usecols"])) foreach ($data["usecols"] as $col) {
            $cols[$col] = $col;
        }
        return $cols;
    }
    private static function execute($function, $params = null) {
        return \AsyncWeb\System\Execute::run($function, $params);
    }
    private static function filter($data, $filter, $row = array()) {
        $ret = "";
        switch (@$filter["type"]) {
            case "php":
                if (!isset($filter["params"]) || !is_array($filter["params"])) $filter["params"] = array();
                $filter["params"]["row"] = $row;
                $ret = MakeDBView::execute($filter["function"], $filter["params"]);
                break;
            case "db":
                /**
                 "filter"=>array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select");
                 */
                $filter["conds"][$filter["where"]] = $data;
                $row2 = DB::gr($filter["table"], $filter["conds"]);
                if ($row2) {
                    $ret = $row2[$filter["col"]];
                }
                break;
            case "or":
                /**
                 "filter"=>array("type"=>"or","filters"=>array(
                 array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
                 array("type"=>"db","table"=>"mytable","conds"=>array(),"where"=>"mycol","col"=>"select"),
                 ));
                 */
                foreach ($filter["filters"] as $f) {
                    $r = MakeDBView::filter($data, $f, $row);
                    if ($r) {
                        $ret = $r;
                        break;
                    }
                }
                break;
            case 'date':
                if ($data) {
                    $ret.= date($filter["format"], \AsyncWeb\Date\Time::getUnix($data));
                } else {
                    $ret.= "-";
                }
                break;
            case 'sprintf':
                $ret.= sprintf($filter["format"], $data);
                break;
            case 'number_format':
                $ret.= number_format($data, $filter["decimal"], $filter["desat_oddelocac"], $filter["oddelovac_tisicov"]);
                break;
            case 'path':
                if (!$data) $data = "-";
                $path = $filter["src"];
                if (is_array($filter["src"])) {
                    $move = array();
                    foreach ($filter["src"] as $k => $v) {
                        if (isset($row[$k])) {
                            $move[$v] = $row[$k];
                        }
                        if (isset($row[$v])) {
                            $move[$v] = $row[$v];
                        }
                    }
                    $path = \AsyncWeb\System\Path::make($move);
                }
                $ret.= '<a href="' . $path . '">' . $data . '</a>';
                break;
            case "urlparser":
                if (!$data) $data = "-";
                $path = $filter["src"];
                if (is_array($filter["src"])) {
                    $move = array();
                    if (isset($filter["src"]["var"])) foreach ($filter["src"]["var"] as $k => $v) {
                        if (isset($row[$k])) {
                            $move[$v] = $row[$k];
                        }
                        if (isset($row[$v])) {
                            $move[$v] = $row[$v];
                        }
                    }
                    $filter["src"]["var"] = $move;
                    $path = \AsyncWeb\Frontend\URLParser::merge2($filter["src"], \AsyncWeb\Frontend\URLParser::parse());
                }
                $ret.= '<a href="' . $path . '">' . $data . '</a>';
                break;
            case 'href':
                if (!$data) $data = "-";
                $ret.= '<a href="' . $filter["src"] . $row["id"] . '">' . $data . '</a>';
                break;
            case 'hrefID2':
                if (!$data) $data = "-";
                if (!($col = @$filter["col"])) {
                    $col = "id2";
                }
                $ret.= '<a href="' . $filter["src"] . $row[$col] . '">' . $data . '</a>';
                break;
            case 'hrefID3':
                if (!$data) $data = "-";
                if (!($col = @$filter["col"])) {
                    $col = "id2";
                }
                $ret.= '<a href="' . $filter["src"] . $row[$col] . '/">' . $data . '</a>';
                break;
            case 'text':
                $ret.= nl2br($data);
                break;
            case 'extra_htmlspecialchars':
                $ret.= htmlspecialchars($data);
                break;
            case 'option':
                if (@$filter["option"][$data]) {
                    $ret.= @$filter["option"][$data];
                } else {
                    $ret.= $data;
                }
                break;
            case 'add_after':
                $ret.= $data . $filter["data"];
                break;
            case 'add_before':
                $ret.= $filter["data"] . $data;
                break;
            default:
                $ret.= $data;
            }
            if (@$filter["filter"]) {
                $ret = MakeDBView::filter($ret, $filter["filter"], $row);
            }
            return $ret;
        }
        public static $repair = false;
        public static function tryRepair(&$data) {
            if (!MakeDBView::$repair) return;
            $update = array();
            $cols = array();
            if (isset($data["usecols"])) {
                foreach ($data["usecols"] as $col) {
                    $update[$col] = "0";
                }
            }
            foreach ($data["col"] as $col => $arr) {
                if (@$arr["function"]) continue;
                if (isset($arr["data"]["col"])) $col = $arr["data"]["col"];
                if (is_numeric($col)) continue;
                $update[$col] = "0";
            }
            if (isset($data["where"])) foreach (@$data["where"] as $k => $col) {
                if (is_numeric($k)) continue;
                if (is_array($col)) {
                    if (@$col["col"][0] == "-") continue;
                    $update[$col["col"]] = "0";
                } else {
                    $update[$k] = "0";
                }
            }
            $update["id2"] = "__TEST__";
            $update["od"] = \AsyncWeb\Date\Time::get();
            $update["do"] = 0;
            $update["edited_by"] = "__TEST__";
            $ret = DB::u($data["table"], $update["id2"], $update, $cols);
            if ($ret) {
                $table = DB::myAddSlashes($data["table"]);
                DB::query($q = "delete from `$table` where id2='__TEST__'");
            }
            return $ret;
        }
    }
    