<?php
// The MCP server (core/mcp.php) and its tools (core/mcp-tools.php), with a
// fixture in place of the database. No settings are loaded. Run from the
// repository root.
require 'core/modules.php';
require 'core/input.php';
require 'core/query.php';
require 'core/rdf.php';
require 'core/record.php';
require 'core/api.php';
require 'core/mcp.php';
require 'core/mcp-tools.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}
function endsWith($text, $end) {
  return(substr($text, -strlen($end)) === $end);
}
//What a tool that fails logs is the server's, not the test's, so it is kept out
//of the test's output; a check that fails is printed rather than logged with it
ini_set("error_log", sys_get_temp_dir()."/audioblast-mcp-test.log");
set_exception_handler(function($e) {
  //A check that fails is reported at the check, not inside check()
  $line = ($e->getTrace()[0]["function"] ?? "") === "check" ? $e->getTrace()[0]["line"] : $e->getLine();
  print("mcp: FAILED: ".$e->getMessage()." (line ".$line.")\n");
  exit(1);
});

//The value in nested arrays at a list of keys, or NULL if it isn't there
function valueAt($value, $keys) {
  foreach ($keys as $key) {
    if (!is_array($value) || !array_key_exists($key, $value)) {return(NULL);}
    $value = $value[$key];
  }
  return($value);
}

//The ways a value decoded from JSON doesn't match a JSON Schema, or none if it
//does. Only the keywords the tools' schemas use are checked. An empty array
//matches both an object and a list. (From Ontomasticon's tests/unit.php.)
function schemaProblems($value, $schema, $path = "") {
  if (isset($schema["type"])) {
    $isList = is_array($value) && array_values($value) === $value;
    $matches = FALSE;
    foreach ((array)$schema["type"] as $type) {
      $matches = $matches || ($type == "object" && is_array($value) && (!$isList || count($value) == 0)) || ($type == "array" && $isList)
        || ($type == "string" && is_string($value)) || ($type == "integer" && is_int($value)) || ($type == "boolean" && is_bool($value))
        || ($type == "number" && (is_int($value) || is_float($value))) || ($type == "null" && $value === NULL);
    }
    if (!$matches) {return(array($path." isn't ".implode(" or ", (array)$schema["type"])));}
  }
  $problems = array();
  if (isset($schema["enum"]) && !in_array($value, $schema["enum"], TRUE)) {
    $problems[] = $path." isn't one of its values";
  }
  foreach ((is_array($value) && isset($schema["required"])) ? $schema["required"] : array() as $key) {
    if (!array_key_exists($key, $value)) {$problems[] = $path."/".$key." is missing";}
  }
  foreach ((is_array($value) && isset($schema["properties"])) ? $schema["properties"] : array() as $key => $property) {
    if (array_key_exists($key, $value)) {
      $problems = array_merge($problems, schemaProblems($value[$key], $property, $path."/".$key));
    }
  }
  foreach ((is_array($value) && isset($schema["items"])) ? $value : array() as $index => $item) {
    $problems = array_merge($problems, schemaProblems($item, $schema["items"], $path."/".$index));
  }
  if (is_array($value) && isset($schema["additionalProperties"]) && is_array($schema["additionalProperties"])
      && isset($schema["additionalProperties"]["type"])) {
    foreach ($value as $key => $item) {
      if (isset($schema["properties"][$key])) {continue;}
      $problems = array_merge($problems, schemaProblems($item, $schema["additionalProperties"], $path."/".$key));
    }
  }
  return($problems);
}

//The MCP server's response to a request, as array(status, headers, the body decoded, the body). An array is sent as JSON.
function testMCPResponse($message, $headers = array(), $method = "POST") {
  $response = mcpResponse($method, $headers, is_string($message) ? $message : mcpJSON($message));
  return(array($response["status"], $response["headers"],
    ($response["body"] === NULL) ? NULL : json_decode($response["body"], TRUE), $response["body"]));
}

//A request of protocol version 2026-07-28, which gives the version and the client's capabilities in its _meta
function testMCPMessage($method, $params = array(), $id = 1) {
  $params["_meta"] = array("io.modelcontextprotocol/protocolVersion" => "2026-07-28",
    "io.modelcontextprotocol/clientCapabilities" => new stdClass());
  return(array("jsonrpc" => "2.0", "id" => $id, "method" => $method, "params" => $params));
}

//The headers a request of protocol version 2026-07-28 repeats its version, method and tool name in
function testMCPHeaders($method, $name = NULL) {
  $headers = array("mcp-protocol-version" => "2026-07-28", "mcp-method" => $method);
  if ($name !== NULL) {$headers["mcp-name"] = $name;}
  return($headers);
}

//A tool's result, checked against the tool's output schema when it isn't an error
function tool($name, $arguments) {
  $result = mcpCallTool($name, $arguments);
  check(is_array($result), "$name is a tool");
  if ($result["isError"]) {return($result);}
  $schema = NULL;
  foreach (mcpTools() as $tool) {
    if ($tool["name"] === $name) {$schema = $tool["outputSchema"];}
  }
  $data = json_decode(mcpJSON($result["structuredContent"]), TRUE);
  $problems = schemaProblems($data, $schema);
  check(!$problems, "$name matches its output schema: ".implode("; ", $problems));
  check($result["content"][0]["text"] === mcpJSON($result["structuredContent"]), "$name gives its data as text too");
  return($result);
}

function toolError($result) {
  return($result["isError"] ? $result["content"][0]["text"] : NULL);
}

