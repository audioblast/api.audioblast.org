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
         //lat and lon are the defaults of daysPhases(), which this module wraps
         "lat" => array(
            "desc" => "Decimal latitude",
            "type" => "float",
            "default" => 50.1,
            "op" => "="
          ),
         "lon" => array(
            "desc" => "Decimal longitude",
            "type" => "float",
            "default" => 1.83,
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
Validates the request parameters for days_phases and builds the command that
runs daysPhases.R. Returns array("command" => string) once every parameter is
good, or array("note" => string) naming the first that is not, so that nothing
is run when the request cannot be answered.

Request parameters reach here with only mysqli_real_escape_string applied,
which is no defence against shell metacharacters, and may be arrays (e.g.
tz[]=x). So every parameter is validated, and each argument is still escaped
with escapeshellarg(). Kept apart from the callback so that it can be
exercised without a shell.
*/
function _suncalc_daysphases_command($f) {
  $date_valid = FALSE;
  if (isset($f["date"]) && is_string($f["date"])) {
    //core/api.php supplies the documented default, "today (YYYY-MM-DD)", and a
    //caller may write "today" alone. Anything else that merely begins with
    //"today" is not a date this endpoint knows: it is left as it stands and
    //refused below, rather than quietly answered for today.
    if ($f["date"] === "today") {
      $f["date"] = date('Y-m-d');
    } else if (substr($f["date"], 0, 7) === "today (" && substr($f["date"], -1) === ")") {
      $f["date"] = substr($f["date"], 7, -1);
    }
    if (preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $f["date"], $date_parts)) {
      //R's as.Date() would read 15/06/2024 as the year 15, so be strict here
      $date_valid = checkdate((int)$date_parts[2], (int)$date_parts[3], (int)$date_parts[1]);
    }
  }
  if (!$date_valid) {
    return(array("note" => "Date must be a valid date in the form YYYY-MM-DD, or today"));
  }

  if (!isset($f["period"]) || !is_string($f["period"]) || !in_array($f["period"], array("month", "year"), TRUE)) {
    return(array("note" => "Period must be month or year"));
  }

  $lat = (isset($f["lat"]) && is_scalar($f["lat"]) && is_numeric($f["lat"])) ? (float)$f["lat"] : NULL;
  if (is_null($lat) || $lat < -90 || $lat > 90) {
    return(array("note" => "Latitude must be a number between -90 and 90"));
  }

  $lon = (isset($f["lon"]) && is_scalar($f["lon"]) && is_numeric($f["lon"])) ? (float)$f["lon"] : NULL;
  if (is_null($lon) || $lon < -180 || $lon > 180) {
    return(array("note" => "Longitude must be a number between -180 and 180"));
  }

  //Olson names, as R expects; UTC (the default) is also allowed explicitly
  if (!isset($f["tz"]) || !is_string($f["tz"]) || ($f["tz"] !== "UTC" && !in_array($f["tz"], DateTimeZone::listIdentifiers(), TRUE))) {
    return(array("note" => "Timezone must be a valid timezone name such as UTC or Europe/London"));
  }

  //Positional arguments of daysPhases.R, in order
  $args = array($f["date"], $f["period"], $lat, $lon, $f["tz"]);
  return(array("command" => "Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R ".implode(" ", array_map("escapeshellarg", $args))));
}

/*
Runs daysPhases.R through the shell.
*/
function suncalc_daysphases($f) {
  $ret = array();

  $command = _suncalc_daysphases_command($f);
  if (isset($command["note"])) {
    $ret["notes"][] = $command["note"];
    return($ret);
  }

  $output=null;
  $retval=null;
  exec($command["command"], $output, $retval);
  if ($retval == 0) {
    $ret["data"] = $output;
  } else {
    $ret["notes"][] = "suncalc calculation failed";
  }
  return($ret);
}
