<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('default_charset', 'utf-8');

  include("core/core.php");

  //What do we need to do?
  if (isHomepage()) {
    ?>
    <html>
    <head>
      <title>audioBLAST! API</title>
      <link rel="stylesheet" href="https://cdn.audioblast.org/ab-api.css">
    </head>
    <body>
    <img src="https://cdn.audioblast.org/audioblast_logo.png" />
    <h1>audioBLAST! API</h1>
    <?php print(modulesHTML(loadModules())); ?>
    </body>
    </html>
    <?php
  } else if (isEmbedPage()) {
     embedPage();
  } else {
    moduleAPI($db);
  }

  $db->close();
