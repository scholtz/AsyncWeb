<?php
namespace AsyncWeb\DataMining;
class WebMining4 extends \AsyncWeb\DataMining\WebMining2 {
    protected function createData() {
        $dataCol = array();
        if (!$this->doc) {
            $this->doc = @\DOMDocument::loadHtml($this->text);
            if (!$this->doc) return false;
        }
        if (!$this->xpath) {
            $this->xpath = new \DOMXPath($this->doc);
        }
        $ndata = array();
        foreach ($this->config["cols"] as $col2 => $value) {
            $ndata[$col2] = $this->getFilteredValue($col2);
            if ($this->config["id"] == $col2) {
                $id = $ndata[$col2];
            }
        }
        if (!$id) {
            $this->error = "ID not found\n";
            return array();
        }
        $i = 0;
        if (@$this->config["xpath"]) {
            $nodes = $this->xpath->query($this->config["xpath"]);
            foreach ($nodes as $node) {
                $i++;
                $data = array();
                $data[$this->config["id"]] = $id;
                $data["i"] = $i;
                foreach (@$this->config["iter"] as $col => $settings) {
                    //echo ".";
                    $node2 = $this->xpath->query($settings["xpath"], $node)->item(0);
                    $this->currentNode = $node2;
                    if ($node2) {
                        $value = $node2->nodeValue;
                        if (array_key_exists("select", $settings)) {
                            $value = $node2->$settings["select"];
                        }
                        if (@$settings["filter"]) {
                            $data[$col] = $this->filter($value, $settings["filter"]);
                        } else {
                            $data[$col] = $value;
                        }
                    }
                }
                foreach ($ndata as $k => $v) {
                    $data[$k] = $v;
                }
                $dataCol[$id][] = $data;
            }
        } else {
            $dataCol[$id][] = $ndata;
            $this->error.= "no xpath element?\n";
        }
        return $dataCol;
    }
}
?>