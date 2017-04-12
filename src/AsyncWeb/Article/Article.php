<?php
namespace AsyncWeb\Article;
interface Article {
    public function check();
    public function showForm();
    public function makeArticle(&$articlerow);
}
