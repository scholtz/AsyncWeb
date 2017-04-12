<?php
namespace AsyncWeb\Article;
use AsyncWeb\Objects\Group;
use AsyncWeb\HTML\Container;
use AsyncWeb\System\Language;
use AsyncWeb\Storage\Session;
use AsyncWeb\HTTP\Header;
use AsyncWeb\View\MakeForm;
use AsyncWeb\Date\Time;
use AsyncWeb\Menu\MainMenu;
use AsyncWeb\System\Path;
class HTMLArticle implements ArticleV2 {
    private $form = null;
    private $show = false;
    private $editor = false;
    public static function getDefaultDate() {
        return date("d.m.Y H:i:s");
    }
    private function __construct() {
        CategoryArticle::addListener($this, "html");
        if (Group::is_in_group("HTMLEditor")) {
            if (isset($_REQUEST["newhtmlarticle"])) {
                Session::set("newhtmlarticle", "1");
                Header::s("reload", array("newhtmlarticle" => ""));
                exit;
            }
            if (isset($_REQUEST["finishArticleEditing"])) {
                Session::set("newhtmlarticle", "0");
            }
            $this->show = Session::get("newhtmlarticle");
            if ($this->show) {
                $form = array("table" => "articles", "col" => array(array("name" => "Text", "form" => array("type" => "tinyMCE", "theme" => "advanced"), "data" => array("col" => "text", "dictionary" => true), "usage" => array("MFi", "MFu", "MFd")), array("form" => array("type" => "value"), "data" => array("col" => "type"), "texts" => array("text" => "html"), "usage" => array("MFi", "MFu", "MFd")), array("form" => array("type" => "value"), "data" => array("col" => "category"), "texts" => array("text" => "PHP::\AsyncWeb\Menu\MainMenu::getCurrentId()"), "usage" => array("MFi", "MFu", "MFd")),
                //array("form"=>array("type"=>"value"),"data"=>array("col"=>"created"),"texts"=>array("text"=>"PHP::Time::get()"),"usage"=>array("MFi","MFu","MFd")),
                array("name" => "Time", "form" => array("type" => "textbox"), "data" => array("col" => "created", "datatype" => "date"), "texts" => array("default" => "PHP::\AsyncWeb\Article\HTMLArticle::getDefaultDate()"), "filter" => array("type" => "date", "format" => "d.m.Y H:i:s"), "usage" => array("MFi", "MFu", "DBVs", "DBVe")), array("name" => "Logintype", "form" => array("type" => "select"), "data" => array("col" => "logintype", "datatype" => "enum"), "filter" => array("type" => "option", "option" => array("all" => "Everyone see the category", "notlogged" => "Category is visible only to Unauthenticated", "logged" => "Category is visible only to Authenticated")), "usage" => array("MFi", "MFu", "MFd")), array("name" => "Group", "form" => array("type" => "selectDB"), "data" => array("col" => "group", "allowNull" => true, "dictionary" => true, "fromTable" => "groups", "fromColumn" => "name",), "texts" => array("nullValue" => "Vyber",), "usage" => array("MFi", "MFu", "MFd")),), "where" => array("type" => "html", "category" => "PHP::\AsyncWeb\Menu\MainMenu::getCurrentId()",), "order" => array("od" => "asc",), "uid" => "articles_html", "show_export" => true, "show_filter" => true, "allowInsert" => true, "allowUpdate" => true, "allowDelete" => true, "useForms" => true, "rights" => array("insert" => "HTMLEditor", "update" => "HTMLEditor", "delete" => "HTMLEditor",), "execute" => array("onInsert" => "PHP::\AsyncWeb\Article\HTMLArticle::onInsert", "onUpdate" => "PHP::\AsyncWeb\Article\HTMLArticle::onUpdate", "onDelete" => "PHP::\AsyncWeb\Article\HTMLArticle::onDelete",), "iter" => array("per_page" => "20"),);
                $this->form = new MakeForm($form);
                //$this->form = new make_form(file_get_contents("forms/editHTMLArticle.xml"));
                
            }
            $this->editor = true;
        }
    }
    private static $inst = null;
    public static function init() {
        if (HTMLArticle::$inst == null) HTMLArticle::$inst = new HTMLArticle();
    }
    public function check() {
        if (!$this->form) return false;
        return $this->form->show_results();
    }
    public function showForm() {
        if (!$this->form) return false;
        return $this->form->show("ALL");
    }
    public function makeArticleRSS(&$articlerow) {
        $nadpis = Language::get("HTML article");
        $text = Language::get($articlerow["text"]);
        if (($pos = strpos($text, "</h1>")) !== false) {
            $start = strpos($text, "<h1>") + 4;
            $nadpis = strip_tags(substr($text, $start, $pos - $start));
            $nadpis = html_entity_decode($nadpis, ENT_COMPAT | ENT_HTML401, "UTF-8");
            $text = substr($text, $pos + 5);
        }
        $path = str_replace("RSS=1", "", $_SERVER["REQUEST_URI"]);
        if (substr($path, -1) == "?") $path = substr($path, 0, -1);
        return ' <item>
  <guid>' . md5($articlerow["id2"] . "-ajfskajf") . '</guid>
  <title>' . $nadpis . '</title>
  <link>http://' . $_SERVER["HTTP_HOST"] . $path . '</link>
  <description>' . htmlspecialchars($text) . '</description>
  <pubDate>' . date("r", Time::getUnix($articlerow["created"])) . '</pubDate>
 </item>
';
    }
    public function makeArticle(&$articlerow) {
        $c1 = new Container("article");
        $c1->setBody(html_entity_decode(Language::get($articlerow["text"])));
        if ($this->editor && isset($articlerow["id"]) && (MainMenu::$editingmenu || MainMenu::$editingart)) {
            $c1->appendBody('<div class="editarticle">
<a href="' . Path::make(array("articles_html___UPDATE1" => 1, "articles_html___ID" => $articlerow["id"], "newhtmlarticle" => "1")) . '">' . Language::get("L__Edit_article") . '</a>
|
<a onclick="confirm(\'' . Language::get("L__Delete_article_confirm") . '\')?ret=true:ret=false;return ret;" href="' . Path::make(array("articles_html___DELETE" => 1, "articles_html___ID" => $articlerow["id"], "newhtmlarticle" => "1")) . '">' . Language::get("L__Delete_article") . '</a>
</div>');
        }
        return $c1->show();
    }
    public static function onInsert($articlerow) {
        Session::set("newhtmlarticle", "0");
    }
    public static function onUpdate($r) {
        Session::set("newhtmlarticle", "0");
    }
    public static function onDelete($r) {
        Session::set("newhtmlarticle", "0");
    }
}
