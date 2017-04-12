<?php
namespace AsyncWeb\DefaultBlocks;
class File extends \AsyncWeb\Frontend\Block {
    protected function initTemplate() {
        $id = \AsyncWeb\Frontend\URLParser::v("h");
        if ($f = \AsyncWeb\DB\DB::gr("files", array("id2" => $id))) {
            \header('Content-Type: ' . $f["type"]);
            \header('Content-Length: ' . filesize($f["fullpath"]));
            \header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($f["fullpath"])) . ' GMT', true, 200);
            echo file_get_contents($f["fullpath"]);
            exit;
        }
        $this->template = "";
    }
}
