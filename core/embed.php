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

/*
Render a single embed in-process: apply the endpoint's parameter defaults,
escape the caller-supplied inputs, and invoke the embed callback. Returns the
HTML string (or an error message), mirroring what the /embed/<mod>/<ep>/ route
produces but without an HTTP round-trip back to ourselves.
*/
function renderEmbed($db, $modname, $epname, $inputs) {
  loadModule($modname);
  if (!function_exists($modname."_embed_info")) {
    return("No embed info for module.");
  }
  $embeds = call_user_func($modname."_embed_info");
  if (!array_key_exists($epname, $embeds)) {
    return("Module does not have requested embed");
  }
  $ep = $embeds[$epname];
  $params = array();
  foreach ($ep["params"] as $pname => $pinfo) {
    if (isset($inputs[$pname])) {
      $params[$pname] = mysqli_real_escape_string($db, $inputs[$pname]);
    } else if (isset($pinfo["default"])) {
      $params[$pname] = $pinfo["default"];
    }
  }
  $mret = call_user_func($ep["callback"], $params);
  return($mret["html"] ?? "");
}

function embedPage() {
  global $db;
  // Build the embed list and render each preview in-process. This page used to
  // issue one HTTP request to our own API for the list and another per
  // module/endpoint/output combination -- dozens of self-requests, each
  // re-bootstrapping the framework, to render one page. loadModules() is
  // memoised, so this is now a single cheap in-process pass.
  $modules = loadModules();
  // Raw values are handed to renderEmbed (which escapes them for SQL); the
  // URL-encoded copies are only used to build the copy-paste embed URLs.
  $rawsource = $_GET["source"] ?? "";
  $rawid = $_GET["id"] ?? "";
  $source = urlencode($rawsource);
  $id = urlencode($rawid);
  foreach ($modules as $modname => $modinfo) {
    if (!function_exists($modname."_embed_info")) {continue;}
    $embeds = call_user_func($modname."_embed_info");
    print("<h2>".$modname."</h2>");
    foreach ($embeds as $epname => $epinfo) {
      print("<h3>".$epname."</h3>");
      if (!isset($epinfo["params"]["output"]["allowed"])) {continue;}
      foreach ($epinfo["params"]["output"]["allowed"] as $output) {
        print("<h4>".$output."</h4>");
        $url = "http://api.audioblast.org/embed/".$modname."/".$epname."/?source=".$source."&id=".$id."&output=".$output;
        print(renderEmbed($db, $modname, $epname, array(
          "source" => $rawsource,
          "id" => $rawid,
          "output" => $output
        )));
        print("<br><textarea style='width: 100%'>");
        switch ($epinfo["embed"]) {
          case "iframe":
            print("<iframe src='".htmlspecialchars($url, ENT_QUOTES)."' style='border: none; width: 100%;'></iframe>");
        }
        print("</textarea>");
      }
    }
  }
}
