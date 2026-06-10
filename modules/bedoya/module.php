<?php

function bedoya_info() {
  return analysis_index_module(array(
    "mname" => "bedoya",
    "hname" => "Rainfall analysis using the bedoya method",
    "table" => "analysis-bedoya",
    "code_name" => "sonicscrewdriver",
    "code_function" => 'rainfallDetection(method="bedoya2017")',
    "references" => array(
      array(
        "title" => "Automatic identification of rainfall in acoustic recordings",
        "year" => 2017,
        "authors" => "Bedoya et al",
        "doi" => "10.1016/j.ecolind.2016.12.018"
      )
    )
  ));
}
