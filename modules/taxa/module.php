<?php

function taxa_info() {
    $info = array(
        "mname" => "taxa",
        "version" => 1.0,
        "category" => "data",
        "table" => "taxa",
        "hname" => "Taxa",
        "desc" => "This endpoint allows for the querying of the taxonomic hierarchy held within audioBLAST!",
        "params" => array(
            "taxon" => array(
              "desc" => "Taxonomic name",
              "type" => "string",
              "default" => "",
              "column" => "taxon",
              "op" => "=",
              "autocomplete" => TRUE
            ),
            "rank" => array(
              "desc" => "Taxonomic rank",
              "type" => "string",
              "default" => "",
              "column" => "Rank",
              "op" => "=",
              "autocomplete" => TRUE
            ),
            "species" => array(
              "desc" => "Species name",
              "type" => "string",
              "default" => "",
              "column" => "Species",
              "op" => "="
            ),
            "genus" => array(
              "desc" => "Genus name",
              "type" => "string",
              "default" => "",
              "column" => "Genus",
              "op" => "="
            ), 
            "tribe" => array(
              "desc" => "Tribe name",
              "type" => "string",
              "default" => "",
              "column" => "Tribe",
              "op" => "="  
            ), 
            "subfamily" => array(
              "desc" => "Subfamily name",
              "type" => "string",
              "default" => "",
              "column" => "Subfamily",
              "op" => "="  
            ), 
            "family" => array(
              "desc" => "Family name",
              "type" => "string",
              "default" => "",
              "column" => "Family",
              "op" => "="  
            ), 
            "suborder" => array(
              "desc" => "Suborder name",
              "type" => "string",
              "default" => "",
              "column" => "Suborder",
              "op" => "="  
            ), 
            "order" => array(
              "desc" => "Order name",
              "type" => "string",
              "default" => "",
              "column" => "Order",
              "op" => "="  
            ), 
            "class" => array(
              "desc" => "Class name",
              "type" => "string",
              "default" => "",
              "column" => "Class",
              "op" => "="  
            ), 
            "kingdom" => array(
              "desc" => "Kingdom name",
              "type" => "string",
              "default" => "",
              "column" => "Kingdom",
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
