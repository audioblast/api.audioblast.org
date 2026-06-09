<?php

function h_info() {
  return analysis_index_module(array(
    "mname" => "h",
    "hname" => "Acoustic Entropy",
    "table" => "analysis-H",
    "code_name" => "seewave",
    "code_function" => "H()"
  ));
}
