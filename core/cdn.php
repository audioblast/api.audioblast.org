<?php
function cdn() {
  $ret = array(
    "zcjs" => "https://cdn.audioblast.org/zcjs/zcjs.js"
  );
  return($ret);
}

function addJavaScript($lib) {
  $cdn = cdn();
  if (array_key_exists($lib, $cdn)) {
    $ret = '<script type="text/javascript" src="'.$cdn[$lib].'"></scipt>';
    return($ret);
  }
  return(FALSE);
}
