<?
// prolog_handler.php
function myPrologHandler() {
    // Ваш код...
    // Например, запись в лог, проверка условий, модификация данных и т.д.
    forDebug2();
}

function forDebug2() {
  $file = "/Applications/MAMP/htdocs/local/___out__.txt";
  file_put_contents($file, $person, FILE_APPEND);
}