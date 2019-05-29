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

class Todo extends \Spot\Entity
{
    protected static $table = "todos";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "title" => ["type" => "string", "length" => 250],
            "comment" => ["type" => "text"],
            "done" => ["type" => "boolean", "default" => false, "value" => false],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
        ];
    }
    
    public function transform(Todo $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (string) $entity->title ?: "",
            "done" => (boolean) $entity->done ?: false,
            "comment" => (string) $entity->comment ?: ""
        ];
    }
    
    public function timestamp()
    {
        return $this->updated->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "fullname" => null
        ]);
    }
}
