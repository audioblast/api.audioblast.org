<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('default_charset', 'utf-8');

include("core/core.php");

//What do we need to do?
$page = "API";
if (isHomepage()) {$page = "home";}
if (isEmbedPage()) {$page = "embed";}

if ($page == "API") {
  moduleAPI($db);
} else {
  ?>
  <html>
    <head>
      <title>audioBLAST! API</title>
      <link rel="stylesheet" href="https://cdn.audioblast.org/ab-api.css">
    </head>
    <body>
      <img src="https://cdn.audioblast.org/audioblast_logo.png" />
      <?php
      switch($page) {
        case "home":
          print("<h1>audioBLAST! API</h1>");
          print(modulesHTML(loadModules()));
          break;
        case "embed":
          print("<h1>Embed API</h1>");
          embedPage();
          break;
      }
      ?>
    </body>
  </html>
<?php
}

$db->close();
