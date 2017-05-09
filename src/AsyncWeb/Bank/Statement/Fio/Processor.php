<?php
namespace AsyncWeb\Bank\Statement\Fio;
use \AsyncWeb\DB\DB;
class Processor implements \AsyncWeb\Bank\Statement\ProcessorInterface {
    public $debug = false;
    public $TABLE = "bank";
    public $emails = array();
    public $from = "info";
    protected $token = null;
    protected $callbacks = array();
    public function __construct($token) {
        $this->token = trim($token);
    }
    public function ProcessStatement() {
        $new = array();
        if (!$this->token) {
            throw new \Exception("No token provided");
        }
        if ($this->debug) {
            echo "downloading statement\n";
        }
        $xmltext = $this->getXMLStatement();
        if ($this->debug) var_dump(file_put_contents("test03.html", $xmltext));
        if (strlen($xmltext) < 10) {
            echo "E00\n";
            \AsyncWeb\Cron\CLI::e();
        }
        $dom = new \DomDocument();
        @$dom->loadXML($xmltext);
        if (!$dom) @$dom->loadHTML($xmltext);
        if (!$dom) {
            echo "E01\n";
            \AsyncWeb\Cron\CLI::e();
        }
        $xpath = new \DomXpath($dom);
        if ($this->debug) var_dump(file_put_contents("test04.html", $dom->saveXML()));
        if (!$xpath) {
            echo "E02\n";
            \AsyncWeb\Cron\CLI::e();
        }
        if (!$xpath->query("//accountId")->item(0)) {
            echo "E03\n";
            \AsyncWeb\Cron\CLI::e();
        }
        $this->account = $xpath->query("//accountId")->item(0)->nodeValue;
        $email = "";
        $total = 0;
        foreach ($xpath->query("//Transaction") as $transaction) {
            $data = array();
            $data["myaccount"] = $this->account;
            $col = "column_1";
            $dbcol = "value";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "currency";
            $col = "column_14";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "date";
            $col = "column_0";
            if ($xpath->query($col, $transaction)->item(0)) $date = $data[$dbcol] = date("Y-m-d", $time = strtotime($xpath->query($col, $transaction)->item(0)->nodeValue));
            if ($time < strtotime("2013-07-24")) continue;
            $dbcol = "bank";
            $col = "column_3";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "account";
            $col = "column_2";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "note";
            $col = "column_16";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "var";
            $col = "column_5";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "const";
            $col = "column_4";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            $dbcol = "spec";
            $col = "column_6";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            //$dbcol = "const";$col ="column_2"; if($xpath->query($col,$transaction)->item(0)) $data[$dbcol] = $xpath->query($col,$transaction)->item(0)->nodeValue;
            //$data["const"] = $row[5];
            //		$data["var"] = $row[6];
            //		$data["spec"] = $row[7];
            $dbcol = "id2";
            $col = "column_22";
            if ($xpath->query($col, $transaction)->item(0)) $data[$dbcol] = $xpath->query($col, $transaction)->item(0)->nodeValue;
            if ($data["id2"]) $id = md5($data["id2"]);
            if (!$id) foreach ($data as $key => $value) {
                if ($key == "note") continue;
                if ($key == "var") continue;
                if ($key == "const") continue;
                if ($key == "spec") continue;
                $id = md5($id . $key . $value);
            }
            $b = DB::gr($this->TABLE, $id);
            if (!$b) {
                if (strtotime($date) > time() - 3600 * 24) {
                    $data["time_create"] = time();
                } else {
                    $data["time_create"] = strtotime($date);
                }
                $total+= $data["value"];
                $data["state"] = 1;
                $ret = DB::u($this->TABLE, $id, $data);
                if ($ret === 1) {
                    $email.= "<div>Bank transaction: " . $data["account"] . "-" . $data["myaccount"] . ": " . $data["value"] . " " . $data["currency"];
                    if (@$data["var"] || @$data["spec"]) {
                        $email.= " :Var., spec. symbol:" . @$data["var"] . " " . @$data["spec"];
                    }
                    $email.= "</div>\n";
                    $new[] = $data;
                }
            }
        }
        if ($total) {
            if ($this->debug) var_dump("spracovane: $total");
            $totaln = $xpath->query("//closingBalance")->item(0);
            if ($totaln) {
                if ($data["myaccount"]) {
                    $row = DB::gr($this->TABLE, array("myaccount" => $data["myaccount"]), array(), array("c" => "sum(value)"));
                    //var_dump($row["c"]);
                    $total = $totaln->nodeValue;
                    $rozdiel = $total - $row["c"];
                    if (abs($rozdiel) >= 0.01) {
                        $rozdiel = round($rozdiel, 3);
                        if ($this->debug) var_dump("Rozdiel: " . ($total - $row["c"]));
                        DB::u($this->TABLE, md5(uniqid()), array("myaccount" => $data["myaccount"], "value" => ($rozdiel), "currency" => "EUR", "date" => date("Y-m-d"), "note" => "Vysporiadanie rozdielu", "time_create" => time()));
                        $email.= "<div>Nasiel sa rozdiel na ucte!! Podla vypisu: $total Podla IS: " . $row["c"] . " Rozdiel: " . ($rozdiel) . "</div>\n";
                    }
                }
            }
            $sendtoall = false;
            if ($email) {
                $email = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><div style="font-size:130%; color: navy; font-weight:bold;">Gimmick.In: Nové prevody na úète</div><div style="font-size: smaller; color:#999999;">Zostatok: ' . $total . ' EUR</div>' . $email . "<br/><div>Tento email je urceny vyhradne pre Samuela Zuba. Ak ste ho dostali omylom, vymazte ho a upozornite ho!!.</div><div>" . $this->user . " :: " . $this->account . "</div>";
                if ($this->emails) {
                    foreach ($this->emails as $to) {
                        \AsyncWeb\Email\Email::send($to, "Bankovy prevod", $email, $this->from, array(), "text/html; charset=UTF-8");
                    }
                } else {
                }
            }
            foreach ($this->callbacks as $callback) {
                if (substr($callback, 0, 3) == "PHP") {
                    \AsyncWeb\System\Execute::run($callback, $new);
                } else {
                    $callback($new);
                }
            }
        }
        return true;
    }
    private function getXMLStatement() {
        $from = date("Y-m-d", time() - 3600 * 24 * 30);
        $to = date("Y-m-d");
        $token = $this->token;
        $page = "https://www.fio.cz/ib_api/rest/periods/$token/$from/$to/transactions.xml";
        if ($this->debug) echo "$page\n";
        $data = \AsyncWeb\Connectors\Page::get($page, array(), array()); //CURLOPT_SSL_VERIFYPEER=>false
        if ($this->debug) echo "OK: " . strlen($data) . "\n";
        return $data;
    }
    public function NewTransactionCallback($callback) {
        $this->callbacks[] = $callback;
    }
}
