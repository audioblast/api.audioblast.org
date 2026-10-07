<?php

/*
The tools of the MCP server (core/mcp.php). Each is a way into what the API's
data modules already serve, built from the functions the API answers its own
requests with, so that a tool gives the answer the endpoint it stands for gives:

  list_modules     the data modules, as /standalone/modules/list_modules/ lists them
  describe_module  a module's filters and fields, from its definition (loadModule())
  query_module     a page of a module's records, as /data/{module}/ gives them
  suggest_values   values of a field, as /data/{module}/autocomplete/{field}/ gives them
  get_record       a record and its links, as its own URI gives them as JSON-LD

Only data modules are reached: the analysis modules' tables hold a row for every
few seconds of every recording, and the standalone modules are answered by
callbacks of their own. None of the tools change anything.

Corpora have no tool of their own: they are found with these tools, as the
server's instructions say (see mcpCorporaInstructions()).
*/

//How many records query_module gives at once unless asked for another number, and the most it gives
define("MCP_PAGE_SIZE", 20);
define("MCP_PAGE_MAX", 100);

//How many values suggest_values gives unless asked for another number. The most is MCP_PAGE_MAX.
define("MCP_SUGGEST_LIMIT", 20);

//The most bytes of linked data get_record gives. A taxon with thousands of recordings has thousands of links, which are better
//read a page at a time from the links module than all at once, and a recording can have thousands of regions of interest,
//which are better read from annomate.
define("MCP_LINKED_DATA_LIMIT", 200000);

//The term a references record is linked to by dcterms:type to say that it is a corpus. The address is a placeholder until the term
//is defined at vocab.audioblast.org, so the code names it here and nowhere else.
define("MCP_CORPUS_TYPE", "https://vocab.audioblast.org/Corpus");

