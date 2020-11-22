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
