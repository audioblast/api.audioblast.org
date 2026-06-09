<?php

function sh_info() {
  return analysis_index_module(array(
    "mname" => "sh",
    "hname" => "Spectral Entropy",
    "table" => "analysis-sh",
    "code_name" => "seewave",
    "code_function" => "sh()"
  ));
}
