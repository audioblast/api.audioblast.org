<?php

function suncalc_info() {
  $info = array(
    "mname" => "suncalc",
    "version" => 1.0,
    "category" => "standalone",
    "hname" => "Sun calculations",
    "desc" => "Provides information about dawn, dusk, day, night, etc.",
    "code" => array(
      "type" => "package",
      "language" => "R",
      "name" => "sonicscrewdriver",
      "source" => "CRAN",
      "function" => "daysPhases()"
    ),
    "endpoints" => array(
      "days_phases" => array(
        "callback" => "suncalc_daysphases",
        "desc" => "Returns day phases for a date range",
        "returns" => "data",
        "params" => array(
          "date" => array(
            "desc" => "String of centre date",
            "type" => "string",
            "op" => "=",
            "default" => "today (".date('Y-m-d').")",
          ),
          "period" => array(
            "desc" => "How long a period to retrieve",
            "type" => "string",
            "allowed" => array(
              "month",
              "year"
            ),
            "default" => "year",
            "op" => "="
          ),
         "lat" => array(
            "desc" => "Decimal latitude",
            "type" => "float",
            "default" => 50.1,
            "op" => "="
          ),
         "lon" => array(
            "desc" => "Decimal longitude",
            "type" => "float",
            "default" => 1.38,
            "op" => "="
          ),
         "tz" => array(
            "desc" => "Timezone",
            "type" => "string",
            "default" => "UTC",
            "op" => "="
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
  if (substr($f["date"], 0, 5) == "today") {$f["date"] = date('Y-m-d'); }
  $output=null;
  $retval=null;
  exec("Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R ".$f["date"]." ".$f["period"]." ".$f["lat"]." ".$f["lon"]." ".$f["tz"], $output, $retval);
  if ($retval == 0) {
    return($output);

  }
}
