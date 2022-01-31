<?php

function suggests($source, $id, $db) {
  $modules = loadModules("data");
  $data = array();
  foreach ($modules as $module) {
    foreach ($module["params"] as $key => $param) {
      if (isset($param["suggest"]) && $param["suggest"] == TRUE) {
        $data[$key] = suggests_query($source, $id, $db, $module["table"], $key);
      }
    }
  }
 return($data);
}

function suggests_query($source, $id, $db, $table, $field) {
  $sql = "SELECT $field FROM $table WHERE source = '$source' AND id = '$id'";
  $res = $db->query($sql);
  $val = $res->fetch_assoc()[$field];

  $ret = array();
  $sql = "SELECT * FROM $table WHERE $field = '$val' AND NOT (source='$source' AND id = '$id') LIMIT 10";
  $res = $db->query($sql);
  while ($row = $res->fetch_assoc()) {
    $ret[] = $row;
  }
  return($ret);
}
