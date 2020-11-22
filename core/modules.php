<?php

function listModuleTypes() {
  return(array(
    "source",
    "data",
    "analysis",
    "standalone"
  ));
}

function parseType($type) {
  if ($type == "range") { return("string");} else {return($type);}
}

function moduleAPI($db) {
  $execute_query = TRUE;
  $parts = explode("/", $_SERVER['REQUEST_URI']);
  $module = loadModule($parts[2]);
  $params = array();
  $notes = array();

  if (isset($parts[3]) && substr($parts[3],0, 1) != "?") {
    $ep = $parts[3];
    $execute_query = FALSE;
    $module["params"] = $module["endpoints"][$ep]["params"];
  }


  //Validate parameters
  foreach ($module["params"] as $pname => $pinfo) {
    if (isset($_GET[$pname])) {
      $params[$pname] = mysqli_real_escape_string($db, $_GET[$pname]);
    } else {
      if (isset($pinfo["default"])) {
        $params[$pname] = $pinfo["default"];
      }
    }
  }
  if (isset($parts[3]) && $parts[3] == "autocomplete") {
    $field= $parts[4];
    if (!isset($module["params"][$field])) {
      $execute_query = FALSE;
      $notes[] = "Autocomplete field does not exist.";
    }
    if (!isset($module["params"][$field]["autocomplete"]) || $module["params"][$field]["autocomplete"] == FALSE) {
      $execute_query = FALSE;
      $notes[] = "Autocomplete not allowed on field. Query will not execute.";
    }
    if (isset($_GET["s"])) {
      $op = "starts";
      $value = $_GET["s"];
    } else if (isset($_GET["c"])) {
      $op = "contains";
      $value = $_GET["c"];
    } else {
      $notes[] = "Autocomplete requested but no c or s value provided.";
    }

    $select = SELECTclause($module, $field, "autocomplete");
    $where = generateParams($module, $params);
    $where[] = array(
      "column" => $module["params"][$field]["column"],
      "op" => $op,
      "value" => $value,
      "type" => "string"
    );
  } else if (substr($parts[3], 0, 1) == "?") {
    $select = SELECTclause($module);
    $where = generateParams($module, $params);
  } else {
    $ret["data"] = call_user_func($module["endpoints"][$ep]["callback"], $params);
  }

  if ($execute_query) {
    $sql = $select.WHEREclause($where);
    $result = $db->query($sql);
    if ($result) {
      while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $ret["data"][] = $row;
      }
      $result->close();
    } else {
      $notes[] = "Query failed on database.";
    }
  }

  $ret["params"] = $params;
  $ret["notes"] = $notes;

  switch($params["output"]) {
    case "JSON";
      print(json_encode($ret));
      break;
  }
}

function loadModules_analysisDefaultParams() {
  $ret = array(
    "source" => array(
      "desc" => "Source database",
      "type" => "string",
      "default" => ""
    ),
    "id" => array(
      "desc" => "Unique id within source",
      "type" => "string",
      "default" => ""
    )
  );
  return($ret);
}

/*
Load single module info
*/
function loadModule($mod) {
    $modfile = "modules/".$mod."/module.php";
    if (file_exists($modfile)) {
      include_once($modfile);
      $module = call_user_func($mod."_info");
      if ($module["category"] == "analysis") {
        $module["params"] = array_merge(
          loadModules_analysisDefaultParams(),
          $module["params"]
        );
      }
    if (isset($module["params"]["output"])) {
      $module["params"]["output"]["column"] = "";
      $module["params"]["output"]["op"] = "";
    }
    } else {
      return("WTF");
    }
  return($module);
}

/*
Load all module info
*/
function loadModules() {
  $modules = array();
  foreach(glob("modules/"."*" , GLOB_ONLYDIR) as $mod_dir) {
      $mod_name = substr($mod_dir, 8);
      $modules[$mod_name] = loadModule($mod_name);
  }
  return($modules);
}

function codeLink($info) {
  $ret  = " ";
  $ret .= $info["code"]["language"];
  $ret .= " package ";

  $href="";
  if ($info["code"]["language"] == "R" && $info["code"]["source"] == "CRAN") {
    $href = "https://cran.r-project.org/package=".$info["code"]["name"];
  }

  $ret .= "<a href='".$href."'>".$info["code"]["name"]."</a>";
  return($ret);
}

function refsLink($info) {
  $ret = "<ul>";
  foreach ($info["references"] as $ref) {
    $ret .= "<li>";
    $ret .= $ref["authors"]." (".$ref["year"].") ";
    $ret .= "<a href='https://doi.org/".$ref["doi"]."'>".$ref["title"]."</a>";
    $ret .= "</li>";
  }
  $ret .= "</ul>";
  return($ret);
}

