<?php

/*
The MCP server, which lets AI applications that support the Model Context
Protocol query audioBLAST! through the tools in core/mcp-tools.php. Clients post
JSON-RPC messages to /mcp (MCP's Streamable HTTP transport). From protocol
version 2026-07-28, each request gives its protocol version and the client's
capabilities, so each is answered on its own. Clients of earlier versions start
with an initialize request. Those versions let a server keep no session, so
their requests are answered on their own too.

The protocol is handled as Ontomasticon handles it for vocab.audioblast.org
(ontomasticon/core/mcp.php, as of ontomasticon 631db33), and the functions here
keep its names and order so that a fix to one can be carried to the other. What
differs: the server is always on, its name and instructions are audioBLAST!'s,
and the cross-origin headers are left to the server in front of the API, which
adds them to every response.
*/

//The most bytes a request may have. Requests name a tool and give a few short arguments, so this is plenty.
define("MCP_BODY_LIMIT", 65536);

//How long clients, and caches shared between them, may keep what is the same for everyone, such as the list of tools
define("MCP_CACHE_SECONDS", 300);

//The version of the server, which changes when its tools do
define("MCP_SERVER_VERSION", "1.0.0");

//The protocol versions the server supports, newest first
function mcpVersions() {
  return(array("2026-07-28", "2025-11-25", "2025-06-18", "2025-03-26"));
}

//Whether a protocol version gives the version and the client's capabilities with each request, rather than starting with initialize
function mcpStatelessVersion($version) {
  return($version === "2026-07-28");
}

//Whether the request is to the MCP server, with or without a slash at the end of its address
function isMCPPage() {
  return(in_array(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), array("/mcp", "/mcp/"), TRUE));
}

//The request's headers, with their names in lower case, as mcpResponse() takes them
function mcpRequestHeaders() {
  $headers = array();
  foreach ($_SERVER as $key => $value) {
    if (strpos($key, "HTTP_") === 0 && is_string($value)) {
      $headers[strtolower(str_replace("_", "-", substr($key, 5)))] = $value;
    }
  }
  return($headers);
}

/*
Answer a request to the MCP server. One byte more than a request may have is
read, to tell when a request is too large. Anything printed while the request is
answered, such as a PHP warning, is dropped rather than sent ahead of the
response, where it would make the response something other than JSON.
*/
function mcpServe() {
  ob_start();
  $response = mcpResponse($_SERVER["REQUEST_METHOD"] ?? "GET", mcpRequestHeaders(),
    (string)file_get_contents("php://input", FALSE, NULL, 0, MCP_BODY_LIMIT + 1));
  ob_end_clean();
  http_response_code($response["status"]);
  foreach ($response["headers"] as $header) {
    header($header);
  }
  if ($response["body"] !== NULL) {
    print($response["body"]);
  }
}

//The response to a request to the MCP server, as array("status" => the HTTP status, "headers" => header lines, "body" => text,
//or NULL for none). $method is the HTTP method, and $headers have their names in lower case (see mcpRequestHeaders()).
function mcpResponse($method, $headers, $body) {
  //Browsers ask before letting scripts on other websites post requests, which they may, as they may use the rest of the API.
  //The server in front of the API says which methods and headers they may use, on this response as on every other.
  if ($method == "OPTIONS") {
    return(mcpEmptyResponse(204, array("Access-Control-Max-Age: 86400")));
  }
  //Earlier protocol versions also used GET, for messages from the server, and DELETE, to end a session. This server has neither.
  if ($method != "POST") {
    return(mcpEmptyResponse(405, array("Allow: POST, OPTIONS")));
  }
  if (strlen($body) > MCP_BODY_LIMIT) {
    return(mcpErrorResponse(413, NULL, -32600, "Invalid request: the request is too large."));
  }
  $message = json_decode($body, TRUE);
  if (json_last_error() !== JSON_ERROR_NONE) {
    return(mcpErrorResponse(400, NULL, -32700, "The request isn't valid JSON."));
  }
  //This also refuses several messages sent together in an array, which MCP no longer allows
  if (!is_array($message) || !isset($message["jsonrpc"]) || $message["jsonrpc"] !== "2.0") {
    return(mcpErrorResponse(400, NULL, -32600, "Invalid request: send a single JSON-RPC 2.0 message."));
  }
  if (!isset($message["method"]) || !is_string($message["method"])) {
    //A response to a request from the server, which clients of earlier versions could post. This server sends no requests.
    if (array_key_exists("id", $message) && (array_key_exists("result", $message) || array_key_exists("error", $message))) {
      return(mcpEmptyResponse(202));
    }
    return(mcpErrorResponse(400, NULL, -32600, "Invalid request: a request needs a method."));
  }
  //Notifications, such as notifications/initialized, need no reply
  if (!array_key_exists("id", $message)) {
    return(mcpEmptyResponse(202));
  }
  $id = $message["id"];
  if (!is_string($id) && !is_int($id)) {
    return(mcpErrorResponse(400, NULL, -32600, "Invalid request: the id must be a string or an integer."));
  }
  if (isset($message["params"]) && !is_array($message["params"])) {
    return(mcpErrorResponse(400, $id, -32602, "Invalid params: params must be an object."));
  }
  $params = isset($message["params"]) ? $message["params"] : array();
  $meta = (isset($params["_meta"]) && is_array($params["_meta"])) ? $params["_meta"] : array();
  $headerVersion = mcpHeader($headers, "mcp-protocol-version");
  if ($message["method"] != "initialize" && (isset($meta["io.modelcontextprotocol/protocolVersion"]) || mcpStatelessVersion($headerVersion))) {
    return(mcpStatelessResponse($id, $message["method"], $params, $meta, $headers));
  }
  //Clients of 2025-06-18 and later give the version they started with in a header. Earlier clients don't.
  if ($headerVersion !== NULL && !in_array($headerVersion, mcpVersions(), TRUE)) {
    return(mcpUnsupportedVersionResponse($id, $headerVersion));
  }
  return(mcpHandshakeResponse($id, $message["method"], $params));
}

