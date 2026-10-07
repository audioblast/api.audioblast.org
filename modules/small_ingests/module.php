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
          "Call.Type" => "Song",
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
      ),
      array(
        //Links from the Natural History Museum specimens that BioAcoustica
        //holds to the objects that hold the same specimens in the NHM Data
        //Portal, which the repository's build/nhm_links.R matches on the
        //barcodes of the specimens themselves. The subjects are BioAcoustica's
        //records, so the links name its source themselves rather than leaving
        //it to be filled in with their own.
        "type" => "links",
        "url" => "https://raw.githubusercontent.com/audioblast/small_ingests/main/nhm_links.csv",
        "override" => array(
          "source" => "nhm"
        )
      )
    )
  );
  //Jeantet & Dufourq (2023), a corpus of bird songs marked in xeno-canto
  //recordings (doi:10.5281/zenodo.7828148), as references to the recordings:
  //the corpus record, a region of interest of a xeno-canto recording for each
  //song, their details and their links to the corpus, as published (v1) and
  //with names as at 5 October 2026 (v2). Every row is the corpus's own, under
  //the source jeantet-dufourq-2023; the override says so for the references,
  //whose reader leaves the file's source out.
  foreach (array("v1", "v2") as $version) {
    foreach (array("references" => "references.csv", "details" => "details.csv",
      "ann-o-mate" => "ann-o-mate.csv", "links" => "links.csv") as $type => $file) {
      $info["sources"][] = array(
        "type" => $type,
        "url" => "https://raw.githubusercontent.com/audioblast/small_ingests/main/jeantet-dufourq-2023/".$version."/".$file,
        "override" => array(
          "source" => "jeantet-dufourq-2023"
        )
      );
    }
  }
  return($info);
}
