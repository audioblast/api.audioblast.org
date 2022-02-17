<?php

function speedbird_put($key, $value) {
  $sql = "INSERT INTO speedbird (`key`, `value`) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE `value`='$value';";
  global $db;
  $db->query($sql);
}

function speedbird_get($key) {
  $sql = "SELECT `value` FROM `speedbird` WHERE `key`='$key';";
  $res = $db->query($sql);
  return(unserialize($res->fetch_assoc()['value']));
}
