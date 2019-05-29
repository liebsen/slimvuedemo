<?php 

function adminer_object() {

  foreach (glob("plugins/*.php") as $filename) {
      include_once "./$filename";
  }
  
  $plugins = array(
  	new AdminerTheme(),
  	new AdminerPassword(),
    new AdminerFileUpload(__DIR__ . '/../uploads/')
  );

  return new AdminerPlugin($plugins); 
}

date_default_timezone_set('America/Argentina/Buenos_Aires'); 

include_once __DIR__ . "/../../vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../..');
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);

include_once __DIR__ . "/../../routes/dependencies.php";
include_once __DIR__ . "/../../routes/handlers.php";
include_once __DIR__ . "/../../routes/middleware.php";
include_once __DIR__ . '/../../routes/functions.php';
include_once __DIR__ . '/editor.php';