//The database, as the tools use it: queries that give rows, and the prepared
//lookups of a record and of the links to and from it
class MCPFixtureResult {
  private $rows;
  function __construct($rows) {$this->rows = $rows;}
  function fetch_assoc() {return(array_shift($this->rows));}
  function close() {}
}
class MCPFixtureStatement {
  private $db;
  private $sql;
  private $values = array();
  function __construct($db, $sql) {$this->db = $db; $this->sql = $sql;}
  function bind_param($types, &...$values) {
    check(strlen($types) === count($values), 'All lookup values are bound');
    $this->values = $values;
    return(TRUE);
  }
  function execute() {return(!$this->db->fail);}
  function get_result() {
    if (strpos($this->sql, '`links` WHERE') !== FALSE) {return(new MCPFixtureResult($this->db->links));}
    //The annotations of a recording, read by the recording they mark
    if (strpos($this->sql, '(`recording_source`, `source_id`) IN') !== FALSE) {return(new MCPFixtureResult($this->db->annotations));}
    $key = strtolower($this->values[0]."/".$this->values[1]);
    return(new MCPFixtureResult(isset($this->db->records[$key]) ? array($this->db->records[$key]) : array()));
  }
  function close() {}
}
class MCPFixtureDB {
  public $queries = array();
  public $rows = array();
  public $total = 0;
  public $records = array();
  public $links = array();
  public $annotations = array();
  public $fail = FALSE;
  public $throw = FALSE;
  function real_escape_string($value) {return(addslashes((string)$value));}
  function query($sql) {
    $this->queries[] = $sql;
    if ($this->throw) {throw new mysqli_sql_exception("Table 'audioblast.v-recordings' doesn't exist");}
    if ($this->fail) {return(FALSE);}
    if (strpos($sql, 'COUNT(*)') !== FALSE) {return(new MCPFixtureResult(array(array("total" => (string)$this->total))));}
    return(new MCPFixtureResult($this->rows));
  }
  function prepare($sql) {
    $this->queries[] = $sql;
    return(new MCPFixtureStatement($this, $sql));
  }
  //A record that the lookups find by its source and id, regardless of case
  function hold($record, $idField = "id") {
    $this->records[strtolower($record["source"]."/".$record[$idField])] = $record;
  }
}
$db = new MCPFixtureDB();

// Routing: the server's address, with or without a slash, and nothing else.
foreach (array("/mcp" => TRUE, "/mcp/" => TRUE, "/mcp?x=1" => TRUE, "/mcpx" => FALSE, "/data/mcp/" => FALSE, "/" => FALSE) as $path => $isMCP) {
  $_SERVER["REQUEST_URI"] = $path;
  check(isMCPPage() === $isMCP, 'Routing of '.$path);
}

// Protocol: clients of earlier versions start with initialize.
$initialize = array("jsonrpc" => "2.0", "id" => 1, "method" => "initialize",
  "params" => array("protocolVersion" => "2025-06-18", "capabilities" => new stdClass(),
    "clientInfo" => array("name" => "Test", "version" => "1")));
list($status, $headers, $response) = testMCPResponse($initialize);
check($status === 200 && valueAt($response, array("result", "protocolVersion")) === "2025-06-18", 'Initialize gives the version asked for');
check(valueAt($response, array("result", "serverInfo", "name")) === "audioblast-api", 'Server named');
check(valueAt($response, array("result", "capabilities", "tools")) === array(), 'Server has tools');
check(strpos((string)valueAt($response, array("result", "instructions")), "audioBLAST!: ") === 0, 'Instructions say what audioBLAST! is first');
check(strpos(mcpInstructions(), "give its URI") !== FALSE, 'Instructions say how to cite a record');
check(count(preg_grep('/^Mcp-Session-Id:/i', $headers)) === 0, 'No session');
check(in_array("Content-Type: application/json; charset=utf-8", $headers), 'JSON response');
$initialize["params"]["protocolVersion"] = "2024-11-05";
list(, , $response) = testMCPResponse($initialize);
check(valueAt($response, array("result", "protocolVersion")) === "2025-11-25", 'Unsupported version gets the newest that starts with initialize');
list($status, , , $body) = testMCPResponse(array("jsonrpc" => "2.0", "method" => "notifications/initialized"), array("mcp-protocol-version" => "2025-06-18"));
check($status === 202 && $body === NULL, 'Notification accepted without a reply');
list(, , , $body) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 2, "method" => "ping"), array("mcp-protocol-version" => "2025-06-18"));
check(strpos((string)$body, '"result":{}') !== FALSE, 'Ping gives an empty object');
list($status, , $response) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 3, "method" => "tools/list"), array("mcp-protocol-version" => "2025-11-25"));
check($status === 200 && array_column((array)valueAt($response, array("result", "tools")), "name")
  === array("list_modules", "describe_module", "query_module", "suggest_values", "get_record"), 'Tools listed in order');
list($status, , $response) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 4, "method" => "tools/list"));
check($status === 200 && count((array)valueAt($response, array("result", "tools"))) === 5, 'Clients of 2025-03-26 give no version header');
list($status, , $response) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 5, "method" => "resources/list"), array("mcp-protocol-version" => "2025-11-25"));
check($status === 200 && valueAt($response, array("error", "code")) === -32601, 'Unknown method is an error only in the response for earlier versions');
list($status, , $response) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 6, "method" => "tools/list"), array("mcp-protocol-version" => "2099-01-01"));
check($status === 400 && valueAt($response, array("error", "code")) === -32022, 'Unsupported version in header refused');

