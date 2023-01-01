<?php

function phymoji_info() {
  $info = array(
    "mname" => "phymoji",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Phylogenetic Emoji",
    "desc" => "Finds an emoji for a scientific name.",
    "endpoints" => array(
      "get_phymoji" => array(
        "callback" => "phymoji_phymoji",
        "desc" => "Finds an emoji for a scientific name.",
        "returns" => "data",
        "params" => array(
          "names" => array(
            "desc" => "Array of names from lowest to highest rank",
            "type" => "string",
            "op" => "=",
            "default" => "",
          ),
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      ),
      "get_taxon" => array(
        "callback" => "phymoji_taxon",
        "desc" => "Finds a scientific name for an emoji.",
        "returns" => "data",
        "params" => array(
          "emoji" => array(
            "desc" => "An emoji of a plant or animal",
            "type" => "string",
            "op" => "=",
            "default" => "",
          ),
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      )
    )
  );
  return($info);
}

function phymoji_phymoji($f) {
  include_once("phymoji.php");
  $phymoji = new PhyMoji;
  $ret = array();
  $ret["data"] = $phymoji->phymoji(strtolower($f['names']));
  return($ret);
}

function phymoji_taxon($f) {
  include_once("phymoji.php");
  $phymoji = new PhyMoji;
  $ret = array();
  $ret["data"] = $phymoji->getTaxon($f['emoji']);
  return($ret);
}
