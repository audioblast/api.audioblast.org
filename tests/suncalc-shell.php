<?php
// No settings or real database are loaded. Run from the repository root.
require 'modules/suncalc/module.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

/*
The parameters of a days_phases request as suncalc_daysphases() meets them.
core/api.php has applied the endpoint's defaults and mysqli_real_escape_string,
which escapes quotes and backslashes and leaves every other shell
metacharacter alone, and a bracketed parameter (tz[]=x) arrives as an array.
None of it may reach the shell as anything but one literal argument.
*/
$today = date('Y-m-d');
$defaults = array("date" => "today (".$today.")", "period" => "year",
                  "lat" => 50.1, "lon" => 1.38, "tz" => "UTC");

//The command a request builds, or "" when it is refused before anything runs
function built($over=array()) {
  global $defaults;
  $r = _suncalc_daysphases_command(array_merge($defaults, $over));
  return(isset($r["command"]) ? $r["command"] : "");
}

//The note a request is refused with, or "" when a command was built
function note($over=array()) {
  global $defaults;
  $r = _suncalc_daysphases_command(array_merge($defaults, $over));
  return(isset($r["note"]) ? $r["note"] : "");
}

$q = chr(39); //A single quote, as escapeshellarg() writes them
function command($date, $period, $lat, $lon, $tz) {
  global $q;
  return("Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R "
    .$q.$date.$q." ".$q.$period.$q." ".$q.$lat.$q." ".$q.$lon.$q." ".$q.$tz.$q);
}

// A request that can be answered, with every argument quoted
check(built() === command($today, "year", "50.1", "1.38", "UTC"),
  "The documented defaults are accepted: ".built());
check(built(array("date" => "today")) === command($today, "year", "50.1", "1.38", "UTC"),
  "and a date written as today alone");
check(built(array("date" => "2024-06-15", "period" => "month", "lat" => "-33.87",
                  "lon" => "151.21", "tz" => "Australia/Sydney"))
      === command("2024-06-15", "month", "-33.87", "151.21", "Australia/Sydney"),
  "and an explicit date, a month, and a named timezone");
check(built(array("date" => "2024-02-29")) === command("2024-02-29", "year", "50.1", "1.38", "UTC"),
  "and a leap day");
check(built(array("lat" => "-90", "lon" => "180")) === command($today, "year", "-90", "180", "UTC"),
  "and the ends of both ranges");
// lat and lon are passed on as the numbers they were read as, not as written
check(built(array("lat" => " 12.5")) === command($today, "year", "12.5", "1.38", "UTC"),
  "A latitude is passed on as a number, not as the string it was written as");
check(built(array("lon" => "1e2")) === command($today, "year", "50.1", "100", "UTC"),
  "and so is one written in exponent notation");

/*
The fault this guards against. Every one of these used to be concatenated into
the command, so that a request could run a second command on the server. None
of them may build a command at all, and each must be refused by naming the
parameter at fault.
*/
$params = array("date" => "Date", "period" => "Period", "lat" => "Latitude",
                "lon" => "Longitude", "tz" => "Timezone");
$payloads = array(";id", "|id", "&id", "&& id", '$(id)', chr(96)."id".chr(96),
                  "> /tmp/pwned", chr(10)."id", $q." ; id ; ".$q, chr(92)."id");
foreach ($params as $p => $word) {
  foreach ($payloads as $payload) {
    $shown = str_replace(array(chr(10), chr(13)), array("<LF>", "<CR>"), $payload);
    //Appended to the value the endpoint would have used, and sent on its own
    foreach (array($defaults[$p].$payload, $payload) as $value) {
      check(built(array($p => $value)) === "",
        $p."=".$shown." must not build a command: ".built(array($p => $value)));
      check(strpos(note(array($p => $value)), $word) === 0,
        $p."=".$shown." must be refused by name, not with: ".note(array($p => $value)));
    }
  }
}
check(built(array("date" => "2024-01-01;id")) === "", "A date carrying a command is refused");
// "today" used to be matched on its prefix, which quietly answered for today
check(built(array("date" => "today;id")) === "", "and so is one that only begins with today");
check(built(array("date" => "todays")) === "", "and today is not a prefix of some other word");

// A bracketed parameter arrives as an array, before any string function sees it
foreach ($params as $p => $word) {
  check(built(array($p => array(";id"))) === "", $p."[]=;id must not build a command");
  check(strpos(note(array($p => array(";id"))), $word) === 0, $p."[]=;id is refused by name");
  check(built(array($p => array())) === "", $p."[] with nothing in it must not build a command");
}

// Values that are wrong without being an attack are refused the same way
$wrong = array(
  //R's as.Date() would read 15/06/2024 as the year 15, so the form is strict
  array("date" => "15/06/2024"), array("date" => "2024-5-1"), array("date" => "2024-02-30"),
  array("date" => "2023-02-29"), array("date" => "2024-13-01"), array("date" => "2024-01-01".chr(10)),
  array("date" => ""), array("period" => "week"), array("period" => "Year"), array("period" => ""),
  array("lat" => "91"), array("lat" => "-90.1"), array("lat" => "north"), array("lat" => "1e400"),
  array("lat" => ""), array("lon" => "180.1"), array("lon" => "-181"), array("lon" => "0x1A"),
  array("tz" => "Mars/Olympus"), array("tz" => "")
);
foreach ($wrong as $over) {
  $name = key($over);
  check(built($over) === "", $name."=".str_replace(chr(10), "<LF>", (string)current($over))." is refused");
  check(strpos(note($over), $params[$name]) === 0, "and is refused by naming ".$name);
}
// A parameter that is missing altogether, rather than wrong
foreach (array_keys($params) as $p) {
  $missing = $defaults;
  unset($missing[$p]);
  $r = _suncalc_daysphases_command($missing);
  check(!isset($r["command"]), "A request with no ".$p." must not build a command");
}

/*
What the shell is actually handed. The command runs printf in place of Rscript,
so that the arguments daysPhases.R would be started with can be read back.
*/
$probe = str_replace("Rscript --quiet --vanilla ./modules/suncalc/daysPhases.R",
  "printf ".$q."[%s]".chr(10).$q,
  built(array("date" => "2024-06-15", "period" => "month", "tz" => "Australia/Sydney")));
$out = array();
$retval = null;
exec($probe, $out, $retval);
check($out === array("[2024-06-15]", "[month]", "[50.1]", "[1.38]", "[Australia/Sydney]"),
  "daysPhases.R is started with five literal arguments: ".implode(" ", $out));

print("suncalc-shell: all checks passed\n");