// Protocol: from 2026-07-28 each request stands alone.
list($status, , $response, $body) = testMCPResponse(testMCPMessage("server/discover"), testMCPHeaders("server/discover"));
check($status === 200 && valueAt($response, array("result", "supportedVersions")) === mcpVersions(), 'Discover gives the versions');
check(strpos($body, '"capabilities":{"tools":{}}') !== FALSE, 'Capabilities are an object');
check(valueAt($response, array("result", "resultType")) === "complete" && valueAt($response, array("result", "ttlMs")) === 300000
  && valueAt($response, array("result", "cacheScope")) === "public", 'Complete, cacheable result');
check(valueAt($response, array("result", "_meta", "io.modelcontextprotocol/serverInfo", "title")) === "audioBLAST! API", 'Server info in _meta');
list($status, , $response) = testMCPResponse(testMCPMessage("server/discover"), array("mcp-protocol-version" => "2026-07-28"));
check($status === 400 && valueAt($response, array("error", "code")) === -32020, 'Method header required');
list($status, , $response) = testMCPResponse(testMCPMessage("server/discover"), testMCPHeaders("tools/list"));
check($status === 400 && valueAt($response, array("error", "code")) === -32020, 'Method header must match');
list($status, , $response) = testMCPResponse(testMCPMessage("ping"), testMCPHeaders("ping"));
check($status === 404 && valueAt($response, array("error", "code")) === -32601, 'Ping is not a 2026-07-28 method');
$call = testMCPMessage("tools/call", array("name" => "list_modules", "arguments" => new stdClass()));
list($status, , $response) = testMCPResponse($call, testMCPHeaders("tools/call", "get_record"));
check($status === 400 && valueAt($response, array("error", "code")) === -32020, 'Tool name header must match');
list($status, , $response) = testMCPResponse($call, testMCPHeaders("tools/call", "=?base64?".base64_encode("list_modules")."?="));
check($status === 200 && valueAt($response, array("result", "isError")) === FALSE
  && valueAt($response, array("result", "resultType")) === "complete", 'Encoded tool name decoded, tool called');
list($status, , $response) = testMCPResponse(testMCPMessage("tools/call", array("name" => "drop_tables")), testMCPHeaders("tools/call", "drop_tables"));
check($status === 200 && valueAt($response, array("error", "code")) === -32602, 'Unknown tool');

// Protocol: what isn't a request.
list($status, $headers) = testMCPResponse("", array(), "GET");
check($status === 405 && in_array("Allow: POST, OPTIONS", $headers), 'GET not allowed');
list($status) = testMCPResponse("", array(), "DELETE");
check($status === 405, 'DELETE not allowed');
list($status, $headers) = testMCPResponse("", array(), "OPTIONS");
check($status === 204 && !preg_grep('/^Access-Control-Allow-(Origin|Methods|Headers):/i', $headers),
  'Preflight answered, leaving the cross-origin headers to the server in front of the API');
list($status, , $response) = testMCPResponse("{not json");
check($status === 400 && valueAt($response, array("error", "code")) === -32700 && !array_key_exists("id", (array)$response), 'Parse error without an id');
list($status, , $response) = testMCPResponse(array(testMCPMessage("tools/list"), testMCPMessage("server/discover", array(), 2)), testMCPHeaders("tools/list"));
check($status === 400 && valueAt($response, array("error", "code")) === -32600, 'Batches refused');
$nullID = testMCPMessage("tools/list"); $nullID["id"] = NULL;
list($status, , $response) = testMCPResponse($nullID, testMCPHeaders("tools/list"));
check($status === 400 && valueAt($response, array("error", "code")) === -32600, 'Null id refused');
list($status) = testMCPResponse(str_repeat(" ", MCP_BODY_LIMIT + 1));
check($status === 413, 'Too large');
list($status) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 7, "result" => new stdClass()));
check($status === 202, 'A response from the client is ignored');

// The tools: named as MCP asks, described, taking and giving objects, only reading.
$tools = mcpTools();
foreach ($tools as $tool) {
  check(preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', $tool["name"]) === 1 && $tool["description"] != ""
    && $tool["inputSchema"]["type"] === "object" && $tool["outputSchema"]["type"] === "object"
    && $tool["annotations"]["readOnlyHint"] === TRUE, 'Tool '.$tool["name"].' well formed');
}
check(strpos(mcpJSON($tools), '"inputSchema":{"type":"object","additionalProperties":false}') !== FALSE, 'A tool without arguments takes an empty object');
$names = $tools[1]["inputSchema"]["properties"]["module"]["enum"];
check(count($names) === 17 && in_array("recordings", $names) && in_array("traitstaxa", $names), 'The data modules are the modules');
check(!array_intersect($names, array("aci", "birdnet_selection", "modules", "suncalc", "bioacoustica")), 'No analysis, standalone or source modules');
check(schemaProblems(array("modules" => "none"), $tools[0]["outputSchema"]) === array("/modules isn't array"), "The schema check finds a result that doesn't match");

// list_modules
$listed = tool("list_modules", array());
$byName = array_column($listed["structuredContent"]["modules"], NULL, "name");
check(array_keys($byName) === $names, 'Every data module listed');
check($byName["annomate"]["record_uri_template"] === "https://api.audioblast.org/annotation/{source}/{annotation_id}", 'Record URIs by the id they use');
check($byName["ecoint"]["record_uri_template"] === NULL, 'A module whose records have no URIs');
check(strpos($byName["recordings"]["description"], "Audiovisual Core") !== FALSE, 'Modules described as they describe themselves');

