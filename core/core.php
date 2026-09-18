<?php
//Answer pings before connecting to the database, so they show whether the web
//server is up whatever the state of the database (which /dbping checks)
if ($_SERVER['REQUEST_URI'] == "/ping") {
  header("Cache-Control: no-store");
  echo "pong";
  exit;
}

include("homepage.php");
include("modules.php");
include("database.php");
$db->set_charset('utf8mb4');
include("speedbird.php");
include("input.php");
include("api.php");
include("rdf.php");
include("record.php");
include("embed.php");
include("cdn.php");
