<?php

/*
Check if currently requested URI is treated as the embed page
*/
function isEmbedPage() {
  $parts = explode("?", $_SERVER['REQUEST_URI']);
  if ($parts[0]=="/standalone/embed/") {
    $parts2 = explode("/", $_SERVER['REQUEST_URI']);
    if (count($parts2)==4) {
      return(TRUE);
    }
  }
  return(FALSE);
}

function embedPage() {
  $embeds = json_decode(file_get_contents("http://api.audioblast.org/standalone/modules/list_embeds/?output=nakedJSON"));
  foreach ($embeds as $modname => $modinfo) {
    print("<h2>".$modname."</h2>");
    foreach ($modinfo as $epname  => $epinfo) {
      print("<h3>".$epname."</h3>");
      foreach ($epinfo->params->output->allowed as $output) {
        print("<h4>".$output."</h4>");
        $url = "http://api.audioblast.org/standalone/embed/".$epname."/?source=".$_GET["source"]."&id=".$_GET["id"]."&output=".$output;
        $response = file_get_contents($url);
        print($response);
        print("<br><textarea style='width: 100%'>");
        switch ($epinfo->embed) {
          case "iframe":
            print("<iframe src='".$url."' style='border: none; width: 100%;'></iframe>");
        }
        print("</textarea>");
      }
    }
  }
}
