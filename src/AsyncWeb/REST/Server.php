<?php
namespace AsyncWeb\REST;
class Server {
    public static $Services = array();
    public static function Register($prepend = "/rest/", $classes = array(), $namespace = "\\") {
        $prepend = str_replace("/", "\\/", $prepend);
        foreach ($classes as $class) {
            foreach (get_class_methods($namespace . $class) as $method) {
                \AsyncWeb\System\Router::addRoute($match = '/^' . $prepend . $class . '\/' . $method . '\/(.*)$/', array("\\AsyncWeb\\REST\\Server", "Process"), false, array("class" => $namespace . $class, "method" => $method));
            }
        }
    }
    public static function Process($router) {
        header("Content-Type: application/json");
        try {
            $params = explode("/", @$router["matches"][1]);
            $ret = array();
            $ret["result"] = call_user_func_array(array($router["data"]["class"], $router["data"]["method"]), $params);
            $ret["status"] = "ok";
            echo json_encode($ret);
            exit;
        }
        catch(\Exception $exc) {
            $error = array("status" => "error", "text" => $exc->getMessage());
            echo json_encode($error);
            exit;
        }
    }
}
