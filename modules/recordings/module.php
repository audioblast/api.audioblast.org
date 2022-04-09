<?php

function recordings_info() {
  $info = array(
    "mname" => "recordings",
    "version" => 1.0,
    "category" => "data",
    "table" => "recordings",
    "hname" => "Recordings",
    "desc" => "This endpoint allows for the querying of recording metadata held within audioBLAST!",
    "params" => array(
      "source" => array(
        "desc" => "Source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "id" => array(
        "desc" => "ID",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "name" => array(
        "desc" => "Recording name",
        "type" => "string",
        "default" => "",
        "column" => "Title",
        "op" => "contains",
        "autocomplete" => TRUE,
        "ac" => "dcterms:title"
      ),
      "taxon" => array(
        "desc" => "Taxon",
        "type" => "string",
        "default" => "",
        "column" => "taxon",
        "op" => "contains",
        "autocomplete" => TRUE,
        "suggest" => array(
          "desc"  => "Recordings of same taxon",
          "same_source" => FALSE
        ),
        "ac" => "dwc:scientificName"
      ),
      "filename" => array(
        "desc" => "File name",
        "type" => "string",
        "default" => "",
        "column" => "file",
        "op" => "contains"
      ),
      "author" => array(
        "desc" => "Author",
        "type" => "string",
        "default" => "",
        "column" => "author",
        "op" => "contains",
        "autocomplete" => TRUE,
        "suggest" => array(
          "desc" => "By same contributor in source",
          "same_source" => TRUE
        )
      ),
      "post_date" => array(
        "desc" => "Date the content was uploaded",
        "type" => "string",
        "column" => "post_date",
        "op" => "none"
      ),
      "human_size" => array(
        "desc" => "Human readble size of file",
        "type" => "string",
        "column" => "size",
        "op" => "none"
      ),
      "bytes" => array(
        "desc" => "File size",
        "type" => "range",
        "default" => "",
        "column" => "size_raw",
        "op" => "range"
      ),
      "mime" => array(
        "desc" => "MIME type of content",
        "type" => "string",
        "column" => "type",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE,
        "ac" => "dc:format"
      ),
      "recording_type" => array(
        "desc" => "Used to identify soundscape recordings",
        "type" => "string",
        "column" => "NonSpecimen",
        "default" => "",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "date" => array(
        "desc" => "Date",
        "type" => "string",
        "column" => "Date",
        "default" => "",
        "op" => "none"
      ),
      "time" => array(
        "desc" => "Time",
        "type" => "string",
        "column" => "Time",
        "default" => "",
        "op" => "none"
      ),
      "duration" => array(
        "desc" => "Duration",
        "type" => "range",
        "default" => "",
        "column" => "Duration",
        "op" => "range",
        "ac" => "ac:mediaDuration"
      ),
      "format" => array(
        "desc" => "Data represenation to return.",
        "type" => "string",
        "allowed" => array(
          "internal",
          "ac"
        ),
       "default" => "internal"
      ),
      "output" => array(
        "desc" => "The format of the returned data",
        "type" => "string",
        "allowed" => array(
          "JSON",
          "nakedJSON",
          "tabulator"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}

function recordings_embed_info() {
  $info = array(
    "recording" => array(
        "callback" => "recordings_embed",
        "desc" => "Returns embeddable recording",
        "returns" => "html",
        "embed" => "iframe",
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
              "html5",
              "zcjs"
            ),
            "default" => "html5"
          )
        )
      )
  );
  return($info);
}


function recordings_embed($f) {
  global $db;
  $sql  = "SELECT * FROM recordings WHERE source = '".$f['source']."' ";
  $sql .= "AND id = ".$f['id'].";";
  $res  = $db->query($sql);
  $file = $res->fetch_assoc();
  switch($f['output']) {
    case 'html5':
      return(recordings_embed_html5($f, $file));
      break;
    case "zcjs":
      return(recordings_embed_zcjs($f, $file));
      break;
  }
}


function recordings_embed_html5($f, $file) {
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

function recordings_embed_zcjs($f, $file) {
  $ret  = "<!DOCTYPE html>";
  $ret .= "<html>";
  $ret .= "<head>";
  $ret .= addJavaScript("zcjs");
  $ret .= addJavaScript("plotly");
  $ret .= "</head>";
  $ret .= "<body>";
  $ret .= '<div id="plot-here" width="100%"></div>';
  $ret .= '<script type="text/javascript">';
  $ret .= 'p = new ZCJS("plot-here");';
  $ret .= 'p.setURL("'.$file["file"].'");';
  $ret .= "</script>";
  $ret .= "</body>";
  $ret .= "</html>";
  $mret = array();
  $mret["html"] = $ret;
  return($mret);
}
