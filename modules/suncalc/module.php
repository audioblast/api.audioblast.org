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

/*
Runs daysPhases.R through the shell. Request parameters reach here with only
mysqli_real_escape_string applied, which is no defence against shell
metacharacters, and may be arrays (e.g. tz[]=x). So every parameter is
validated before use, and each argument is still escaped with escapeshellarg().
*/
function suncalc_daysphases($f) {
  $ret = array();

  $date_valid = FALSE;
  if (isset($f["date"]) && is_string($f["date"])) {
    if (substr($f["date"], 0, 5) == "today") {$f["date"] = date('Y-m-d'); }
    if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $f["date"], $date_parts)) {
      $date_valid = checkdate((int)$date_parts[2], (int)$date_parts[3], (int)$date_parts[1]);
    }
  }
  if (!$date_valid) {
    $ret["notes"][] = "Date must be a valid date in the form YYYY-MM-DD, or today";
    return($ret);
  }

  if (!isset($f["period"]) || !is_string($f["period"]) || !in_array($f["period"], array("month", "year"), TRUE)) {
    $ret["notes"][] = "Period must be month or year";
    return($ret);
  }

  $lat = (isset($f["lat"]) && is_scalar($f["lat"]) && is_numeric($f["lat"])) ? (float)$f["lat"] : NULL;
  if (is_null($lat) || $lat < -90 || $lat > 90) {
    $ret["notes"][] = "Latitude must be a number between -90 and 90";
    return($ret);
  }

  $lon = (isset($f["lon"]) && is_scalar($f["lon"]) && is_numeric($f["lon"])) ? (float)$f["lon"] : NULL;
  if (is_null($lon) || $lon < -180 || $lon > 180) {
    $ret["notes"][] = "Longitude must be a number between -180 and 180";
    return($ret);
  }

  //Olson names, as R expects; UTC (the default) is also allowed explicitly
  if (!isset($f["tz"]) || !is_string($f["tz"]) || ($f["tz"] !== "UTC" && !in_array($f["tz"], DateTimeZone::listIdentifiers(), TRUE))) {
    $ret["notes"][] = "Timezone must be a valid timezone name such as UTC or Europe/London";
    return($ret);
  }

  //Positional arguments of daysPhases.R, in order
  $args = array($f["date"], $f["period"], $lat, $lon, $f["tz"]);
  $output=null;
  $retval=null;
  exec("Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R ".implode(" ", array_map("escapeshellarg", $args)), $output, $retval);
  if ($retval == 0) {
    $ret["data"] = $output;
  } else {
    $ret["notes"][] = "suncalc calculation failed";
  }
  return($ret);
}