// describe_module
$described = tool("describe_module", array("module" => "recordings"))["structuredContent"];
$filters = array_column($described["filters"], NULL, "name");
check($filters["taxon"]["match"] === "words" && $filters["taxon"]["suggest"] === TRUE, 'A full-text filter matches by words');
check($filters["id"]["match"] === "exact" && $filters["id"]["multiple"] === TRUE, 'An exact filter that takes several values');
check($filters["locality"]["match"] === "contains" && $filters["duration"]["match"] === "range", 'Contains and range filters');
check($filters["source"]["match"] === "exact", "A recording's source is matched exactly, as the key on source and id needs");
check(!isset($filters["peaks_url"]) && in_array("peaks_url", $described["fields"]), 'A field that is not a filter is still a field');
check(!isset($filters["spectrogram_url"]) && in_array("spectrogram_url", $described["fields"]), "A recording's spectrogram tiles are a field too");
check(!in_array("output", $described["fields"]) && !isset($filters["output"]) && !isset($filters["format"]), 'Output and format are not the tools\' to give');
check(isset($described["matches"]["words"]) && strpos($described["matches"]["words"], "Gryllus") !== FALSE, 'The ways of matching are explained');
$taxa = tool("describe_module", array("module" => "taxa"))["structuredContent"];
check($taxa["see_also"] && strpos(implode(" ", $taxa["see_also"]), "<") === FALSE, 'See also as plain text');
check($taxa["record_uri_template"] === "https://api.audioblast.org/taxon/{source}/{id}", 'Taxon URIs');
check(strpos((string)toolError(mcpCallTool("describe_module", array("module" => "recording"))), "recordings") !== FALSE, 'An unknown module lists the modules');
check(toolError(mcpCallTool("describe_module", array("module" => "aci"))) !== NULL, 'Analysis modules are not reached');
check(toolError(mcpCallTool("describe_module", array("module" => "../settings/db"))) !== NULL, 'A path is not a module');

// query_module: filters are checked as the API checks them.
$db->rows = array();
$problem = toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("family" => "Gryllidae"))));
check(strpos((string)$problem, "Parameter `family` is not recognised. This module can be filtered by: source, id,") === 0, 'Unknown filter refused, naming the filters');
check(strpos($problem, "output") === FALSE, 'The filters named are those a tool can give');
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("output" => "Turtle")))), "Parameter `output` is not recognised") === 0, 'Output is not a filter');
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("page" => 2)))), "argument of its own") !== FALSE, 'Page is not a filter');
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("path" => "x")))), "Parameter `path` is not recognised") === 0, "The rewrite's parameter is not a filter");
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("peaks_url" => "x")))), "cannot be filtered on") !== FALSE, 'A field that is not a filter');
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("taxon" => array("a", "b"))))), "takes one value") !== FALSE, 'One value where one is taken');
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("id" => TRUE)))) !== NULL, 'A value that is not text or a number');
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("id" => array(array("12")))))) !== NULL, 'Lists of lists');
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => array("id" => array("a" => "12"))))) !== NULL, 'Values as a list');
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "filters" => "id=12"))) !== NULL, 'Filters as an object');
foreach (array(0, 101, "ten", 2.5) as $size) {
  check(toolError(mcpCallTool("query_module", array("module" => "recordings", "page_size" => $size))) !== NULL, 'Page size out of range: '.var_export($size, TRUE));
}
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "page" => 0))) !== NULL, 'Pages start at 1');
check(toolError(mcpCallTool("query_module", array("module" => "recordings", "count" => "yes"))) !== NULL, 'Count is true or false');
check(!$db->queries, 'Nothing refused reaches the database');

// query_module: a page of records, with one more asked for to tell whether there is another.
$row = function($id) {return(array("source" => "bio.acousti.ca", "id" => $id, "taxon" => "Gryllus campestris", "license" => "CC BY"));};
$db->rows = array($row("12"), $row("15"), $row("17"));
$page = tool("query_module", array("module" => "recordings", "page" => 2, "page_size" => 2.0,
  "filters" => array("id" => array("12", 15, "17"), "source" => "O'Brien", "duration" => "10:20")))["structuredContent"];
$sql = $db->queries[0];
check(count($db->queries) === 1, 'No count unless asked for');
check(strpos($sql, "SELECT `source` as `source`, `id` as `id`") === 0 && strpos($sql, "FROM `audioblast`.`v-recordings`") !== FALSE, 'Every field, by its name');
check(strpos($sql, "`id` IN ('12', '15', '17')") !== FALSE, 'Several values match any of them');
check(strpos($sql, "`source` = 'O\\'Brien'") !== FALSE, 'Values escaped as the API escapes them');
check(strpos($sql, "CAST(`Duration` AS DECIMAL(65,10)) >= CAST('10' AS DECIMAL(65,10)) AND CAST(`Duration` AS DECIMAL(65,10)) <= CAST('20' AS DECIMAL(65,10))") !== FALSE, 'Ranges as the API reads them');
check(endsWith($sql, " LIMIT 2, 3;"), 'The second page, and one record more');
check($page["more"] === TRUE && count($page["rows"]) === 2 && $page["total"] === NULL && $page["page"] === 2 && $page["page_size"] === 2, 'A page and whether there is another');
check(array_keys($page["rows"][0])[0] === "record_uri" && $page["rows"][0]["record_uri"] === "https://api.audioblast.org/recording/bio.acousti.ca/12", 'Records come with their URIs');
check($page["rows"][1]["license"] === "CC BY", 'Records as the database gives them');
$db->queries = array(); $db->rows = array($row("12")); $db->total = 1234;
$counted = tool("query_module", array("module" => "recordings", "filters" => array("id" => "12, 15"), "count" => TRUE))["structuredContent"];
check(strpos($db->queries[0], "`id` IN ('12', '15')") !== FALSE, 'Several values joined with commas');
check($counted["more"] === FALSE && $counted["total"] === 1234, 'Counted when asked');
check($db->queries[1] === "SELECT COUNT(*) as `total` FROM `audioblast`.`v-recordings` WHERE `id` IN ('12', '15') ;", 'Counted with the same filters');
$db->queries = array();
$ecoint = tool("query_module", array("module" => "ecoint"))["structuredContent"];
check(!isset($ecoint["rows"][0]["record_uri"]) && strpos($db->queries[0], "WHERE") === FALSE && endsWith($db->queries[0], " LIMIT 0, 21;"), 'No filters, the first page, and no URIs where records have none');
$db->fail = TRUE;
check(toolError(mcpCallTool("query_module", array("module" => "recordings"))) === "The query failed on the database.", 'A failed query');
$db->fail = FALSE; $db->throw = TRUE;
check(toolError(mcpCallTool("query_module", array("module" => "recordings"))) === "The query failed on the database.", 'A query that throws, as mysqli does from PHP 8.1');
$db->throw = FALSE;

