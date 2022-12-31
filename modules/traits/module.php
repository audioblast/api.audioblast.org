<?php

function traits_info() {
  $info = array(
    "mname" => "traits",
    "version" => 1.0,
    "category" => "data",
    "table" => "traits",
    "hname" => "Traits",
    "desc" => "This endpoint allows for the querying of the organism traits held within audioBLAST!",
    "endpoints" => array(
      "list_text_values" => array(
        "callback" => "traits_list_text_values",
        "desc" => "Returns a list of distinct text values.",
        "returns" => "data",
        "params" => array(
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
    ),
    "params" => array(
      "taxon" => array(
        "desc" => "Taxonomic name",
        "type" => "string",
        "default" => "",
        "column" => "Taxonomic.name",
        "op" => "=",
        "autocomplete" => TRUE
      ),
      "trait" => array(
        "desc" => "Trait name",
        "type" => "string",
        "default" => "",
        "column" => "Trait",
        "op" => "contains",
        "autocomplete" => TRUE
      ),
      "trait_ontology" => array(
        "desc" => "Link to trait in ontology",
        "type" => "string",
        "default" => "",
        "column" => "Ontology.Link",
        "op" => "="
      ),
      "value" => array(
        "desc" => "Value of trait",
        "type" => "string",
        "default" => "",
        "column" => "Value",
        "op" => "="
      ),
      "call_type" => array(
        "desc" => "Type of call the trait applies to",
        "type" => "string",
        "default" => "",
        "column" => "Call.Type",
        "op" => "="
      ),
      "sex" => array(
        "desc" => "Sex",
        "type" => "string",
        "default" => "",
        "column" => "Sex",
        "op" => "="
      ),
      "temperature" => array(
        "desc" => "Temperature at which trait was measured",
        "type" => "string",
        "default" => "",
        "column" => "Temperature",
        "op" => "range"
      ),
      "value_min" => array(
        "desc" => "Value min",
        "type" => "string",
        "default" => "",
        "column" => "min",
        "op" => "="
      ),
      "value_max" => array(
        "desc" => "Value max",
        "type" => "string",
        "default" => "",
        "column" => "max",
        "op" => "="
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
  );
  return($info);
}

function traits_list_text_values() {
  $ret = array();
  $ret["data"] = array();
  global $db;
  $sql = "SELECT DISTINCT `Value` as `value` FROM `traits`;";
  $res = $db->query($sql);
  while ($row = $res->fetch_assoc()) {
    if (preg_match('/^[\p{L} \(\)]+$/u', $row["value"])) {
      $ret["data"][] = $row["value"];
    }
  }
  return($ret);
}