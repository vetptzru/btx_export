<?php
AddEventHandler('crm', 'OnBeforeCrmDealUpdate', 'myDealViewHandler');

function myDealViewHandler(&$arFields) {
    forDebug();
}


function forDebug() {
  $file = "/Applications/MAMP/htdocs/local/___out__.txt";
  file_put_contents($file, $person, FILE_APPEND);
}