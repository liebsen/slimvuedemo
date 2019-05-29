<?php 

/*
 * This a demo project made with Slim, Adminer & Vue
 *
 * Copyright (c) 2019 Overlemon
 * Author: Martin Frith
 * Email: telemagico@gmail.com
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/martinfree/slimvue
 *
 */

date_default_timezone_set('America/Argentina/Buenos_Aires'); 

require __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);

require __DIR__ . "/routes/functions.php";
require __DIR__ . "/routes/dependencies.php";
require __DIR__ . "/routes/handlers.php";
require __DIR__ . "/routes/middleware.php";
require __DIR__ . "/routes/routes.php";	

$app->run();