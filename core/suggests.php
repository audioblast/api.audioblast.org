<?php

function suggests($source, $id, $db) {
  $modules = loadModules("data");
  $data = array();
  foreach ($modules as $module) {
    foreach ($module["params"] as $key => $param) {
      if (isset($param["suggest"])) {
        $data[$key] = array(
          "params" => $param["suggest"],
          "data" => suggests_query($source, $id, $db, $module["table"], $key, $param["suggest"]["same_source"])
        );
      }
    }
  }
 return($data);
}

function suggests_query($source, $id, $db, $table, $field, $same_source) {
  $sql = "SELECT $field FROM $table WHERE source = '$source' AND id = '$id'";
  $res = $db->query($sql);
  $val = $res->fetch_assoc()[$field];

  $ret = array();
  if ($same_source) {
    $sql = "SELECT * FROM $table WHERE $field = '$val' AND NOT (source='$source' AND id = '$id') AND source = '$source' LIMIT 10";
  } else {
    $sql = "SELECT * FROM $table WHERE $field = '$val' AND NOT (source='$source' AND id = '$id') LIMIT 10";
  }
  $res = $db->query($sql);
  while ($row = $res->fetch_assoc()) {
    $ret[] = $row;
  }
  return($ret);
}
