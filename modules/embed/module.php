<?php

function embed_info() {
  $info = array(
    "mname" => "embed",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Embed",
    "desc" => "Allows embedding of audio files on the web.",

    "endpoints" => array(
      "recording" => array(
        "callback" => "embed_recording",
        "desc" => "Returns day phases for a date range",
        "returns" => "html",
        "params" => array(
          "source" => array(
             "desc" => "Source",
             "type" => "string",
             "default" => "",
             "column" => "source",
             "op" => "="
          ),
          "id" => array(
            "desc" => "ID",
            "type" => "string",
            "default" => "",
            "column" => "id",
            "op" => "="
          ),
          "title" => array(
            "desc" => "Include title in output?",
            "type" => "boolean",
            "allowed" => array(
              "true",
              "false"
            ),
            "default"=> "true"
          ),
          "credit" => array(
            "desc" => "Credit audioblast.org",
            "type" => "boolean",
            "allowed" => array(
              "true",
              "false"
            ),
            "default" => "true"
          ),
          "output" => array(
            "desc" => "Type of player to return.",
            "type" => "string",
            "allowed" => array(
              "html5"
            ),
            "default" => "html5"
          )
        )
      )
    )
  );
  return($info);
}

function embed_recording($f) {
  global $db;
  $sql  = "SELECT * FROM recordings WHERE source = '".$f['source']."' ";
  $sql .= "AND id = ".$f['id'].";";
  $res  = $db->query($sql);
  $file  = $res->fetch_assoc();

  switch($f['output']) {
    case 'html5':
      return embed_html5($f, $file);
      break;
  }
}


function embed_html5($f, $file) {
  $ret  = "<figure class='audioblast-embed-html5-figure'>";
  if ($f["title"] == "true") {
    $ret .= "<figcaption>".$file["Title"].":</figcaption>";
  }
  $ret .= "<audio ";
  $ret .= "controls ";
  $ret .= "src='".$file["file"]."'>";
  $ret .= "Your browser does not support the <code>audio</code> element.";
  $ret .= "</audio>";
  if ($f["credit"] == "true") {
    $ret .= "<p>Powered by <a href='https://audioblast.org'>Audioblast</a>.</p>";
  }
  $ret .= "</figure>";

  $mret = array();
  $mret["html"] = $ret;
  return($mret);
}