//The tools the server has, always in this order. None of them change anything.
function mcpTools() {
  $readOnly = array("readOnlyHint" => TRUE, "idempotentHint" => TRUE, "openWorldHint" => FALSE);
  $modules = loadModules("data");
  $module = array("type" => "string", "enum" => array_keys($modules),
    "description" => "The name of a data module, as list_modules gives it");
  $value = array("type" => array("string", "number"));
  $filters = array("type" => "object",
    "description" => "The module's filters, as describe_module gives them, each with its value. Every filter given must match. "
      ."A filter that takes several values (describe_module marks it multiple) is given them as a list, or joined with commas, and "
      ."matches any of them. Any other filter takes a comma as part of its value, so country GB,FR finds nothing.",
    "additionalProperties" => array("anyOf" => array($value, array("type" => "array", "items" => $value))));
  $text = array("type" => "string");
  $nullableText = array("type" => array("string", "null"));
  $object = array("type" => "object");
  return(array(
    array(
      "name" => "list_modules",
      "title" => "List modules",
      "description" => "List the kinds of record audioBLAST! holds, which are its data modules: recordings, taxa, references, specimens, "
        ."traits, links between records, and others. Each module's name is what the other tools take.",
      "inputSchema" => array("type" => "object", "additionalProperties" => FALSE),
      "outputSchema" => mcpObjectSchema(array("modules" => array("type" => "array", "items" => mcpObjectSchema(array(
        "name" => $text,
        "title" => $text,
        "description" => $text,
        "record_uri_template" => mcpNullable(array("type" => "string",
          "description" => "How the module's records are identified, or null if they have no URIs of their own"))
      ))))),
      "annotations" => $readOnly
    ),
    array(
      "name" => "describe_module",
      "title" => "Describe a module",
      "description" => "Describe a data module: what its records are, the filters query_module takes for it and how each matches "
        ."(exactly, by containing the value, by words, or as a range), which filters take several values, which fields "
        ."suggest_values gives the values of, the fields each record has, and the URI its records are identified by.",
      "inputSchema" => array("type" => "object", "properties" => array("module" => $module), "required" => array("module")),
      "outputSchema" => mcpObjectSchema(array(
        "name" => $text,
        "title" => $text,
        "description" => $text,
        "source_notes" => $nullableText,
        "see_also" => array("type" => "array", "items" => $text),
        "record_uri_template" => $nullableText,
        "filters" => array("type" => "array", "items" => mcpObjectSchema(array(
          "name" => $text,
          "description" => $text,
          "type" => $text,
          "match" => array("type" => "string", "enum" => array_keys(mcpMatches())),
          "multiple" => array("type" => "boolean", "description" => "Whether the filter takes several values"),
          "allowed" => mcpNullable(array("type" => "array", "items" => $text)),
          "suggest" => array("type" => "boolean", "description" => "Whether suggest_values gives the field's values")
        ))),
        "matches" => array("type" => "object", "description" => "What each way of matching used by the filters means",
          "additionalProperties" => $text),
        "fields" => array("type" => "array", "items" => $text, "description" => "The fields of each record, filters or not")
      )),
      "annotations" => $readOnly
    ),
    array(
      "name" => "query_module",
      "title" => "Query a module",
      "description" => "Find the records of a data module that match filters, a page at a time. The filters are the module's own "
        ."(see describe_module), and a filter the module doesn't have is refused with a list of those it does. Records come with "
        ."their record_uri, where the module's records have one. more says whether there is another page; total is given when "
        ."count is true.",
      "inputSchema" => array("type" => "object", "properties" => array(
        "module" => $module,
        "filters" => $filters,
        "page" => array("type" => "integer", "minimum" => 1, "default" => 1),
        "page_size" => array("type" => "integer", "minimum" => 1, "maximum" => MCP_PAGE_MAX, "default" => MCP_PAGE_SIZE),
        "count" => array("type" => "boolean", "default" => FALSE,
          "description" => "Whether to count every matching record as well, which takes longer")
      ), "required" => array("module")),
      "outputSchema" => mcpObjectSchema(array(
        "module" => $text,
        "page" => array("type" => "integer"),
        "page_size" => array("type" => "integer"),
        "more" => array("type" => "boolean", "description" => "Whether there are more records on the next page"),
        "total" => mcpNullable(array("type" => "integer", "description" => "How many records match, or null if count wasn't asked for")),
        "rows" => array("type" => "array", "items" => $object)
      )),
      "annotations" => $readOnly
    ),
    array(
      "name" => "suggest_values",
      "title" => "Suggest values",
      "description" => "Give the values a field of a data module holds that start with, or contain, some text: the exact names of "
        ."taxa starting with \"Gryllus\", say, or the countries recordings were made in, or the licenses they are under. Use it to "
        ."find the value an exact filter needs. Values come in no particular order, with or without text. On a field with a "
        ."full-text index, such as the names of taxa and recordings, contains matches as the words match does. Only fields "
        ."describe_module marks suggest have values to suggest, and filters narrow the records the values are taken from.",
      "inputSchema" => array("type" => "object", "properties" => array(
        "module" => $module,
        "field" => $text,
        "text" => $text,
        "match" => array("type" => "string", "enum" => array("starts", "contains"), "default" => "starts"),
        "filters" => $filters,
        "limit" => array("type" => "integer", "minimum" => 1, "maximum" => MCP_PAGE_MAX, "default" => MCP_SUGGEST_LIMIT)
      ), "required" => array("module", "field")),
      "outputSchema" => mcpObjectSchema(array(
        "module" => $text,
        "field" => $text,
        "values" => array("type" => "array", "items" => $text),
        "more" => array("type" => "boolean", "description" => "Whether there are more values than the limit")
      )),
      "annotations" => $readOnly
    ),
    array(
      "name" => "get_record",
      "title" => "Get a record",
      "description" => "Get one record by its URI (https://api.audioblast.org/{kind}/{source}/{id}, as query_module gives it), or by "
        ."its module, source and id, as a link gives them. The record comes with its linked data, as JSON-LD: the links to and from "
        ."it (what a recording is of, what a reference is about, which specimen a recording was made of), for a recording the "
        ."regions of interest annotations mark on it, as ac:hasROI, and for a taxon its whole classification. A record with too "
        ."many links or regions to give at once, such as a corpus with thousands of regions, comes without its linked data, and "
        ."with a note on finding them a page at a time with query_module. A record's details, such as "
        ."the tape a recording was made on, are never included: find them with query_module on details, with the record's source "
        ."as record_source, its module as type, and its id.",
      "inputSchema" => array("type" => "object", "properties" => array(
        "uri" => array("type" => "string", "description" => "The record's URI. Give this, or the module, source and id."),
        "module" => $module,
        "source" => $text,
        "id" => $text
      )),
      "outputSchema" => mcpObjectSchema(array(
        "uri" => $text,
        "module" => $text,
        "record" => $object,
        "linked_data" => mcpNullable($object),
        "note" => $nullableText
      )),
      "annotations" => $readOnly
    )
  ));
}