// suggest_values
$db->queries = array(); $db->rows = array(array("taxon" => "Gryllus"), array("taxon" => NULL), array("taxon" => "Gryllus bimaculatus"));
$suggested = tool("suggest_values", array("module" => "taxa", "field" => "taxon", "text" => "Gryll", "limit" => 2))["structuredContent"];
check($db->queries[0] === "SELECT DISTINCT(`taxon`) as `taxon`  FROM `audioblast`.`taxa` WHERE `taxon` LIKE 'Gryll%'  LIMIT 0, 3;", 'Values starting with the text, as autocomplete asks for them');
check($suggested["values"] === array("Gryllus") && $suggested["more"] === TRUE, 'Values, without the empty ones');
$db->queries = array();
tool("suggest_values", array("module" => "recordings", "field" => "taxon", "text" => "Gryllus", "match" => "contains"));
check(strpos($db->queries[0], "MATCH(`taxon`) AGAINST ('Gryllus*' IN BOOLEAN MODE)") !== FALSE, 'Contains on a full-text field searches its words');
$db->queries = array();
tool("suggest_values", array("module" => "recordings", "field" => "country", "filters" => array("source" => "xeno-canto")));
check(strpos($db->queries[0], "WHERE `source` = 'xeno-canto'  LIMIT 0, 21;") !== FALSE, 'Without text, every value of the records the filters match');
check(strpos((string)toolError(mcpCallTool("suggest_values", array("module" => "taxa", "field" => "genus"))), "these are: taxon, rank.") !== FALSE, 'A field without values to suggest names those with them');
check(toolError(mcpCallTool("suggest_values", array("module" => "taxa", "field" => "taxon", "match" => "sounds like"))) !== NULL, 'Starts or contains');
check(toolError(mcpCallTool("suggest_values", array("module" => "taxa", "field" => "taxon", "limit" => 0))) !== NULL, 'Limit out of range');

// get_record
$ref = array('source' => 'fixture', 'id' => 'book/a #1', 'type' => 'article', 'title' => 'A title', 'year' => '1922');
$uri = 'https://api.audioblast.org/reference/fixture/book/a%20%231';
$db->hold($ref);
$db->links = array(array('source' => 'curator', 'id' => 'citation',
  'subject_type' => 'recordings', 'subject_source' => 'fixture', 'subject_id' => 'rec1',
  'predicate' => 'http://purl.org/dc/terms/isReferencedBy',
  'object_type' => 'references', 'object_source' => 'fixture', 'object_id' => $ref['id'],
  'qualifier' => NULL, 'remarks' => 'p. 7'));
$got = tool("get_record", array("uri" => $uri))["structuredContent"];
check($got["uri"] === $uri && $got["module"] === "references" && $got["record"] === $ref && $got["note"] === NULL, 'A record by its URI');
check($got["linked_data"]["@context"] === rdfContext() && $got["linked_data"]["@graph"] === rdfResponseNodes($db, loadModule("references"), array($ref), TRUE), 'With the linked data its URI gives');
check(strpos(mcpJSON($got["linked_data"]), "https://api.audioblast.org/recording/fixture/rec1") !== FALSE, 'Including the records linked to it');
check(tool("get_record", array("module" => "references", "source" => "fixture", "id" => "book/a #1"))["structuredContent"]["uri"] === $uri, 'A record by its module, source and id');
check(tool("get_record", array("uri" => "/reference/fixture/book/a%20%231"))["structuredContent"]["uri"] === $uri, 'A record by its path');
$db->links = array();
$db->hold(array('source' => 'Fixture', 'id' => 'Case', 'title' => 'Held with capitals'));
check(tool("get_record", array("module" => "references", "source" => "fixture", "id" => "case"))["structuredContent"]["uri"] === "https://api.audioblast.org/reference/Fixture/Case", 'Each record has one URI, whatever the case asked for');
check(strpos((string)toolError(mcpCallTool("get_record", array("module" => "references", "source" => "fixture", "id" => "none"))), "No record of references has the source `fixture` and the id `none`.") === 0, 'A record that is not held');
check(strpos((string)toolError(mcpCallTool("get_record", array("module" => "ecoint", "source" => "a", "id" => "1"))), "no URIs of their own") !== FALSE, 'Records without URIs');
check(toolError(mcpCallTool("get_record", array("uri" => "https://example.org/reference/fixture/1"))) !== NULL, 'A URI elsewhere is not a record');
check(toolError(mcpCallTool("get_record", array("uri" => "https://api.audioblast.org/data/recordings/"))) !== NULL, 'An endpoint is not a record');
check(toolError(mcpCallTool("get_record", array("module" => "references", "source" => "fixture"))) !== NULL, 'A record needs its id');
check(toolError(mcpCallTool("get_record", array())) !== NULL, 'A record needs naming');
// A taxon at its own URI comes with its classification.
$db->hold(array('source' => 'fixture', 'id' => '2', 'parent_id' => '1', 'taxon' => 'Gryllus campestris', 'rank' => 'species'));
$db->hold(array('source' => 'fixture', 'id' => '1', 'parent_id' => '', 'taxon' => 'Gryllus', 'rank' => 'genus'));
$taxon = tool("get_record", array("uri" => "https://api.audioblast.org/taxon/fixture/2"))["structuredContent"];
check(in_array("https://api.audioblast.org/taxon/fixture/1", array_column($taxon["linked_data"]["@graph"], "@id")), 'A taxon comes with the taxa it is inside');
// A record with more links than are worth giving at once says where to find them.
for ($i = 0; $i < 1000; $i++) {
  $db->links[] = array('source' => 'curator', 'id' => 'many'.$i, 'subject_type' => 'recordings',
    'subject_source' => 'fixture', 'subject_id' => 'rec'.$i, 'predicate' => 'http://purl.obolibrary.org/obo/IAO_0000136',
    'object_type' => 'references', 'object_source' => 'fixture', 'object_id' => $ref['id'], 'qualifier' => NULL, 'remarks' => NULL);
}
$crowded = tool("get_record", array("uri" => $uri))["structuredContent"];
check($crowded["linked_data"] === NULL && strpos($crowded["note"], "query_module on links") !== FALSE && $crowded["record"] === $ref, 'Too many links to give at once');
check(strpos($crowded["note"], "annomate") === FALSE, 'A record without regions of interest is not sent to annomate for them');
$db->links = array();
// A recording comes with the regions of interest annotations mark on it. They
// are not links, so one with too many to give at once says where they are.
$recording = array_merge(array_fill_keys(array_keys(loadModule("recordings")["params"]), NULL),
  array('source' => 'fixture', 'id' => 'rec1'));
