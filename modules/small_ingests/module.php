<?php

function small_ingests_info() {
  $info = array(
    "mname" => "small_ingests",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Small ingests",
    "url" => "https://github.com/audioblast/small_ingests",
    "web_files" => "https://cdn.audioblast.org/files/unp/",
    "sources" => array(
      array(
        "type" => "recordings",
        "url" => "https://raw.githubusercontent.com/audioblast/small_ingests/main/ColinBirds.csv",
        "process" => array(),
        "override" => array(
          "deployment" => NULL
        )
      )
    )
  );
  return($info);
}
