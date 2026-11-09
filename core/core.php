<?php
require __DIR__."/llms.php";

//Answer pings and serve the llms.txt pointer before connecting to the database,
//so they work whatever the state of the database (which /dbping checks)
if ($_SERVER['REQUEST_URI'] == "/ping") {
  header("Cache-Control: no-store");
  echo "pong";
  exit;
}

//Serve a copy of acousti.cloud's llms.txt at the site root, where AI crawlers
//that look for the file expect it (see core/llms.php)
if ($_SERVER['REQUEST_URI'] == "/llms.txt") {
  serveLlmsTxt();
}

include("homepage.php");
include("modules.php");
include("query.php");
include("database.php");
$db->set_charset('utf8mb4');
include("speedbird.php");
include("input.php");
include("api.php");
include("rdf.php");
include("record.php");
include("embed.php");
include("cdn.php");
include("mcp.php");
include("mcp-tools.php");

//Point AI crawlers at the llms.txt with a Link header. Done here, after the
//includes (which only declare functions) and before any handler in index.php
//emits output, so the header is set on every response and never sent too late.
llmsLinkHeader();