$recordingURI = "https://api.audioblast.org/recording/fixture/rec1";
$db->hold($recording);
$region = function($i) {
  return(array('source' => 'corpus', 'annotation_id' => 'roi-'.$i, 'recording_source' => 'fixture', 'source_id' => 'rec1'));
};
$db->annotations = array($region(1), $region(2));
$marked = tool("get_record", array("uri" => $recordingURI))["structuredContent"];
check(array_column($marked["linked_data"]["@graph"], NULL, "@id")[$recordingURI]["ac:hasROI"] ===
  array(rdfIRI("https://api.audioblast.org/annotation/corpus/roi-1"), rdfIRI("https://api.audioblast.org/annotation/corpus/roi-2"))
  && $marked["note"] === NULL, 'A recording comes with its regions of interest');
$db->annotations = array_map($region, range(1, 4000));
$crowdedRecording = tool("get_record", array("uri" => $recordingURI))["structuredContent"];
check($crowdedRecording["linked_data"] === NULL && strpos($crowdedRecording["note"], "query_module on links") !== FALSE,
  'A recording with too many regions to give at once comes without its linked data');
check(strpos($crowdedRecording["note"], "query_module on annomate, filtered by recording_source `fixture` and source_id `rec1`") !== FALSE,
  'and says its regions are annotations, and how to find them');
$db->annotations = array();
$db->fail = TRUE;
check(toolError(mcpCallTool("get_record", array("uri" => $uri))) === "The query failed on the database.", 'A failed lookup is not a missing record');
$db->fail = FALSE;

// Corpora: the instructions say how to find them and their regions with the tools, after everything else, so that a client
// that cuts the instructions short keeps what it needs for every other record.
$corpora = mcpCorporaInstructions();
check(endsWith(mcpInstructions(), "\n\n".$corpora), 'Corpora come last in the instructions');
foreach (array(MCP_CORPUS_TYPE, "http://purl.org/dc/terms/type", "http://purl.org/dc/terms/isPartOf", "http://purl.org/dc/terms/source") as $iri) {
  check(strpos($corpora, $iri) !== FALSE, 'The instructions name '.$iri);
}
check(strpos($tools[4]["description"], "corpus") !== FALSE, 'get_record says a corpus comes without its regions');
$naming = array();
foreach (array_merge(glob("core/*.php"), glob("modules/*/module.php")) as $file) {
  if (strpos(file_get_contents($file), MCP_CORPUS_TYPE) !== FALSE) {$naming[] = $file;}
}
check($naming === array("core/mcp-tools.php"), 'The placeholder address of the corpus term is in one place: '.implode(", ", $naming));
// Each filter the instructions name is one its module has, matching exactly, and taking a list where a list of ids is given.
$named = array(
  "links" => array("predicate" => FALSE, "object_id" => FALSE, "object_type" => FALSE, "object_source" => FALSE, "qualifier" => FALSE),
  "annomate" => array("annotation_id" => TRUE, "recording_source" => FALSE, "source_id" => FALSE),
  "details" => array("type" => FALSE, "record_source" => FALSE, "id" => TRUE)
);
foreach ($named as $name => $filters) {
  $described = array_column(tool("describe_module", array("module" => $name))["structuredContent"]["filters"], NULL, "name");
  foreach ($filters as $filter => $list) {
    check(strpos($corpora, $filter) !== FALSE, "The instructions name $filter");
    check(($described[$filter]["match"] ?? NULL) === "exact" && (!$list || $described[$filter]["multiple"]),
      "$name has the filter $filter, matching exactly".($list ? " and taking a list" : ""));
  }
}
// The steps, as the database gets them.
$where = function($sql, $conditions, $message) {
  foreach ($conditions as $condition) {check(strpos($sql, $condition) !== FALSE, $message.": ".$condition);}
};
$db->queries = array(); $db->rows = array();
tool("query_module", array("module" => "links", "filters" => array("predicate" => "http://purl.org/dc/terms/type", "object_id" => MCP_CORPUS_TYPE)));
$where($db->queries[0], array("`predicate` = 'http://purl.org/dc/terms/type'", "`object_id` = '".MCP_CORPUS_TYPE."'"), 'Corpora found by their type');
$db->queries = array();
$db->rows = array(array('source' => 'corpus', 'id' => 'l1', 'subject_type' => 'annomate', 'subject_source' => 'corpus', 'subject_id' => 'corpus-v2-12-1',
  'predicate' => 'http://purl.org/dc/terms/isPartOf', 'object_type' => 'references', 'object_source' => 'corpus', 'object_id' => 'corpus-v2',
  'qualifier' => 'Validation', 'remarks' => NULL));