//A JSON Schema for an object that has all of these properties
function mcpObjectSchema($properties) {
  return(array("type" => "object", "properties" => $properties, "required" => array_keys($properties)));
}

//A JSON Schema that also allows null
function mcpNullable($schema) {
  $schema["type"] = array_merge((array)$schema["type"], array("null"));
  return($schema);
}

//The ways a filter matches (see mcpMatch()), and what each means
function mcpMatches() {
  return(array(
    "exact" => "The field is the value, letter case aside.",
    "contains" => "The field contains the value.",
    "words" => "Full-text search: records with any one of the words given, the last of them as the start of a word, so Gryllus "
      ."campestris also finds every other Gryllus, and every other campestris, such as Anthus campestris. Check the records, or "
      ."filter a module that matches names exactly.",
    "starts" => "The field starts with the value.",
    "range" => "A number: min:max (both ends included), >=x, <=x, >x, <x, or a number alone."
  ));
}

//How a filter matches (see mcpMatches())
function mcpMatch($info) {
  switch ($info["op"]) {
    case "=":
      return("exact");
    case "contains":
      return(empty($info["fulltext"]) ? "contains" : "words");
  }
  return($info["op"]);
}

//The text of a module's description or notes, which are written for the API's HTML documentation
function mcpPlainText($html) {
  return(trim(preg_replace('/\s+/u', " ", html_entity_decode(strip_tags((string)$html), ENT_QUOTES, "UTF-8"))));
}

/*
What to know to read the records rightly, for the server's instructions (see
mcpInstructions()): the things that would otherwise give a model a wrong answer
whichever module it queries. What is true of one module's records is in its own
source_notes, which describe_module gives.
*/
function mcpDataInstructions() {
  $text  = "Names are written as each source writes them, and an exact filter matches only that form: bio.acousti.ca writes a ";
  $text .= "subgenus without brackets, as Gryllus Gryllus campestris, so a filter for Gryllus campestris misses its records. Before ";
  $text .= "saying that audioBLAST! holds nothing, look for other forms of a name with suggest_values, giving its last word as the ";
  $text .= "text and contains as the match. Each source that names a taxon has a taxa record of its own, and a link points at one ";
  $text .= "source's record. Taxa are matched to those of the Catalogue of Life, held as the source CoL, by ";
  $text .= "http://www.w3.org/2004/02/skos/core#exactMatch links, so the links with that predicate to a CoL taxon give each ";
  $text .= "source's record of it; a name that wasn't matched has no such link. A recording is linked to the taxa it is of by ";
  $text .= "http://purl.obolibrary.org/obo/IAO_0000136 (is about), and where the link's qualifier is ";
  $text .= "https://vocab.audioblast.org/cv/recordingContent#NonFocalTaxa, the taxon is only heard in the background. What else ";
  $text .= "to know of a module's records is in the source_notes that describe_module gives.";
  return($text);
}

/*
How to find corpora and their regions, for the server's instructions (see
mcpInstructions()). Each step is a query of the filters the links, annomate and
details modules have, and tests/mcp.php checks that they still have them, with
the matching the steps need.
*/
function mcpCorporaInstructions() {
  $text  = "A corpus, such as a set of regions marked in recordings to train classifiers, is a references record linked to ";
  $text .= MCP_CORPUS_TYPE." by http://purl.org/dc/terms/type: query_module on links with that predicate and object_id finds ";
  $text .= "them, and each link's subject_source and subject_id name one. A corpus's regions of interest are annomate records linked ";
  $text .= "to it by http://purl.org/dc/terms/isPartOf. There can be thousands, more than get_record gives, so page through them ";
  $text .= "with query_module on links, with that predicate, object_type references, and the corpus's source and id as ";
  $text .= "object_source and object_id. Each link's subject_id is a region's annotation_id, and its qualifier is the split the ";
  $text .= "region is in, such as Training or Validation, which the qualifier filter chooses. To get the regions, give annomate up ";
  $text .= "to ".MAX_FILTER_VALUES." of those ids at once as annotation_id; annomate's source filter gives every region a source ";
  $text .= "holds, of whichever corpus. A region's recording is its recording_source and source_id, and its frequency bounds, in ";
  $text .= "Hz, are its freq_low and freq_high. Its other values, such as svl_label, are details: type annomate, record_source the ";
  $text .= "region's source, id its annotation_id. A later version of a corpus links to the one it came from by ";
  $text .= "http://purl.org/dc/terms/source, and has its own regions. Every annotation of a recording, whichever source gave it, is ";
  $text .= "found with annomate's recording_source and source_id.";
  return($text);
}

