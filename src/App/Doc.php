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

class Doc extends \Spot\Entity
{
    protected static $table = "docs";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "name" => ["type" => "string", "length" => 250],
            "version" => ["type" => "string", "length" => 10],
            "title" => ["type" => "string", "length" => 250],
            "excerpt" => ["type" => "string", "length" => 250],
            "comment" => ["type" => "text"],
            "attachment1_url" => ["type" => "string", "length" => 255],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'items' => $mapper->hasMany($entity, 'App\DocItem', 'doc_id')->order(['title' => "ASC"])
        ];
    }
    
    public function transform(Doc $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "name" => (string) $entity->name ?: "",
            "version" => (string) $entity->version ?: "",
            "attachment1_url" => (string) $entity->attachment1_url ?: "",
            "title" => (string) $entity->title ?: "",
            "excerpt" => (string) $entity->excerpt ?: "",
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
