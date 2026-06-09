<?php

function evenness_info() {
  return analysis_index_module(array(
    "mname" => "evenness",
    "hname" => "Acoustic Evenness",
    "table" => "analysis-evenness",
    "code_name" => "soundecology",
    "code_function" => "acoustic_evenness()"
  ));
}
