<?php

function modules_info() {
    $info = array(
        "mname" => "modules",
        "version" => 1.0,
        "category" => "standalone",
        "hname" => "Loaded modules",
        "desc" => "Provide information about loaded modules",
        "endpoints" => array(
          array(
            "path" => "list",
            "callback" => "modules_list"
          )
        )
    );
    return($info);
}

function modules_list() {
  print("OK");exit;
}
