<?php

function th_info() {
  return analysis_index_module(array(
    "mname" => "th",
    "hname" => "Temporal Entropy",
    "table" => "analysis-th",
    "code_name" => "seewave",
    "code_function" => "th()"
  ));
}
