<?php
// Acoustic Complexity Index module for audioBlast API
//
// This module provides access to the Acoustic Complexity Index (ACI) analyses
// in audioBlast. The ACI is a measure of the acoustic complexity of a soundscape,
// and is in this instance calculated using the seewave package in R, with an analysis
// window of 60 seconds.

function aci_info() {
  return analysis_index_module(array(
    "mname" => "aci",
    "hname" => "ACI",
    "table" => "analysis-aci",
    "code_name" => "seewave",
    "code_function" => "ACI()",
    "vocab_url" => "https://vocab.audioblast.org/cv/analysis#seewaveACI"
  ));
}
