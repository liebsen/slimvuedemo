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

namespace App;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;
use Spot\EventEmitter;
use Tuupola\Base62;
use Ramsey\Uuid\Uuid;
use Psr\Log\LogLevel;

class Config extends \Spot\Entity
{
    protected static $table = "config";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "config_key" => ["type" => "string", "length" => 255, "value" => 0, "comment" => "Setting Key"],
            "config_value" => ["type" => "text", "comment" => "Setting Value"],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
        ];
    }

    public function transform(Config $entity)
    {
        return [
            "id" => (string) $entity->id ?: "0",
            "key" => (string) $entity->config_key ?: "",
            "value" => (string) $entity->config_value ?: "",
            "created" => (string) $entity->created->format('Y-m-d H:i:s'),
            "updated" => (string) $entity->updated->format('Y-m-d H:i:s')
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "title" => null,
            "image" => null,
            "enabled" => null
        ]);
    }
}