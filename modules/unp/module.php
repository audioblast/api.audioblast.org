<?php

function unp_info() {
  $info = array(
    "mname" => "unp",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Urban Nature Project",
    "url" => "https://www.nhm.ac.uk/about-us/urban-nature-project.html",
    "logo_url" => "https://www.thelostwords.org/uploads/NHM%20Urban%20Nature%20Project%20logo.png",
    "web_files" => "https://cdn.audioblast.org/files/unp/",
    "sources" => array(
      array(
        "type" => "recordings",
        "git" => array(
          "owner" => "NaturalHistoryMuseum",
          "repo" => "audioblast_ingest",
          "file" => "recordings.csv"
        ),
       "process" => array()
      ),
      array(
        "type" => "deployments",
        "url" => "https://raw.githubusercontent.com/NaturalHistoryMuseum/audioblast_ingest/master/deployments.csv",
        "process" => array()
      ),
      array(
        "type" => "devices",
        "url" => "https://raw.githubusercontent.com/NaturalHistoryMuseum/audioblast_ingest/master/devices.csv",
        "process" => array()
      ),
      array(
        "type" => "abiotic",
        "url" => "https://github.com/NaturalHistoryMuseum/audioblast_ingest/raw/master/abiotic.csv",
        "process" => array()
      ),
      array(
        "type" => "deployment_locations",
        "url" => "https://github.com/NaturalHistoryMuseum/audioblast_ingest/raw/master/deployment_location.csv",
        "process" => array()
      ),
      array(
        "type" => "sensors",
        "url" => "https://github.com/NaturalHistoryMuseum/audioblast_ingest/raw/master/sensor.csv",
        "process" => array()
      )
    )
  );
  return($info);
}
