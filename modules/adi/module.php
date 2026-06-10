<?php

function adi_info() {
  return analysis_index_module(array(
    "mname" => "adi",
    "hname" => "Acoustic Diversity Index",
    "table" => "analysis-adi",
    "code_name" => "soundecology",
    "code_function" => "acoustic_diversity()"
  ));
}
