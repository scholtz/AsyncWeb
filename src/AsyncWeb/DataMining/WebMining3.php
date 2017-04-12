<?php
/**
 * Pre webmining tabuliek
 *
 */
namespace AsyncWeb\DataMining;
use AsyncWeb\Date\Time;
use AsyncWeb\DB\DB;
class WebMining3 {
    private $doc = null;
    private $xpath = null;
    private $text = null;
    private $config = null;
    private $time = null;
    /**
     * Config by mal obsahovat xpath upmiestnenie tabulky a pocet tr ktore preskocit
     *
     * @param unknown_type $config
     * @param unknown_type $text
     * @param unknown_type $time
     */
    public function spracuj($config, $text, $time) {
        $text = '<meta http-equiv="content-type" content="text/html; charset=utf-8"/>' . $text;
        $from_enc = "ISO-8859-2";
        if ($config["from_encoding"]) $from_enc = $config["from_encoding"];
        $text = str_replace($from_enc, "utf-8", $text);
        $this->text = iconv($from_enc, "utf-8", $text);
        $this->config = $config;
        $this->time = Time::get($time);
        $this->doc = @\DOMDocument::loadHtml($this->text);
        if (!$this->doc) {
            file_put_contents("test002.html", $text);
            exit;
        }
        //		var_dump($this->doc);
        $this->xpath = new \DOMXPath($this->doc);
        return $data = $this->createData();
    }
    private $currentNode = null;
    private function createData() {
        $ret = false;
        $table = $this->xpath->query($this->config["xpath"])->item(0);
        if (!$table) {
            var_dump($this->config);
            $this->doc->save("temp02.html");
            return false;
        }
        $nodes = $this->xpath->query("tr", $table);
        $i = 0;
        foreach ($nodes as $node) {
            $i++;
            $data = array();
            //			$this->doc->save("temp01.html",$node);
            if ($this->config["skip"]) {
                if ($i <= $this->config["skip"]) continue;
            }
            foreach ($this->config["customcols"] as $key => $value) {
                $data[$key] = $value;
            }
            foreach ($this->config["tablecols"] as $key => $value) {
                $node2 = $this->xpath->query("td[$key]", $node)->item(0);
                if ($node2) {
                    if (@$this->config["iterfilter"]) {
                        $data[$value] = $this->filter($node2->nodeValue, $this->config["iterfilter"]);
                    } else {
                        $data[$value] = $node2->nodeValue;
                    }
                }
            }
            $id = "";
            foreach ($data as $value) {
                $id = md5($id . $value);
            }
            $data["id2"] = $id;
            $conf = array();
            if (@$this->config["nocheck"]) $conf = null;
            if (isset($this->config["tracktable"])) $conf["tracktable"] = $this->config["tracktable"];
            $ret1 = DB::u($this->config["table"], $id, $data, $conf);
            if ($ret1) $ret = true;
        }
        return $ret;
        foreach ($this->config["iter"] as $col => $settings) {
            if (!$this->doc) {
                $this->doc = @\DOMDocument::loadHtml($this->text);
                if (!$this->doc) return false;
            }
            if (!$this->xpath) {
                $this->xpath = new \DOMXPath($this->doc);
            }
            foreach ($this->xpath->query($settings["xpath"]) as $node) {
                $this->currentNode = $node;
                $data = array();
                foreach ($this->config["cols"] as $col2 => $value) {
                    if ($this->config["id"] == $col2) {
                        $id = $this->getFilteredValue($col2);
                        $data["id2"] = $id;
                        //						continue;
                        
                    }
                    $data[$col2] = $this->getFilteredValue($col2);
                }
                if (@$settings["filter"]) {
                    $data[$col] = $this->filter($node->nodeValue, $settings["filter"]);
                } else {
                    $data[$col] = $node->nodeValue;
                }
                //				$id = $data[$this->config["id"]];
                $dataCol[$id][] = $data;
            }
        }
        return $dataCol;
    }
    private function checkAndUpdate($data) {
        // skontroluj ci su vsetky hodnoty tam
        // skontroluj ci nie je nieco navyse
        $table = $this->config["table"];
        foreach ($data as $id => $arrv) {
            $res = DB::qb($table, array("where" => array("id2" => $id), "cols" => array("od")));
            $data2 = array();
            while ($row = DB::f($res)) {
                $data2[] = $row;
                if ($this->time <= Time::get($row["od"])) { // ak lubovolny od je novsi ako dat subor, tak to nerob
                    echo "stary udaj\n";
                    return;
                }
            }
            foreach ($arrv as $row2) {
                // hladame zhodny row s row2
                $indb = false;
                foreach ($data2 as $row) {
                    $rovnaky = true;
                    foreach ($row2 as $key => $value) {
                        if ($row[$key] != $value) $rovnaky = false;
                    }
                    if ($rovnaky) $indb = true;
                }
                if (!$indb) {
                    echo "vkladam $id\n";
                    $row2["od"] = $this->time;
                    $config = array();
                    foreach ($this->config["cols"] as $col => $value) {
                        if (@$value["datatype"]) {
                            $config["cols"][$col] = $value["datatype"];
                        }
                    }
                    foreach ($this->config["iter"] as $col => $value) {
                        if (@$value["datatype"]) {
                            $config["cols"][$col] = $value["datatype"];
                        }
                    }
                    $config["keys"] = array($this->config["id"]);
                    if ($config["nocheck"]) $config = null;
                    if (isset($this->config["tracktable"])) $config["tracktable"] = $this->config["tracktable"];
                    DB::u($this->config["table"], md5(uniqid()), $row2, $config);
                }
            }
            foreach ($data2 as $row) {
                $inp = false;
                foreach ($arrv as $row2) {
                    $rovnaky = true;
                    foreach ($row2 as $key => $value) {
                        if ($row[$key] != $value) $rovnaky = false;
                    }
                    if ($rovnaky) {
                        $inp = true;
                    }
                }
                if (!$inp) {
                    echo "mazem $id\n";
                    DB::delete($this->config["table"], $row["id2"]);
                }
            }
        }
    }
    private function getFilteredValue($col) {
        $data = $this->getValue($col);
        if (@$this->config["cols"][$col]["filter"]) {
            $data = $this->filter($data, $this->config["cols"][$col]["filter"]);
        }
        return $data;
    }
    private function getValue($col) {
        if (@$this->config["cols"][$col]["xpath"]) {
            if (@$this->config["cols"][$col]["xpath_rel"]) {
                return $this->getXpathValue($this->config["cols"][$col]["xpath"], true);
            } else {
                return $this->getXpathValue($this->config["cols"][$col]["xpath"]);
            }
        }
        if (@$this->config["cols"][$col]["contains"]) {
            return $this->getContainsValue($this->config["cols"][$col]["contains"]);
        }
        if (@$this->config["cols"][$col]["value"]) {
            return $this->config["cols"][$col]["value"];
        }
    }
    private function getContainsValue($cont) {
        foreach ($cont as $key => $value) {
            if (stripos($this->text, $key)) {
                return $value;
            }
        }
    }
    private function getXpathValue($query, $relative = false) {
        //		file_put_contents("test003.html",$this->text);
        if (!$this->doc) {
            $this->doc = @\DOMDocument::loadHtml($this->text);
            if (!$this->doc) return false;
        }
        //		$this->doc->save("test002.html");
        if (!$this->xpath) {
            $this->xpath = new \DOMXPath($this->doc);
        }
        if ($relative && $this->currentNode) {
            $node = $this->xpath->query($query, $this->currentNode)->item(0);
        } else {
            $node = $this->xpath->query($query)->item(0);
        }
        //		var_dump($node);
        if ($node) {
            return $node->nodeValue;
        }
        return false;
    }
    private function filter($data, $filter) {
        if (@$filter["str_replace"]) {
            return $this->filter_str_replace($data, $filter);
        }
        if (@$filter["explode"]) {
            return $this->filter_explode($data, $filter);
        }
        if (@$filter["trim"]) {
            return $this->filter_trim($data, $filter);
        }
    }
    private function filter_str_replace($data, $filter) {
        if (is_array($filter["str_replace"])) {
            foreach ($filter["str_replace"] as $find => $repl) {
                $data = str_replace($find, $repl, $data);
            }
        }
        if (@$filter["filter"]) {
            $data = $this->filter($data, $filter["filter"]);
        }
        return $data;
    }
    private function filter_explode($data, $filter) {
        $expl_a = explode($filter["explode"], $data);
        $data = @$expl_a[$filter["explode_iter"]];
        if (@$filter["filter"]) {
            $data = $this->filter($data, $filter["filter"]);
        }
        return $data;
    }
    private function filter_trim($data, $filter) {
        $data = trim($data);
        if (@$filter["filter"]) {
            $data = $this->filter($data, $filter["filter"]);
        }
        return $data;
    }
}
?>