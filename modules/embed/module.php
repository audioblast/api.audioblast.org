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
        "returns" => "data",
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
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      )
    )
  );
  return($info);
}

function embed_recording($f) {
  print_r(f);
}
