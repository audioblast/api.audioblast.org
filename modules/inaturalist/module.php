<?php

function inaturalist_info() {
  $info = array(
    "mname" => "iNaturalist",
    "version" => 1.0,
    "category" => "source",
    "hname" => "iNaturalist",
    "url" => "https://www.inaturalist.org",
    "sources" => array(
      array(
        "type" => "recordings",
        //Harvested from the iNaturalist API by audioBlastIngest, which needs no
        //API key to read. iNaturalist holds about a million recordings
        //altogether, and the birds alone are most of them.
        //
        //Every taxon is one source, which is the taxon_id given empty. The
        //harvest is streamed, and its upload deletes the source's links before
        //inserting, so two harvests under one name, such as one for each taxon
        //group, would wipe each other's links; audioBlastIngest harvests
        //nothing while a streamed source has more than one entry. A harvest of
        //every taxon took about 16 hours in September 2026.
        "inaturalist" => array(
          "taxon_id" => array("")
        ),
        "process" => array(
          "sourceR"
        )
      )
    )
  );
  return($info);
}
