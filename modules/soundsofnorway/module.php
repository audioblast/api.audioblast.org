<?php

function soundsofnorway_info() {
  $info = array(
    "mname" => "sounds_of_norway",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Sounds of Norway",
    "url" => "http://", //TODO
    "sources" => array(
      array(
        "type" => "recordings",
        "git" => array(
          "owner" => "audioblast",
          "repo" => "small_ingests",
          "file" => "son_test.csv"
        ),
        "mapping" => array(
          "id" => "audio_id",
          //"Title" => "Title",
          //"taxon" => "taxon",
          "file" => "audio_link",
          //"author" => "author",
          "post_date" => "upload_time",
          //"size" => "size",
          //"size_raw" => "size_raw",
          //"type" => "type",
          //"NonSpecimen" => "NonSpecimen",
          "Date" => "created_time",
          //"Time" => "Time",
          //"Duration" => "Duration",
          "deployment" =>"site"
        ),
        "override" => array(
          "NonSpecimen" => "Soundscape",
          "Duration" => 300,
          "type" => "audio/mpeg"
        ),
        "process" => array(
          "sourceR",
          "date2dateAndTime"
        )
      ),
      array(
        "type" => "ann-o-mate",
        "git" => array(
          "owner" => "audioblast",
          "repo" => "small_ingests",
          "file" => "son_detections.csv"
        ),
        "mapping" => array(
          "source_id" => "audio_id",
          "annotation_id" => "id",
          "annotation_date" => "upload_time",
          "recording_url" => "audio_link",
          "time_start" => "start_secs",
          "time_end" => "end_secs",
          "taxon" => "tags",
          "lat" => "latitude",
          "lon" => "longitude"
        ),
        "override" => array(
          "annotator" => "BirdNet-Lite",
          "type" => "Call"
        ),
        "confidence" => array(
          "column" => "confidence",
          "minimum" => 0.8
        ),
        "process" => array(
          "sourceR",
          "birdnetVernacular"
        )
      )
    )
  );
  return($info);
}
