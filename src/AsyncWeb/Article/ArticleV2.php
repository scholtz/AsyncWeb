<?php
namespace AsyncWeb\Article;
interface ArticleV2 extends \AsyncWeb\Article\Article {
    public function makeArticleRSS(&$articlerow);
}
