<?php

function bi_info() {
  return analysis_index_module(array(
    "mname" => "bi",
    "hname" => "Bioacoustic index",
    "table" => "analysis-bi",
    "code_name" => "soundecology",
    "code_function" => "bioacoustic_index()",
    "histogram" => "hist-bi"
  ));
}