//The result of a tools/call request as array("result" => the result), or array("error" => array("code" => ..., "message" => ...))
//if the request doesn't call a tool the server has
function mcpToolsCall($params) {
  if (!isset($params["name"]) || !is_string($params["name"])) {
    return(array("error" => array("code" => -32602, "message" => "Invalid params: tools/call needs the name of a tool.")));
  }
  if (isset($params["arguments"]) && !is_array($params["arguments"])) {
    return(array("error" => array("code" => -32602, "message" => "Invalid params: the arguments must be an object.")));
  }
  $result = mcpCallTool($params["name"], isset($params["arguments"]) ? $params["arguments"] : array());
  if ($result === NULL) {
    return(array("error" => array("code" => -32602, "message" => "Unknown tool: ".$params["name"])));
  }
  return(array("result" => $result));
}

//The result of calling a tool with its arguments, or NULL if there is no tool with that name. Arguments that can't be used, and
//records that aren't found, give a result that is an error, which says what to change. So does a query that fails: from PHP
//8.1, mysqli throws where it used to return FALSE, and a tool's error is more use to a client than a broken response.
function mcpCallTool($name, $arguments) {
  global $db;
  try {
    switch ($name) {
      case "list_modules":
        return(mcpListModules());
      case "describe_module":
        return(mcpDescribeModule($arguments));
      case "query_module":
        return(mcpQueryModule($db, $arguments));
      case "suggest_values":
        return(mcpSuggestValues($db, $arguments));
      case "get_record":
        return(mcpGetRecord($db, $arguments));
    }
  } catch (mysqli_sql_exception $e) {
    error_log("MCP ".$name.": ".$e->getMessage());
    return(mcpQueryFailed());
  } catch (Throwable $e) {
    error_log("MCP ".$name.": ".$e);
    return(mcpToolError("The tool failed on the server."));
  }
  return(NULL);
}

//A tool's result: data, which matches the tool's output schema, given as JSON text too for clients that only read text
function mcpToolResult($data) {
  return(array(
    "content" => array(array("type" => "text", "text" => mcpJSON($data))),
    "structuredContent" => $data,
    "isError" => FALSE
  ));
}

function mcpToolError($text) {
  return(array("content" => array(array("type" => "text", "text" => $text)), "isError" => TRUE));
}

function mcpQueryFailed() {
  return(mcpToolError("The query failed on the database."));
}

//A whole number given as an argument, or NULL if the argument isn't one. JSON numbers such as 10.0 are whole numbers too.
function mcpWholeNumber($value) {
  if (is_int($value)) {
    return($value);
  }
  if (is_float($value) && floor($value) == $value && abs($value) < PHP_INT_MAX) {
    return((int)$value);
  }
  return(NULL);
}

//A whole number argument from $min to $max, $default if it isn't given, or NULL if it isn't one
function mcpNumberArgument($arguments, $name, $min, $max, $default) {
  if (!isset($arguments[$name])) {return($default);}
  $number = mcpWholeNumber($arguments[$name]);
  if ($number === NULL || $number < $min || ($max !== NULL && $number > $max)) {return(NULL);}
  return($number);
}

//The data module the module argument names, or NULL if it names none. Only a module's own name is ever looked for, so the text a
//client gives never reaches the path a module is loaded from.
function mcpModuleArgument($arguments) {
  $modules = loadModules("data");
  $name = isset($arguments["module"]) ? $arguments["module"] : NULL;
  return((is_string($name) && isset($modules[$name])) ? $modules[$name] : NULL);
}

function mcpModuleProblem() {
  return(mcpToolError("Give the name of a data module as the module: one of ".implode(", ", array_keys(loadModules("data")))
    .". list_modules says what each holds."));
}

