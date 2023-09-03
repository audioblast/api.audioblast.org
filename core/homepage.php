<?php

/*
Check if currently requested URI is treated as the homepage
*/
function isHomepage() {
  $homes = array(
    "/",
    "/index.php"
  );
  return(in_array($_SERVER['REQUEST_URI'], $homes));
}

/*
Formatting function for generating links to code
*/
function codeLink($info) {
  $ret  = $info["code"]["language"];
  $ret .= " package ";

  if ($info["code"]["language"] == "R" && $info["code"]["source"] == "CRAN") {
    $href = "https://cran.r-project.org/package=".$info["code"]["name"];
  }

  $ret .= "<a href='".$href."'>".$info["code"]["name"]."</a>";
  return($ret);
}

/*
Formatting function for generating links to references
*/
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

/*
Helper function to list sources for data
*/
function printSources($modules, $type) {
  $ret  = "<h4>Data Sources</h4>";
  if (isset($modules[$type]["source_notes"])) {
    $ret .= "<p>".$modules[$type]["source_notes"]."</p>";
  }
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
    $out = "<div class='module'>";
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
    if (isset($info["vocab_url"])) {
      $out .= "<h4>Vocabulary links</h4>";
      $out .= $info["vocab_url"];
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
          $out .= "<li>".$pname." starts: https://api.audioblast.org/".$info["category"]."/".$name."/autocomplete/".$pname."/?s=[query]</li>";
          $out .= "<li>".$pname." contains: https://api.audioblast.org/".$info["category"]."/".$name."/autocomplete/".$pname."/?c=[query]</li>";
        }
      }
    }
    $out .= "</ul>";
    

    if (isset($info["see_also"])) {
      $out .= "<h4>See also</h4>";
      $out .= "<ul>";
      foreach ($info["see_also"] as $sa) {
        $out .= "<li>".$sa."</li>";
      }
      $out .= "</ul>";
    }
    
    $out.="</div>";

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
