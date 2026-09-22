<?php

function osf_info() {
  $info = array(
    "mname" => "osf",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Orthoptera Species File",
    "url" => "https://orthoptera.speciesfile.org",
    "sources" => array(
      array(
        "type" => "recordings",
        //Harvested from the Orthoptera Species File's TaxonWorks API by
        //audioBlastIngest, which needs no API key: the project token it uses
        //by default is the one https://sfg.taxonworks.org/api/v1/ publishes
        //for every open TaxonWorks project. The harvest gives the taxa the
        //recordings are of, and the links between them, as well as the
        //recordings themselves.
        "orthoptera" => array(
          "per_page" => 100,
          "pause" => 1
        ),
        "process" => array(
          "sourceR"
        )
      )
    )
  );
  return($info);
}