//The response to a request of protocol version 2026-07-28, which gives the version and the client's capabilities in its _meta. Its
//headers repeat the version, the method and, for tools/call, the tool's name, so gateways can route it without reading it. They must
//match the request, so a gateway can't act on one request while this server answers another.
function mcpStatelessResponse($id, $method, $params, $meta, $headers) {
  $version = isset($meta["io.modelcontextprotocol/protocolVersion"]) ? $meta["io.modelcontextprotocol/protocolVersion"] : NULL;
  if (!is_string($version)) {
    return(mcpErrorResponse(400, $id, -32602, "Invalid params: _meta must give io.modelcontextprotocol/protocolVersion."));
  }
  if (mcpHeader($headers, "mcp-protocol-version") !== $version) {
    return(mcpErrorResponse(400, $id, -32020, "Header mismatch: the MCP-Protocol-Version header must give the protocol version in _meta."));
  }
  if (!mcpStatelessVersion($version)) {
    return(mcpUnsupportedVersionResponse($id, $version));
  }
  if (!isset($meta["io.modelcontextprotocol/clientCapabilities"]) || !is_array($meta["io.modelcontextprotocol/clientCapabilities"])) {
    return(mcpErrorResponse(400, $id, -32602, "Invalid params: _meta must give io.modelcontextprotocol/clientCapabilities."));
  }
  if (mcpHeader($headers, "mcp-method") !== $method) {
    return(mcpErrorResponse(400, $id, -32020, "Header mismatch: the Mcp-Method header must give the request's method."));
  }
  switch ($method) {
    case "server/discover":
      $result = array("supportedVersions" => mcpVersions(), "capabilities" => mcpCapabilities(), "instructions" => mcpInstructions()) + mcpCacheHints();
      break;
    case "tools/list":
      $result = array("tools" => mcpTools()) + mcpCacheHints();
      break;
    case "tools/call":
      if (!isset($params["name"]) || !is_string($params["name"])) {
        return(mcpErrorResponse(400, $id, -32602, "Invalid params: tools/call needs the name of a tool."));
      }
      if (mcpHeaderValue(mcpHeader($headers, "mcp-name")) !== $params["name"]) {
        return(mcpErrorResponse(400, $id, -32020, "Header mismatch: the Mcp-Name header must give the tool's name."));
      }
      $call = mcpToolsCall($params);
      if (isset($call["error"])) {
        return(mcpErrorResponse(200, $id, $call["error"]["code"], $call["error"]["message"]));
      }
      $result = $call["result"];
      break;
    default:
      return(mcpErrorResponse(404, $id, -32601, "Method not found: ".$method));
  }
  $result = array("resultType" => "complete") + $result;
  $result["_meta"] = array("io.modelcontextprotocol/serverInfo" => mcpServerInfo());
  return(mcpResultResponse($id, $result));
}

