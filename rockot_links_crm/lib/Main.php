<?php
namespace RockotLinksCRM;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Page\Asset;

class Main {
    public function appendJavaScriptAndCSS() {
      // Asset::getInstance()->addString("<script id='".$module_id."-params' data-params='".$options."'></script>", true);
      Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');
      // $this->log2file("js and css");
    }

    public function log() {
      // $this->log2file("simple log");
    }

    public function log2file($message = "none") {
      // file_put_contents("/Applications/MAMP/logs/deb.log", "$message\n", FILE_APPEND);
    }

}
