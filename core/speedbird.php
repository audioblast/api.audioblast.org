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
  global $db;
  // Parameterised upsert. $value is serialize() output, which can contain
  // quotes and backslashes; the old raw-interpolated INSERT would corrupt or
  // fail on those (and was injectable). The value is bound twice -- once for
  // the INSERT, once for the UPDATE -- to avoid the deprecated VALUES().
  $stmt = $db->prepare("INSERT INTO `speedbird` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?;");
  if (!$stmt) {return;}
  $stmt->bind_param("sss", $key, $value, $value);
  $stmt->execute();
  $stmt->close();
}

function speedbird_get($key) {
  global $db;
  $stmt = $db->prepare("SELECT `value` FROM `speedbird` WHERE `key` = ?;");
  if (!$stmt) {return(FALSE);}
  $stmt->bind_param("s", $key);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  if ($row === NULL) {
    return(FALSE);
  }
  // Cache contents are written only by speedbird_put (trusted, internal), so
  // this unserialize() is not exposed to user-controlled input.
  return(unserialize($row['value']));
}
