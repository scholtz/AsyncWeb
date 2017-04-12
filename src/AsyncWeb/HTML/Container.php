<?php
/**
 Example
 $cont = Container::Create("myContainerName");
 $cont->setBody("body");
 $cont->append("append");
 echo $cont->show();
 */
namespace AsyncWeb\HTML;
class Container {
    private $name = "_";
    private $header = "";
    private $body = "";
    public function __construct($name, $name2 = "large_box", $title = null, $body = null, $body_text = null) {
        if ($name) {
            $this->name = $name;
        } else {
            $this->name = $name2;
        }
        $this->header = $title;
        $this->body = $body;
        if ($body_text) $this->body = $body_text;
    }
    /**
     *@param $name Meno konaineru
     *@return Container Kontainer
     *
     */
    public static function Create($name = "_", $text = null) {
        return new Container($name, "large_box", null, $text);
    }
    public function setHeader($header) {
        $this->header = $header;
    }
    public function setBody($body) {
        $this->body = $body;
    }
    public function appendHeader($header) {
        $this->header.= $header;
    }
    public function appendBody($body) {
        $this->body.= $body;
    }
    public function append($body) {
        $this->appendBody($body);
    }
    public function show() {
        $ret = "";
        $ret.= '<div class="AWcontainer">';
        if ($this->header) {
            $ret.= '<div class="container_head">';
            $ret.= $this->header;
            $ret.= '</div>';
        }
        if ($this->body) {
            $ret.= '<div class="container_body">';
            $ret.= $this->body;
            $ret.= '</div>';
        }
        $ret.= '</div>';
        return $ret;
    }
}
?>