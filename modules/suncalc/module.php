<?php

function suncalc_info() {
  $info = array(
    "mname" => "suncalc",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Sun calculations",
    "desc" => "Provides information about dawn, dusk, day, night, etc.",
    "endpoints" => array(
      "days_phases" => array(
        "callback" => "suncalc_daysphases",
        "desc" => "Returns day phases for a date range",
        "returns" => "data",
        "params" => array(
          "date" => array(
            "desc" => "String of centre date",
            "type" => "string",
          ),
          "period" => array(
            "desc" => "How long a period to retrieve",
            "type" => "string",
            "allowed" => array(
              "month",
              "year"
            )
          ),
          "output" => array(
            "desc" => "At present just an array",
            "type" => "string",
            "allowed" => array(
              "JSON"
            ),
            "default" => "JSON"
          )
        )
      )
    )
  );
  return($info);
}

function suncalc_daysphases($f) {
  $output=null;
  $retval=null;
  exec("Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R ".$f["date"]." ".$f["period"], $output, $retval);
  if ($retval == 0) {
    return($output);

  }
}
