<?php 


$container = $app->getContainer();
$config = $container['spot']->mapper("App\Config")
    ->all();

foreach($config as $item){
    putenv("$item->config_key=$item->config_value");
}

require __DIR__ . "/auth.php";   
require __DIR__ . "/account.php";
// Remove this and line below when current refocus tool is obsolete
require __DIR__ . "/v1.php";   
