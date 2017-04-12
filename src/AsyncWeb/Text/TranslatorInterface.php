<?php
namespace AsyncWeb\Text;
interface TranslatorInterface {
    public function translate($text, $from, $to, $usecache = true);
}
