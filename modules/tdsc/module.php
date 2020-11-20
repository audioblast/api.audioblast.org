<?php

function tdsc_info() {
    $info = array(
        "mname" => "tdsc",
        "version" => 1.0,
        "category" => "analysis",
        "hname" => "Time Domain Signal Coding",
        "desc" => "Time domain signal coding is...",
        "doc" => "tdsc.html",
        "params" => array(
            "from" => "start time to return",
            "to" => "end time to return",
            "output" => "At present just an array"
        ),

    );
    return($info);
}