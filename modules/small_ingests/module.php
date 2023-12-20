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
          "deployment" => ""
        )
      ),
      array(
        "type" => "traits",
        "url" => "https://raw.githubusercontent.com/audioblast/small_ingests/main/Mikula_etal_2020.csv",
        "process" => array(
          "sourceR"
        ),
        "mapping" => array(
          "traitID" => "Xeno_cantoID",
          "Taxonomic.name" => "scinam_birdtree",
          "Value" => "Peak_frequency"
        ),
        "override" => array(
          "source" => "Mikula_etal_2020",
          "taxonID" => "",
          "Trait" => "Peak Frequency (kHz)",
          "Ontology.Link" => "https://vocab.audioblast.org/PeakFrequency",
          "Call.Type" => "call",
          "Sex" => "",
          "Temperature" => "",
          "Reference" => "Mikula, et al. 2020",
          "Cascade" => "0",
          "Annotation.ID" => "",
          "min" => "",
          "max" => ""
        ),
        "process" => array(
          "hz2khz"
        )
      ) 
    )
  );
  return($info);
}
