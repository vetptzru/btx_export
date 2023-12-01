<?php
function onEndBufferContentHandler(&$content) {
  $script = '<script type="text/javascript">alert(123)</script>';
  $content = str_replace('</body>', $script.'</body>', $content);
}

AddEventHandler("main", "OnEndBufferContent", "onEndBufferContentHandler");