//The response to a request of a protocol version before 2026-07-28, whose clients start with initialize and may ping
function mcpHandshakeResponse($id, $method, $params) {
  switch ($method) {
    case "initialize":
      //The version the client asks for, if the server supports it, or otherwise the newest version that starts with initialize
      $versions = array_values(array_filter(mcpVersions(), function($version) { return(!mcpStatelessVersion($version)); }));
      $requested = isset($params["protocolVersion"]) ? $params["protocolVersion"] : NULL;
      $result = array(
        "protocolVersion" => in_array($requested, $versions, TRUE) ? $requested : $versions[0],
        "capabilities" => mcpCapabilities(),
        "serverInfo" => mcpServerInfo(),
        "instructions" => mcpInstructions()
      );
      break;
    case "ping":
      $result = new stdClass();
      break;
    case "tools/list":
      $result = array("tools" => mcpTools());
      break;
    case "tools/call":
      $call = mcpToolsCall($params);
      if (isset($call["error"])) {
        return(mcpErrorResponse(200, $id, $call["error"]["code"], $call["error"]["message"]));
      }
      $result = $call["result"];
      break;
    default:
      //These clients may take an HTTP error as a failed connection, so the error is only given in the JSON-RPC response
      return(mcpErrorResponse(200, $id, -32601, "Method not found: ".$method));
  }
  return(mcpResultResponse($id, $result));
}

//A request header, or NULL if it isn't given
function mcpHeader($headers, $name) {
  return((isset($headers[$name]) && is_string($headers[$name])) ? $headers[$name] : NULL);
}

//A header value, decoded if the client encoded it as =?base64?...?=, as clients do with values that aren't plain ASCII.
//NULL if the value isn't given or can't be decoded.
function mcpHeaderValue($value) {
  if ($value === NULL || preg_match('/^=\?base64\?(.*)\?=$/sD', $value, $matches) !== 1) {
    return($value);
  }
  $decoded = base64_decode($matches[1], TRUE);
  return(($decoded === FALSE) ? NULL : $decoded);
}

function mcpEmptyResponse($status, $headers = array()) {
  return(array("status" => $status, "headers" => $headers, "body" => NULL));
}

function mcpResultResponse($id, $result) {
  return(mcpJSONResponse(200, array("jsonrpc" => "2.0", "id" => $id, "result" => $result)));
}

//A JSON-RPC error. The id is left out when it is NULL, because the request's id couldn't be read.
function mcpErrorResponse($status, $id, $code, $message, $data = NULL) {
  $response = array("jsonrpc" => "2.0");
  if ($id !== NULL) {
    $response["id"] = $id;
  }
  $response["error"] = array("code" => $code, "message" => $message);
  if ($data !== NULL) {
    $response["error"]["data"] = $data;
  }
  return(mcpJSONResponse($status, $response));
}

function mcpJSONResponse($status, $response) {
  $body = mcpJSON($response);
  if ($body === FALSE) {
    //Nothing the API holds should fail to be written as JSON, but a response that can't be is an error rather than an empty body
    $status = 500;
    $body = mcpJSON(array("jsonrpc" => "2.0", "error" => array("code" => -32603, "message" => "The response could not be written as JSON.")));
  }
  return(array(
    "status" => $status,
    "headers" => array("Content-Type: application/json; charset=utf-8"),
    "body" => $body
  ));
}

//JSON as the server writes it. Text that isn't valid UTF-8 is written with replacement characters rather than failing.
function mcpJSON($value) {
  return(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
}

//The error for a protocol version the server doesn't support, listing those it does, so the client can choose one
function mcpUnsupportedVersionResponse($id, $version) {
  return(mcpErrorResponse(400, $id, -32022, "Unsupported protocol version", array("supported" => mcpVersions(), "requested" => $version)));
}

//The server only has tools. PHP arrays with no items are written as JSON arrays, so an empty object is an stdClass.
function mcpCapabilities() {
  return(array("tools" => new stdClass()));
}

//How long clients, and caches shared between them, may keep results that are the same for everyone, such as the list of tools
function mcpCacheHints() {
  return(array("ttlMs" => MCP_CACHE_SECONDS * 1000, "cacheScope" => "public"));
}

//The server's name and version
function mcpServerInfo() {
  return(array("name" => "audioblast-api", "title" => "audioBLAST! API", "version" => MCP_SERVER_VERSION));
}

//The instructions AI applications give their models: what audioBLAST! holds, then how to use the tools and how to cite what they
//give. What audioBLAST! is comes first, as some clients cut long instructions short.
function mcpInstructions() {
  $about  = "audioBLAST!: sound recordings of animals, and the taxa, traits, references, specimens, locations and other records that ";
  $about .= "describe them, gathered from sound archives and databases.";
  $usage  = "Use list_modules to see the kinds of record, describe_module for the filters and fields of one, query_module to find ";
  $usage .= "records, suggest_values for the values a filter takes (such as the exact name of a taxon), and get_record for one record ";
  $usage .= "with the records linked to it. Each record is identified by its URI: when you use a record, give its URI, and for a ";
  $usage .= "recording also its author and license. The predicates and other IRIs of links are defined at vocab.audioblast.org, ";
  $usage .= "which has an MCP server of its own at https://vocab.audioblast.org/api/mcp.";
  return($about."\n\n".$usage);
}
