<?php

function bioacoustica_info() {
  $info = array(
    "mname" => "bio.acousti.ca",
    "version" => 1.0,
    "category" => "source",
    "hname" => "BioAcoustica",
    "url" => "http://bio.acousti.ca",
    "logo_url" => "https://raw.githubusercontent.com/BioAcoustica/BioAcoustica-Resources/master/logos/Website/logo.svg",
    "sources" => array(
      array(
        "type" => "recordings",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/recordings.csv",
        //The export names its columns as the recordings table does, so they are
        //taken by name and only the deployment, which BioAcoustica has none of,
        //is set here
        "override" => array(
          "deployment" => ""
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
        "type" => "references",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/references.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "descriptions",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/descriptions.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "locations",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/locations.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "specimens",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/specimens.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "links",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/links.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "details",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/details.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "vernacularnames",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/vernacularnames.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "onomatopoeia",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/onomatopoeia.csv",
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "images",
        "url" => "https://raw.githubusercontent.com/BioAcoustica/audioblast_ingest/main/images.csv",
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