$parts = tool("query_module", array("module" => "links", "page_size" => MCP_PAGE_MAX, "filters" => array("predicate" => "http://purl.org/dc/terms/isPartOf",
  "object_type" => "references", "object_source" => "corpus", "object_id" => "corpus-v2", "qualifier" => "Validation")))["structuredContent"];
$where($db->queries[0], array("`predicate` = 'http://purl.org/dc/terms/isPartOf'", "`object_type` = 'references'", "`object_source` = 'corpus'",
  "`object_id` = 'corpus-v2'", "`qualifier` = 'Validation'"), "A split of a corpus's regions");
check($parts["rows"][0]["subject_id"] === "corpus-v2-12-1" && $parts["rows"][0]["qualifier"] === "Validation", "Each link names a region and its split");
$ids = array();
for ($i = 1; $i <= MAX_FILTER_VALUES; $i++) {$ids[] = "corpus-v2-12-".$i;}
$db->queries = array(); $db->rows = array();
tool("query_module", array("module" => "annomate", "page_size" => MCP_PAGE_MAX, "filters" => array("annotation_id" => $ids)));
check(strpos($db->queries[0], "`annotation_id` IN ('corpus-v2-12-1', 'corpus-v2-12-2', ") !== FALSE && endsWith($db->queries[0], " LIMIT 0, 101;"),
  "A page of a corpus's regions by their ids");
$ids[] = "one too many";
check(strpos((string)toolError(mcpCallTool("query_module", array("module" => "annomate", "filters" => array("annotation_id" => $ids)))),
  "takes at most ".MAX_FILTER_VALUES.".") !== FALSE && strpos($corpora, "up to ".MAX_FILTER_VALUES." of those ids") !== FALSE,
  'The instructions give as many ids at once as annomate takes');
$db->queries = array();
tool("query_module", array("module" => "details", "filters" => array("type" => "annomate", "record_source" => "corpus", "id" => array("corpus-v2-12-1", "corpus-v2-12-2"))));
$where($db->queries[0], array("`type` = 'annomate'", "`record_source` = 'corpus'", "`id` IN ('corpus-v2-12-1', 'corpus-v2-12-2')"), "The regions' other values");
$regionFields = tool("describe_module", array("module" => "annomate"))["structuredContent"]["fields"];
check(in_array("freq_low", $regionFields) && in_array("freq_high", $regionFields) && strpos($corpora, "are its freq_low and freq_high") !== FALSE,
  "A region's frequency bounds are its own");
$db->queries = array();
tool("query_module", array("module" => "annomate", "filters" => array("recording_source" => "xeno-canto", "source_id" => "280667")));
$where($db->queries[0], array("`recording_source` = 'xeno-canto'", "`source_id` = '280667'"), 'Every annotation of a recording, whichever source gave it');
// A corpus's thousands of regions are more than get_record gives, and its note says how to page through them instead.
$corpus = array('source' => 'corpus', 'id' => 'corpus-v2', 'type' => 'misc', 'title' => 'A corpus');
$db->hold($corpus);
for ($i = 0; $i < 1000; $i++) {
  $db->links[] = array('source' => 'corpus', 'id' => 'part'.$i, 'subject_type' => 'annomate', 'subject_source' => 'corpus', 'subject_id' => 'corpus-v2-12-'.$i,
    'predicate' => 'http://purl.org/dc/terms/isPartOf', 'object_type' => 'references', 'object_source' => 'corpus', 'object_id' => 'corpus-v2',
    'qualifier' => 'Training', 'remarks' => NULL);
}
$got = tool("get_record", array("module" => "references", "source" => "corpus", "id" => "corpus-v2"))["structuredContent"];
check($got["record"] === $corpus && $got["linked_data"] === NULL && strpos($got["note"], "object_type, object_source and object_id") !== FALSE,
  'A corpus comes without its regions, and a note on paging through them');
$db->links = array();

// Reading the data: what the instructions, the tools and the modules' notes tell a model is true of what the tools do.
$data = mcpDataInstructions();
check(strpos(mcpInstructions(), "\n\n".$data."\n\n".$corpora) !== FALSE, 'How to read the records comes before corpora');
$described = array();
foreach (array("taxa", "traits", "recordings", "recordingstaxa", "annomate", "links", "details") as $name) {
  $described[$name] = tool("describe_module", array("module" => $name))["structuredContent"];
  $described[$name]["filters"] = array_column($described[$name]["filters"], NULL, "name");
}
// Names: exact filters match one form of a name, so other forms are looked for as suggest_values can.
check($described["taxa"]["filters"]["taxon"]["match"] === "exact" && $described["traits"]["filters"]["taxon"]["match"] === "exact"
  && $described["recordingstaxa"]["filters"]["species"]["match"] === "exact", 'Taxa are filtered by one form of their name');
