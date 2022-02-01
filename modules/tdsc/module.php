<?php

function tdsc_info() {
  $info = array(
    "mname" => "tdsc",
    "version" => 1.0,
    "category" => "analysis",
    "hname" => "Time Domain Signal Coding",
    "desc" => "Time domain signal coding is...",
    "table" => "analysis-tdsc",
    "code" => array(
    "type" => "package",
      "language" => "R",
      "name" => "tdsc",
      "source" => "CRAN",
      "function" => "tdsc()"
    ),
    "params" => array(
      "source" => array(
        "desc" => "Filter by source",
        "type" => "string",
        "default" => "",
        "column" => "source",
        "op" => "=",
      ),
      "id" => array(
        "desc" => "filter by id within source",
        "type" => "string",
        "default" => "",
        "column" => "id",
        "op" => "="
      ),
      "startTime" => array(
        "desc" => "start time(s) to return",
        "type" => "integer",
        "default" => 0,
        "column" => "startTime",
        "op" => "range"
      ),
      "value" => array(
        "desc" => "Output of analysis",
        "type" => "integer",
        "default" => 0,
        "column" => "tdsc",
        "op" => "range"
      ),
      "output" => array(
        "desc" => "At present just an array",
        "type" => "string",
        "allowed" => array(
          "JSON"
        ),
        "default" => "JSON"
      )
    ),
  );
  return($info);
}

function tdsc_search_info() {
  $info = array(
    "5x5" => array(
      "callback" => "tdsc_search_5x5",
      "params" => array(
        "source" => array(
          "desc" => "Source",
          "type" => "string",
          "column" => "source"
        ),
        "id" => array(
          "desc" => "ID within source",
          "type" => "string",
          "column" => "id"
        ),
        "startTime" => array(
          "desc" => "start time",
          "type" => "integer",
          "column" => "startTime"
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
  );
  return($info);
}

function tdsc_search_5x5($f) {
  $sql = "SELECT * FROM `analysis-tdsc5x5` WHERE source='".$f["source"]."' AND id=".$f["id"]." AND startTime=".$f["startTime"].";";
  global $db;
  $res = $db->query($sql);
  $ret = $res->fetch_assoc();

  $sql  = "SELECT `recordings`.source, `recordings`.id, taxon, startTime, ";
  $sql .= "SQRT(";
  $sql .= "POW(a-".$ret["a"].") + ";
  $sql .= "POW(b-".$ret["b"].") + ";
  $sql .= "POW(c-".$ret["c"].") + ";
  $sql .= "POW(d-".$ret["d"].") + ";
  $sql .= "POW(e-".$ret["e"].") + ";
  $sql .= "POW(f-".$ret["f"].") + ";
  $sql .= "POW(g-".$ret["g"].") + ";
  $sql .= "POW(h-".$ret["h"].") + ";
  $sql .= "POW(i-".$ret["i"].") + ";
  $sql .= "POW(j-".$ret["j"].") + ";
  $sql .= "POW(k-".$ret["k"].") + ";
  $sql .= "POW(l-".$ret["l"].") + ";
  $sql .= "POW(m-".$ret["m"].") + ";
  $sql .= "POW(n-".$ret["n"].") + ";
  $sql .= "POW(o-".$ret["o"].") + ";
  $sql .= "POW(p-".$ret["p"].") + ";
  $sql .= "POW(q-".$ret["q"].") + ";
  $sql .= "POW(r-".$ret["r"].") + ";
  $sql .= "POW(s-".$ret["s"].") + ";
  $sql .= "POW(t-".$ret["t"].") + ";
  $sql .= "POW(u-".$ret["u"].") + ";
  $sql .= "POW(v-".$ret["v"].") + ";
  $sql .= "POW(w-".$ret["w"].") + ";
  $sql .= "POW(x-".$ret["x"].") + ";
  $sql .= "POW(y-".$ret["y"].")";
  $sql .= ") AS diff FROM `analysis-tdsc5x5` INNER JOIN `recordings`";
  $sql .= " ON `analysis-tdsc5x5`.`source` = `recordings`.`source` AND `analysis-tdsc5x5`.`id` = `recordings`.`id`";
  $sql .= " ORDER BY diff DESC LIMIT 10;";

print_r($sql);

  $res = $db->query($sql);
  $ret = array();
  while ($row = $res->fetch_assoc()) {
    $ret["data"][] = $row;
  }
  return($ret);
}
