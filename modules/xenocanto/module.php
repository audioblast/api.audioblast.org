<?php

function xenocanto_info() {
  $info = array(
    "mname" => "xeno-canto",
    "version" => 1.0,
    "category" => "source",
    "hname" => "xeno-canto",
    "url" => "https://xeno-canto.org",
    "sources" => array(
      array(
        "type" => "recordings",
        //Harvested from the xeno-canto API by audioBlastIngest, which reads
        //its API key from the XC_API_KEY environment variable.
        "xenocanto" => array(
          "query" => array(
            "grp:birds",
            "grp:grasshoppers",
            "grp:bats",
            "grp:frogs",
            "grp:\"land mammals\"",
            "grp:soundscape"
          )
        ),
        "process" => array(
          "sourceR"
        )
      )
    )
  );
  return($info);
}