check(strpos($data, "Gryllus Gryllus campestris") !== FALSE && strpos($data, "contains as the match") !== FALSE
  && in_array("contains", $tools[3]["inputSchema"]["properties"]["match"]["enum"], TRUE), 'Other forms of a name are looked for with suggest_values');
// Taxa of every source are gathered by their links to CoL, and recordings' links to their taxa say which are in the background.
foreach (array("http://www.w3.org/2004/02/skos/core#exactMatch", "http://purl.obolibrary.org/obo/IAO_0000136",
  "https://vocab.audioblast.org/cv/recordingContent#NonFocalTaxa", "source CoL") as $named) {
  check(strpos($data, $named) !== FALSE, 'The instructions name '.$named);
}
check($described["links"]["filters"]["qualifier"]["match"] === "exact", "A link's qualifier is chosen exactly");
check(strpos(mcpInstructions(), "an empty license field means the licence is unknown") !== FALSE && in_array("license", $described["recordings"]["fields"]),
  'An empty licence is unknown');
check(strpos(mcpInstructions(), "Most predicates of links come from IAO, Dublin Core, Darwin Core and SKOS") !== FALSE
  && strpos(mcpInstructions(), "https://vocab.audioblast.org/api/mcp") !== FALSE, 'Where the terms of links are defined');
// Words: any one of them, only the last as the start of a word.
$db->queries = array(); $db->rows = array();
tool("query_module", array("module" => "recordings", "filters" => array("taxon" => "Gryllus campestris")));
check(strpos($db->queries[0], "MATCH(`taxon`) AGAINST ('Gryllus campestris*' IN BOOLEAN MODE)") !== FALSE
  && strpos(mcpMatches()["words"], "the last of them as the start of a word") !== FALSE
  && strpos(mcpMatches()["words"], "every other campestris") !== FALSE, 'Words match as the explanation says');
// suggest_values: no order, and contains on a name matches by words, even where its filter matches exactly.
$db->queries = array();
tool("suggest_values", array("module" => "taxa", "field" => "taxon", "text" => "campestris", "match" => "contains"));
check(strpos($db->queries[0], "ORDER BY") === FALSE && strpos($tools[3]["description"], "no particular order") !== FALSE, 'Values in no particular order');
check(strpos($db->queries[0], "MATCH(`taxon`) AGAINST ('campestris*' IN BOOLEAN MODE)") !== FALSE
  && strpos($tools[3]["description"], "such as the names of taxa and recordings, contains matches as the words match does") !== FALSE,
  "Contains on a taxon's name matches by words");
// A comma is part of the value of a filter that takes one.
$db->queries = array();
tool("query_module", array("module" => "recordings", "filters" => array("country" => "GB,FR")));
check($described["recordings"]["filters"]["country"]["multiple"] === FALSE && strpos($db->queries[0], "`country` = 'GB,FR'") !== FALSE
  && strpos($tools[2]["inputSchema"]["properties"]["filters"]["description"], "country GB,FR finds nothing") !== FALSE, 'A comma in a value of its own');
// get_record never gives details, which have no linked data, and says how to find them.
check(!isset(loadModule("details")["rdf"]) && strpos($tools[4]["description"], "details") !== FALSE
  && isset($described["details"]["filters"]["record_source"], $described["details"]["filters"]["type"], $described["details"]["filters"]["id"]),
  "A record's details are found apart from it");
// The modules' notes, and the fields and filters they name.
$notes = function($name, $words, $message) use ($described) {
  foreach ($words as $word) {check(strpos((string)$described[$name]["source_notes"], $word) !== FALSE, $message.': '.$word);}
};
$notes("recordings", array("recording_type Soundscape", "Sounds of Norway", "empty license", "Animal Sound Archive (TSA)", "calculated_hash"), 'Recordings notes');
check(isset($described["recordings"]["filters"]["recording_type"], $described["recordings"]["filters"]["calculated_hash"]), 'Soundscapes and shared files can be filtered');
$notes("recordingstaxa", array("several rows", "source and id"), 'Recordings-taxa notes');
$notes("traits", array("Peak Frequency (kHz)", "value_min and value_max", "inference_notes", "type traits, record_source bio.acousti.ca"), 'Traits notes');
check($described["traits"]["filters"]["value"]["match"] === "exact" && $described["traits"]["filters"]["value_min"]["match"] === "range"
  && isset($described["details"]["filters"]["name"]), 'Trait values are text, their ranges numbers, and their notes details');
$notes("annomate", array("BirdNet-Lite", "source_id is the id of the recording", "seconds", "freq_low and freq_high", "matched as text"), 'Annotation notes');
foreach (array("time_start", "time_end", "freq_low", "freq_high") as $bound) {
  check($described["annomate"]["filters"][$bound]["match"] === "exact", 'An annotation\'s '.$bound.' is matched as text');
}
check(isset($described["annomate"]["filters"]["annotator"]), 'Annotations can be filtered by annotator');

// Through the protocol, a tool's result is the result of tools/call.
list($status, , $response) = testMCPResponse(array("jsonrpc" => "2.0", "id" => 8, "method" => "tools/call",
  "params" => array("name" => "describe_module", "arguments" => array("module" => "links"))), array("mcp-protocol-version" => "2025-06-18"));
check($status === 200 && valueAt($response, array("result", "structuredContent", "name")) === "links", 'Tool called through the protocol');
check(mcpCallTool("delete_everything", array()) === NULL, 'No tool, no result');

print("mcp: all checks passed\n");
