<?php
function cdn() {
  $ret = array(
    "zcjs" => "https://cdn.audioblast.org/zcjs/zcjs.js",
    "plotly" => "https://cdn.plot.ly/plotly-latest.min.js"
  );
  return($ret);
}

function addJavaScript($lib) {
  $cdn = cdn();
  if (array_key_exists($lib, $cdn)) {
    $ret = '<script type="text/javascript" src="'.$cdn[$lib].'"></script>';
    return($ret);
  }
  return(FALSE);
}
