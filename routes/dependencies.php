<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

$container = $app->getContainer();

use Spot\Config;
use Spot\Locator;
use Doctrine\DBAL\Logging\MonologSQLLogger;

$container['view'] = function ($c) {
    $view = new \Slim\Views\Twig('templates', [
        'cache' => false
    ]);

    $localhost = ($_SERVER['REMOTE_ADDR'] == "127.0.0.1");
    $baseurl = $c['request']->getUri()->getScheme().'://'.$c['request']->getUri()->getHost();

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $c['request']->getUri()->getBasePath()), '/');
    $view->offsetSet('rev_parse', substr(exec('git rev-parse HEAD'),0,7));
    $view->offsetSet('ts', time());
    $view->offsetSet('mods', explode(',',getenv('APP_MODS')));
    $view->offsetSet('localhost', $localhost);
    $view->offsetSet('appver', getenv('APP_VERSION'));
    $view->offsetSet('baseurl', $baseurl);
    $view->offsetSet('pathname', $_SERVER['REQUEST_URI']);

    putenv("APP_URL=$baseurl");

    $view->addExtension(new Slim\Views\TwigExtension($c['router'], $basePath));

    return $view;
};

$container["spot"] = function ($container) {

    $config = new Config();
    $mysql = $config->addConnection("mysql", [
        "dbname" => getenv("DB_NAME"),
        "user" => getenv("DB_USER"),
        "password" => getenv("DB_PASSWORD"),
        "host" => getenv("DB_HOST"),
        "driver" => "pdo_mysql",
        "charset" => "utf8"
    ]);

    $spot = new Locator($config);

    $logger = new MonologSQLLogger($container["logger"]);
    $mysql->getConfiguration()->setSQLLogger($logger);

    return $spot;
};

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;
use Monolog\Formatter\LineFormatter;

$container = $app->getContainer();

$container["logger"] = function ($container) {
    $logger = new Logger("slim");

    $formatter = new LineFormatter(
        "[%datetime%] [%level_name%]: %message% %context%\n",
        null,
        true,
        true
    );

    /* Log to timestamped files */
    $rotating = new RotatingFileHandler(__DIR__ . "/../logs/slim.log", 0, getenv('DEBUG_LEVEL'));
    $rotating->setFormatter($formatter);
    $logger->pushHandler($rotating);

    return $logger;
};