//The name of a data module, as the tools take it
function mcpModuleName($module) {
  foreach (loadModules("data") as $name => $info) {
    if ($info["mname"] === $module["mname"]) {return($name);}
  }
  return($module["mname"]);
}

//How a module's records are identified, or NULL if they have no URIs of their own (see rdfRecordURI())
function mcpRecordURITemplate($module) {
  if (!isset($module["rdf"]["path"])) {return(NULL);}
  return("https://api.audioblast.org/".$module["rdf"]["path"]."/{source}/{".($module["rdf"]["id"] ?? "id")."}");
}

//A record with its URI first, where the module's records have one
function mcpWithURI($module, $record) {
  if (!isset($module["rdf"]["path"])) {return($record);}
  $id = (string)$record[$module["rdf"]["id"] ?? "id"];
  return(array("record_uri" => rdfRecordURI($module, (string)$record["source"], $id)) + $record);
}

//The rows a query gives, or FALSE if it failed
function mcpRows($db, $sql) {
  $result = $db->query($sql);
  if (!$result) {return(FALSE);}
  $rows = array();
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
  }
  $result->close();
  return($rows);
}

/*
The filters a tool was given, as the inputs moduleParams() takes, or what is
wrong with them. They are checked as the API checks the parameters of a request
(see checkParams()), against the module's filters alone: the tools give records
as JSON with the module's own field names, so output and format, which would
choose otherwise, are not among them.
*/
function mcpFilterArguments($module, $filters) {
  if (!is_array($filters)) {
    return("Give the filters as an object, from the name of each filter to its value.");
  }
  $filterable = $module;
  foreach (listOutputColumns() as $name) {
    unset($filterable["params"][$name]);
  }
  $inputs = array();
  foreach ($filters as $name => $value) {
    $name = (string)$name;
    if ($name === "page" || $name === "page_size") {
      return("Give `".$name."` as an argument of its own rather than as a filter.");
    }
    //The API's own control parameters are not a tool's to give
    if (in_array($name, listControlParams())) {
      return(paramProblem($filterable, $name, "is not recognised"));
    }
    if (is_array($value)) {
      if (array_values($value) !== $value) {
        return("Give the values of `".$name."` as a list.");
      }
      $inputs[$name] = array();
      foreach ($value as $one) {
        $one = mcpFilterValue($one);
        if ($one === NULL) {return("Give the values of `".$name."` as text or numbers.");}
        $inputs[$name][] = $one;
      }
    } else {
      $one = mcpFilterValue($value);
      if ($one === NULL) {return("Give the value of `".$name."` as text or a number, or several values as a list.");}
      $inputs[$name] = $one;
    }
  }
  $problem = checkParams($filterable, $inputs);
  return(($problem === NULL) ? $inputs : $problem);
}

//A filter's value as the API reads one, which is text, or NULL if it can't be one
function mcpFilterValue($value) {
  if (is_string($value)) {return($value);}
  if (is_int($value) || is_float($value)) {return((string)$value);}
  return(NULL);
}

function mcpListModules() {
  $modules = array();
  foreach (loadModules("data") as $name => $module) {
    $modules[] = array(
      "name" => $name,
      "title" => $module["hname"],
      "description" => mcpPlainText($module["desc"] ?? ""),
      "record_uri_template" => mcpRecordURITemplate($module)
    );
  }
  return(mcpToolResult(array("modules" => $modules)));
}

function mcpDescribeModule($arguments) {
  $module = mcpModuleArgument($arguments);
  if ($module === NULL) {return(mcpModuleProblem());}
  $filters = array();
  $fields = array();
  $matches = array();
  foreach ($module["params"] as $name => $info) {
    if (in_array($name, listOutputColumns())) {continue;}
    $fields[] = $name;
    if (!in_array($name, listFilterParams($module))) {continue;}
    $match = mcpMatch($info);
    $matches[$match] = mcpMatches()[$match] ?? "";
    $filters[] = array(
      "name" => $name,
      "description" => mcpPlainText($info["desc"] ?? ""),
      "type" => $info["type"] ?? "string",
      "match" => $match,
      "multiple" => paramTakesMany($info),
      "allowed" => isset($info["allowed"]) ? array_values(array_map("strval", $info["allowed"])) : NULL,
      "suggest" => !empty($info["autocomplete"])
    );
  }
  $seeAlso = array();
  foreach (($module["see_also"] ?? array()) as $line) {
    $seeAlso[] = mcpPlainText($line);
  }
  return(mcpToolResult(array(
    "name" => mcpModuleName($module),
    "title" => $module["hname"],
    "description" => mcpPlainText($module["desc"] ?? ""),
    "source_notes" => isset($module["source_notes"]) ? mcpPlainText($module["source_notes"]) : NULL,
    "see_also" => $seeAlso,
    "record_uri_template" => mcpRecordURITemplate($module),
    "filters" => $filters,
    //An object, even where a module has no filters
    "matches" => $matches ? $matches : new stdClass(),
    "fields" => $fields
  )));
}

