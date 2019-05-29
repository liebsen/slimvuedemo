<?php

/*
 * This file is part of the Slim API skeleton packagesdfsdf
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

class Ecmalog extends \Spot\Entity
{
    protected static $table = "ecmalogs";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "url" => ["type" => "string", "length" => 250],
            "filename" => ["type" => "string", "length" => 250],
            "line" => ["type" => "string", "length" => 250],
            "colum" => ["type" => "string", "length" => 250],
            "browser" => ["type" => "string", "length" => 250],
            "ip" => ["type" => "string", "length" => 100],
            "message" => ["type" => "text"],
            "extra" => ["type" => "text"],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
        ];
    }
    
    public function transform(Ecmalog $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "url" => (string) $entity->url ?: "",
            "line" => (string) $entity->report ?: "",
            "browser" => (string) $entity->browser ?: "",
            "message" => (string) $entity->message ?: "",
            "extra" => (string) $entity->extra ?: ""
        ];
    }

    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "slug" => null
        ]);
    }
}
