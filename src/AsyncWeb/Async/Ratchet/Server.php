<?php
namespace AsyncWeb\Async\Ratchet;

use AsyncWeb\Frontend\BlockManagement;
use AsyncWeb\Frontend\Block;
use AsyncWeb\Frontend\URLParser;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface {
    protected $clients;
	protected $methods = array();
	protected $rTemplate = null;
    public function __construct() {
        $this->clients = new \SplObjectStorage;
		\AsyncWeb\Storage\Log::log("App","Inicialized");
		
		$this->rTemplate = new RTemplate();
		
		$this->methods["/RTemplate/getTemplate"] = array("object"=>$this->rTemplate,"function"=>"getTemplate");
		$this->methods["/RTemplate/getData"] = array("object"=>$this->rTemplate,"function"=>"getData");
		
		echo "Ratchet server is running\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
		\AsyncWeb\Storage\Log::log("App","Connected");
        echo date("c").": New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
	
		echo ".";
        $numRecv = count($this->clients) - 1;
        //echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n" , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
		$rarr = array();
		Clients::$heartbeats[$from->resourceId] = Clients::$heartbeattime;
		if($m = json_decode($msg,true)){
			if($m["msg"] == "connect"){
				$ret = '{"msg":"connected","session":"'.md5(uniqid()).'"}';
				echo "returning: $ret\n";
				$from->send($ret);
			}else
			if($m["msg"] == "method"){
					//echo "method ".$m["method"]."\n";
					if(isset($this->methods[$m["method"]])){
						$params = array();
						if(isset($m["params"])) $params = $m["params"];
						$id = array();
						if(isset($m["id"])) $id = $m["id"];

						if(!isset($this->methods[$m["method"]]["type"]) || $this->methods[$m["method"]]["type"] == "sync"){
							$method = $this->methods[$m["method"]]["function"];
							if(method_exists($this->methods[$m["method"]]["object"],$method)){
								//echo "ok\n";
								$ret = $this->methods[$m["method"]]["object"]->$method($id,$params);
								//echo "ret:";var_dump($ret);
								
								if(is_array($ret)){
									$d = json_encode($ret);
								}else{
									$d = json_encode(array("msg"=>"result","id"=>$id,"result"=>$ret));
								}
								//echo "ret: $d\n";
								$from->send($d);
							}else{
								$from->send('{"msg":"result","id":"'.$m["id"].'","error":{"error":404,"reason":"Method not found","message":"Method '.$m["method"].' not found [404]  0x015213","errorType":"Meteor.Error"}}');
								echo "nemam method 0x914912\n";
							}
						}elseif($this->methods[$m["method"]]["type"] == "async"){
							//todo
							
							$from->send('{"msg":"result","id":"'.$m["id"].'","error":{"error":404,"reason":"Async call not implemented yet","message":"Async call '.$m["method"].' not implemented yet [500]","errorType":"Meteor.Error"}}');

							echo "todo 0x914913\n";
						}else{
							$from->send('{"msg":"result","id":"'.$m["id"].'","error":{"error":404,"reason":"Method not found","message":"Method '.$m["method"].' not found [404] 0x015215","errorType":"Meteor.Error"}}');
							echo "type not registered 0x914919\n";
						}
					}else{
						$from->send('{"msg":"result","id":"'.$m["id"].'","error":{"error":404,"reason":"Method not found","message":"Method '.$m["method"].' not found [404] 0x015216","errorType":"Meteor.Error"}}');
						echo "method not registered 0x914911\n";
					}				
			}else 
			if($m["msg"] == "ping"){
				$from->send('{"msg":"pong","id":"'.$m["id"].'"}');
			}elseif($m["msg"] == "pong"){
				echo("pong received:".$from->resourceId."\n");
				
				Clients::$heartbeats[$from->resourceId] = $m["id"];
			}elseif($m["msg"] == "sub"){

				Clients::add($from,"sub",array("id"=>$m["id"],"name"=>$m["name"]));
			}else 
			if($m["msg"] == "unsub"){
				//var_dump("unsub:".$m["name"]);
				//var_dump($from->resourceId);
				//Clients::remove($from->resourceId,"sub",array("id"=>$m["id"]));
			}
		}
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
		Clients::unsubscribe($conn);
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
		\AsyncWeb\Storage\Log::log("App","Error: ".$e->getMessage());
		Clients::unsubscribe($conn);
        $conn->close();
    }
}