<?php

function tierstimmenarchiv_info() {
  $info = array(
    "mname" => "Tierstimmenarchiv",
    "version" => 1.0,
    "category" => "source",
    "hname" => "Animal Sound Archive",
    "url" => "https://suche.tierstimmenarchiv.de",
    "sources" => array(
      array(
        "type" => "recordings",
        //Harvested from the Animal Sound Archive's search by audioBlastIngest,
        //which needs no key. The archive holds some 47,000 recordings with
        //audio, of which about three quarters are birds.
        //
        //The whole archive is one source. Its search has no parameter that
        //divides it into groups the way xeno-canto's and iNaturalist's do: the
        //classes it browses by are not search parameters, and an unrecognised
        //parameter is ignored rather than refused, so a query meant to take a
        //part of the archive silently takes all of it. The uploads delete a
        //source's rows before inserting, so two harvests under one name would
        //wipe each other's details and links.
        //
        //The default query matches every record: the search needs a parameter,
        //a date range would leave out the recordings nobody dated, and every
        //identifier has a colon in it (e.g. TSA:Anas_acuta_DIG_195_1_0).
        "tierstimmenarchiv" => array(
          "query" => array("unique_identifier=:")
        ),
        "process" => array(
          "sourceR"
        )
      )
    )
  );
  return($info);
}
