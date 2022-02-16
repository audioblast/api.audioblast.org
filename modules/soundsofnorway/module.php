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
        "url" => "https://raw.githubusercontent.com/audioblast/small_ingests/main/son_test.csv",
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
          //"Duration" => "Duration"
        ),
        "process" => array(
          "sourceR",
          "date2dateAndTime"
        )
      )
    )
  );
  return($info);
}
