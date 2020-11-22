<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

  include("core/core.php");

  //What do we need to do?
  if (isHomepage()) {
    ?>
    <html>
    <head>
      <link rel="stylesheet" href="https://cdn.audioblast.org/ab-api.css">
    </head>
    <body>
    <img src="https://cdn.audioblast.org/audioblast_logo.png" />
    <h1>audioBLAST! API</h1>
    <?php print(modulesHTML(loadModules())); ?>
    </body>
    </html>
    <?php
  } else {
    moduleAPI($db);
  }

  $db->close();
