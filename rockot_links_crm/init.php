<?php
require_once(__DIR__ . '/prolog_handler.php');

AddEventHandler('main', 'OnProlog', 'myPrologHandler');

AddEventHandler("main", "OnEndBufferContent", "addMyJavaScript");

function addMyJavaScript(&$content) {
    $script = '<script type="text/javascript">console.log("Мой JavaScript-код выполнен!");</script>';
    $content = str_replace('</body>', $script.'</body>', $content);
}