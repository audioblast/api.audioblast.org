<?php

function inaturalist_info() {
  $info = array(
    "mname" => "iNaturalist",
    "version" => 1.0,
    "category" => "source",
    "hname" => "iNaturalist",
    "url" => "https://www.inaturalist.org",
    "sources" => array(
      //Harvested from the iNaturalist API by audioBlastIngest, which needs no
      //API key to read. Each taxon group is a source of its own, so a harvest
      //that fails skips that group rather than every group: iNaturalist holds
      //about a million recordings altogether, and the birds alone are most of
      //them.
      array(
        "type" => "recordings",
        //Orthoptera
        "inaturalist" => array(
          "taxon_id" => array("47651")
        ),
        "process" => array(
          "sourceR"
        )
      ),
      array(
        "type" => "recordings",
        //Cicadidae
        "inaturalist" => array(
          "taxon_id" => array("50186")
        ),
        "process" => array(
          "sourceR"
        )
      )
    )
  );
  return($info);
}
