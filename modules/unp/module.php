<?php

function unp_info() {
  $info = array(
    "mname" => "unp",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Urban Nature Project",
    "url" => "https://www.nhm.ac.uk/about-us/urban-nature-project.html",
    "web_files" => "https://cdn.audioblast.org/files/unp/",
    "sources" => array(
      array(
        "type" => "recordings",
        "url" => "https://raw.githubusercontent.com/NaturalHistoryMuseum/audioblast_ingest/master/recordings.csv",
        "process" => array()
      ),
      array(
        "type" => "deployments",
        "url" => "https://raw.githubusercontent.com/NaturalHistoryMuseum/audioblast_ingest/master/deployments.csv",
        "process" => array()
      )
    )
  );
  return($info);
}
