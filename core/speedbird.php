<?php

/**
  Speedbird cache

  This simple cache service stores the results of queries that take significant
  execution time (i.e. more than a second). The exact queries that make use of
  the cache is defined where those queries are called, an example are the homepage
  statistics of http://audioblast.org. The choice of queries and cache durations
  are chosen in external code.

*/

function speedbird_put($key, $value) {
  $sql = "INSERT INTO speedbird (`key`, `value`) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE `value`='$value';";
  global $db;
  $db->query($sql);
}

function speedbird_get($key) {
  $sql = "SELECT `value` FROM `speedbird` WHERE `key`='$key';";
  global $db;
  $res = $db->query($sql);
  if ($res->num_rows == 0) {
    return(FALSE);
  }
  return(unserialize($res->fetch_assoc()['value']));
}
