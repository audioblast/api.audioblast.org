<?php

//Load database settings.
if (file_exists("settings/db.php")) {
  //The database check reports a failed connection itself, where PHP 8.1 and
  //later would otherwise stop with an error. PHP's own warnings are kept out of
  //its reply, as they can include the database user and host.
  if ($_SERVER['REQUEST_URI'] == "/dbping") {
    mysqli_report(MYSQLI_REPORT_OFF);
    ini_set("display_errors", "0");
  }
  include("settings/db.php");
} else {
  print("settings/db.php does not exist!");
  exit;
}

//Check the database is up, for status monitoring: it must take the connection
//and answer a query. The reason for a failure isn't shown, as it can include
//the database user and host.
if ($_SERVER['REQUEST_URI'] == "/dbping") {
  header("Cache-Control: no-store");
  if ($db->connect_error || !$db->query("SELECT 1;")) {
    http_response_code(503);
    print("Database connection failed: the database is not answering");
  } else {
    print("pong");
  }
  exit;
}