//A page of a module's records, as /data/{module}/ gives them. One record more than the page holds is asked for, to tell whether
//there is another page without counting every record that matches.
function mcpQueryModule($db, $arguments) {
  $module = mcpModuleArgument($arguments);
  if ($module === NULL) {return(mcpModuleProblem());}
  $inputs = mcpFilterArguments($module, isset($arguments["filters"]) ? $arguments["filters"] : array());
  if (is_string($inputs)) {return(mcpToolError($inputs));}
  $page = mcpNumberArgument($arguments, "page", 1, NULL, 1);
  if ($page === NULL) {return(mcpToolError("Give the page as a whole number from 1."));}
  $size = mcpNumberArgument($arguments, "page_size", 1, MCP_PAGE_MAX, MCP_PAGE_SIZE);
  if ($size === NULL) {return(mcpToolError("Give the page_size as a whole number from 1 to ".MCP_PAGE_MAX."."));}
  $count = isset($arguments["count"]) ? $arguments["count"] : FALSE;
  if (!is_bool($count)) {return(mcpToolError("Give count as true or false."));}

  $where = WHEREclause(generateParams($module, moduleParams($db, $module, $inputs)));
  $rows = mcpRows($db, SELECTclause($module, NULL, "table", "internal").$where." LIMIT ".($size * ($page - 1)).", ".($size + 1).";");
  if ($rows === FALSE) {return(mcpQueryFailed());}
  $records = array();
  foreach (array_slice($rows, 0, $size) as $row) {
    $records[] = mcpWithURI($module, $row);
  }
  $total = NULL;
  if ($count) {
    $counted = mcpRows($db, SELECTcount($module).$where.";");
    if ($counted === FALSE || !isset($counted[0]["total"])) {return(mcpQueryFailed());}
    $total = (int)$counted[0]["total"];
  }
  return(mcpToolResult(array(
    "module" => mcpModuleName($module),
    "page" => $page,
    "page_size" => $size,
    "more" => count($rows) > $size,
    "total" => $total,
    "rows" => $records
  )));
}

//Values of a field, as /data/{module}/autocomplete/{field}/ gives them
function mcpSuggestValues($db, $arguments) {
  $module = mcpModuleArgument($arguments);
  if ($module === NULL) {return(mcpModuleProblem());}
  $suggestable = array();
  foreach ($module["params"] as $name => $info) {
    if (!empty($info["autocomplete"])) {$suggestable[] = $name;}
  }
  $field = isset($arguments["field"]) ? $arguments["field"] : NULL;
  if (!is_string($field) || !in_array($field, $suggestable, TRUE)) {
    if (!$suggestable) {return(mcpToolError("No field of ".mcpModuleName($module)." has values to suggest."));}
    return(mcpToolError("Give as the field one that has values to suggest. For ".mcpModuleName($module)
      .", these are: ".implode(", ", $suggestable)."."));
  }
  $text = isset($arguments["text"]) ? $arguments["text"] : "";
  if (!is_string($text)) {return(mcpToolError("Give the text as text."));}
  $match = isset($arguments["match"]) ? $arguments["match"] : "starts";
  if (!in_array($match, array("starts", "contains"), TRUE)) {return(mcpToolError("Give the match as starts or contains."));}
  $limit = mcpNumberArgument($arguments, "limit", 1, MCP_PAGE_MAX, MCP_SUGGEST_LIMIT);
  if ($limit === NULL) {return(mcpToolError("Give the limit as a whole number from 1 to ".MCP_PAGE_MAX."."));}
  $inputs = mcpFilterArguments($module, isset($arguments["filters"]) ? $arguments["filters"] : array());
  if (is_string($inputs)) {return(mcpToolError($inputs));}

  $where = generateParams($module, moduleParams($db, $module, $inputs));
  $where[] = autocompleteFilter($module, $field, ($text === "") ? "none" : $match, $db->real_escape_string($text));
  $rows = mcpRows($db, SELECTclause($module, $field, "autocomplete").WHEREclause($where)." LIMIT 0, ".($limit + 1).";");
  if ($rows === FALSE) {return(mcpQueryFailed());}
  $values = array();
  foreach (array_slice($rows, 0, $limit) as $row) {
    if (($row[$field] ?? "") !== "") {$values[] = (string)$row[$field];}
  }
  return(mcpToolResult(array(
    "module" => mcpModuleName($module),
    "field" => $field,
    "values" => $values,
    "more" => count($rows) > $limit
  )));
}

