<?php

/*
Check if currently requested URI is treated as the embed page
*/
function isEmbedPage() {
  $parts = explode("?", $_SERVER['REQUEST_URI']);
  if ($parts[0]=="/embed/") {
    $parts2 = explode("/", $_SERVER['REQUEST_URI']);
    if (count($parts2)==3) {
      return(TRUE);
    }
  }
  return(FALSE);
}

function embedPage() {
  $embeds = json_decode(file_get_contents("http://api.audioblast.org/standalone/modules/list_embeds/?output=nakedJSON"));
  // URL-encode user-controlled query params before placing them into the embed URL.
  $source = urlencode($_GET["source"] ?? "");
  $id = urlencode($_GET["id"] ?? "");
  foreach ($embeds as $modname => $modinfo) {
    print("<h2>".$modname."</h2>");
    foreach ($modinfo as $epname  => $epinfo) {
      print("<h3>".$epname."</h3>");
      foreach ($epinfo->params->output->allowed as $output) {
        print("<h4>".$output."</h4>");
        $url = "http://api.audioblast.org/embed/".$modname."/".$epname."/?source=".$source."&id=".$id."&output=".$output;
        $response = file_get_contents($url);
        print($response);
        print("<br><textarea style='width: 100%'>");
        switch ($epinfo->embed) {
          case "iframe":
            print("<iframe src='".htmlspecialchars($url, ENT_QUOTES)."' style='border: none; width: 100%;'></iframe>");
        }
        print("</textarea>");
      }
    }
  }
}
