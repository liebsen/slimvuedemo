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

class Contact extends \Spot\Entity
{
    protected static $table = "contacts";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "user_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "first_name" => ["type" => "string", "length" => 250],
            "last_name" => ["type" => "string", "length" => 250],
            "full_name" => ["type" => "string", "length" => 250],
            "email" => ["type" => "string", "length" => 250],
            "reason" => ["type" => "string", "length" => 250],
            "comment" => ["type" => "text"],
            "attachment1" => ["type" => "string", "length" => 255],
            "attachment2" => ["type" => "string", "length" => 255],
            "deleted" => ["type" => "boolean", "value" => false, "notnull" => true],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'App\User', 'user_id')
        ];
    }
    
    public function transform(Contact $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "first_name" => (string) $entity->first_name ?: "",
            "last_name" => (string) $entity->last_name ?: "",
            "email" => (string) $entity->email ?: "",
            "comment" => (string) $entity->comment ?: "",
            "user" => (object) [
                "id" => (integer) $entity->user->id ?: null,
                "first_name" => (string) $entity->user->first_name ?: null,
                "last_name" => (string) $entity->user->last_name ?: null,
                "email" => (string) $entity->user->email ?: null
            ]
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
