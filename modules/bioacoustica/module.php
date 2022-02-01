<?php

function bioacoustica_info() {
  $info = array(
    "mname" => "bio.acousti.ca",
    "version" => 1.0,
    "category" => "source",
    "hname" => "BioAcoustica",
    "url" => "http://bio.acousti.ca",
    "sources" => array(
      array(
        "type" => "recordings",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/recordings.csv",
        "mapping" => array(
          "id" => "id",
          "Title" => "Title",
          "taxon" => "taxon",
          "file" => "file",
          "author" => "author",
          "post_date" => "post_date",
          "size" => "size",
          "size_raw" => "size_raw",
          "type" => "type",
          "NonSpecimen" => "NonSpecimen",
          "Date" => "Date",
          "Time" => "Time",
          "Duration" => "Duration"
        ),
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "taxa",
        "url" => "https://github.com/BioAcoustica/audioblast_ingest/raw/main/taxa.txt",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "traits",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/traits.txt",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "ann-o-mate",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/annotations.csv"
      )
    ),
    "references" => array(
      "title" => "BioAcoustica: a free and open repository and analysis platform for bioacoustics",
      "authors" => "Baker et al",
      "year" => 2015,
      "doi" => "10.1093/database/bav054"
    )
  );
  return($info);
}