function printSources($modules, $type) {
  $ret  = "<h4>Data Sources</h4>";
  $ret .= "<ul>";
  foreach ($modules as $name => $info) {
    if ($info["category"] == "source") {
      foreach ($info["sources"] as $source) {
        if ($source["type"] == $type) {
          $ret .= "<li><a href='".$modules[$name]["url"]."'>".$modules[$name]["hname"]."</a></li>";
        }
      }
    }
  }
  $ret .= "</ul>";
  return($ret);
}

/*
Print all module info
*/
function modulesHTML($modules) {
  $data = "";
  $analysis = "";
  $standalone = "";
  $sources = array();
  $links = array();
  foreach ($modules as $name => $info) {
    if ($info["category"] == "source") {continue;}
    $links[$info["category"]][] = array(
      "text" => $info["mname"],
      "href" => "#".$info["mname"]
    );
    $out = "";
    $out .= "<h3 id='".$info["mname"]."'>".$info["hname"]."</h3>";
    $out .= "<p>".$info["desc"]."</p>";

    if ($info["category"] == "data") {
      $out .= printSources($modules, $name);
    }

    if (isset($info["code"])) {
      $out .= "<h4>Analysis details</h4>";
      $out .= $info["code"]["function"]." function of ".codeLink($info).".";
    }

    if (isset($info["references"])) {
      $out .= "<h4>References</h4>";
      $out .= refsLink($info);
    }

    $out .= "<h4>Endpoints</h4>";
    $out .= "<ul>";
    if (isset($info["params"])) {
      $out .= "<li><strong>https://api.audioblast.org/".$info["category"]."/".$name."/</strong>";
      $out .= printParams($info["params"])."</li>";
    }
    if (isset($info["endpoints"])) {
      foreach ($info["endpoints"] as $path => $einfo) {
        $out .= "<li>";
        $out .= "<strong>https://api.audioblast.org/".$info["category"]."/".$name."/".$path."/</strong>";
        $out .= "<br>";
        $out .= $einfo["desc"];
        $out .= printParams($einfo["params"]);
        $out .= "</li>";
      }
    }
    if (isset($info["params"])) {
      foreach ($info["params"] as $pname => $pinfo) {
        if (isset($pinfo["autocomplete"]) && $pinfo["autocomplete"]) {
          $out .= "<li>https://api.audioblast.org/".$info["category"]."/".$name."/autocomplete/".$pname."/</li>";
        }
      }
    }
    $out .= "</ul>";

    switch($info["category"]) {
      case "data":
        $data .= $out;
        break;
      case "analysis":
        $analysis .= $out;
        break;
      case "standalone":
        $standalone .= $out;
        break;
    }
  }

  $data = "<h2>Data</h2>".modulesHTML_printlinks($links, "data").$data;
  $analysis = "<h2>Analysis</h2>".modulesHTML_printlinks($links, "analysis").$analysis;
  $standalone = "<h2>Standalone</h2>".modulesHTML_printlinks($links, "standalone").$standalone;
  return($data.$analysis.$standalone);
}

function printParams($params) {
    $out  = "<table class='stripe'>";
    $out .= "<tr><th>Name</th><th>Can filter?</th><th>Can autocomplete?</th><th>Description</th><th>Type</th><th>Default filter value</th><th>Allowed values</th></tr>";

    foreach ($params as $pname => $pinfo) {
      $out .= "<tr>";
      $out .= "<td>".$pname."</td>";
      if (!isset($pinfo["op"]) || $pinfo["op"] == "none") {
        $out.= "<td></td>";
      } else {
        $out .= "<td class='tdcent'>Y</td>";
      }
      if (isset($pinfo["autocomplete"]) && $pinfo["autocomplete"] == TRUE) {
        $out .= "<td class='tdcent'>Y</td>";
      } else {
        $out .= "<td></td>";
      }
      $out .= "<td>".$pinfo["desc"]."</td>";
      $out .= "<td class='tdcent'>".$pinfo["type"]."</td>";
      if (isset($pinfo["default"])) {
        $out .= "<td class='tdcent'>".$pinfo["default"]."</td>";
      } else {
        $out .= "<td></td>";
      }
      $out .= "<td class='tdcent'>".modulesHTML_allowedvalues($pinfo)."</td>";
      $out .= "</tr>";
    }
    $out .= "</table>";
  return($out);
}

function modulesHTML_allowedvalues($pinfo) {
  $ret = "";
  if (isset($pinfo["allowed"])) {
    foreach ($pinfo["allowed"] as $value) {
      $ret .= $value."<br>";
    }
  }
  return($ret);
}

function modulesHTML_printlinks($links, $section) {
  $ret = "<ul class='ulhoriz'>";
  foreach ($links[$section] as $data) {
    $ret .= "<li><a href='".$data["href"]."'>".$data["text"]."</a></li>";
  }
  $ret .= "</ul>";
  return($ret);
}
