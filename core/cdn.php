<?php
/**
 *  Functions to make it easier to use cdn.audioblast.org and
 *  other resources via the API.
 */


/**
 *  List of CDN resources
 */
function cdn() {
  $ret = array(
    "zcjs" => "https://cdn.audioblast.org/zcjs/zcjs.js",
    "plotly" => "https://cdn.plot.ly/plotly-latest.min.js"
  );
  return($ret);
}


/**
 *  Add a JavaScript resource from cdn() using HTML
 */
function addJavaScript($lib) {
  $cdn = cdn();
  if (array_key_exists($lib, $cdn)) {
    $ret = '<script type="text/javascript" src="'.$cdn[$lib].'"></script>';
    return($ret);
  }
  return(FALSE);
}