//A record and its linked data, as the record's own URI gives them (see recordAPI())
function mcpGetRecord($db, $arguments) {
  if (isset($arguments["uri"])) {
    $uri = $arguments["uri"];
    $at = is_string($uri) ? recordAt($uri) : NULL;
    $host = is_string($uri) ? parse_url($uri, PHP_URL_HOST) : NULL;
    if ($at === NULL || ($host !== NULL && $host !== "api.audioblast.org")) {
      return(mcpToolError("The uri isn't the URI of an audioBLAST! record. Records are identified as "
        ."https://api.audioblast.org/{kind}/{source}/{id}, as query_module gives them in record_uri."));
    }
    $module = $at["module"];
    $source = $at["source"];
    $id = $at["id"];
  } else {
    $module = mcpModuleArgument($arguments);
    if ($module === NULL) {
      return(mcpToolError("Give the record's uri, or its module, source and id."));
    }
    if (!isset($module["rdf"]["path"])) {
      return(mcpToolError("Records of ".mcpModuleName($module)." have no URIs of their own, so there is no record to get. "
        ."Find them with query_module."));
    }
    $source = isset($arguments["source"]) ? mcpFilterValue($arguments["source"]) : NULL;
    $id = isset($arguments["id"]) ? mcpFilterValue($arguments["id"]) : NULL;
    if ($source === NULL || $source === "" || $id === NULL || $id === "") {
      return(mcpToolError("Give the source holding the record and its id there, as well as the module."));
    }
  }

  $record = recordByID($db, $module, $source, $id);
  if ($record === FALSE) {return(mcpQueryFailed());}
  if ($record === NULL) {
    return(mcpToolError("No record of ".mcpModuleName($module)." has the source `".$source."` and the id `".$id."`."));
  }
  //The database compares text regardless of case, but each record has one URI, which is the one given
  $uri = rdfRecordURI($module, $record["source"], $record[$module["rdf"]["id"] ?? "id"]);
  $nodes = rdfResponseNodes($db, $module, array($record), TRUE);
  if ($nodes === FALSE) {return(mcpQueryFailed());}
  $linked = array("@context" => rdfContext(), "@graph" => $nodes);
  $note = NULL;
  if (strlen(mcpJSON($linked)) > MCP_LINKED_DATA_LIMIT) {
    $linked = NULL;
    $note = "The record has too many links to give here. Find them a page at a time with query_module on links, filtered by "
      ."subject_type, subject_source and subject_id for the links from it, or by object_type, object_source and object_id for "
      ."the links to it, with the type being ".mcpModuleName($module).".";
    //A recording's regions of interest are annotations rather than links (see
    //recordings_rdf_rois()), and can be most of what made it too much
    if (mcpHasRegions($nodes, $uri)) {
      $note .= " Its regions of interest are annotations, not links: find them with query_module on annomate, filtered by "
        ."recording_source `".$record["source"]."` and source_id `".$record[$module["rdf"]["id"] ?? "id"]."`.";
    }
  }
  return(mcpToolResult(array(
    "uri" => $uri,
    "module" => mcpModuleName($module),
    "record" => $record,
    "linked_data" => $linked,
    "note" => $note
  )));
}

//Whether the linked data of the record at a URI gives it regions of interest
function mcpHasRegions($nodes, $uri) {
  foreach ($nodes as $node) {
    if (($node["@id"] ?? NULL) === $uri && isset($node["ac:hasROI"])) {return(TRUE);}
  }
  return(FALSE);
}
