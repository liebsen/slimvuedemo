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

class User extends \Spot\Entity
{
    protected static $table = "users";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "code" => ["type" => "string", "length" => 255],
            "role_id" => ["type" => "integer", "unsigned" => true, 'index' => true, 'value' => 1],
            "email" => ["type" => "string", "length" => 50, "unique" => true],
            "first_name" => ["type" => "string", "length" => 32],
            "last_name" => ["type" => "string", "length" => 32],
            "bio" => ["type" => "text"],
            "token" => ["type" => "text"],
            "password_hash" => ["type" => "string", "length" => 255],
            "password_token" => ["type" => "string", "length" => 255],
            "validated" => ["type" => "boolean", "value" => false],
            "last_activity" =>  ["type" => "string", "length" => 50],
            "enabled" => ["type" => "boolean", "value" => true],
            "created"   => ["type" => "datetime", "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'role' => $mapper->belongsTo($entity, 'App\UserType', 'role_id')
        ];
    }

    public function transform(User $entity)
    {

        $member_since = $entity->created;
        if($member_since){
            $member_since_date = $member_since->format('U');
        }

        return [
            "id" => (integer) $entity->id ?: null,
            "email" => (string) $entity->email ?: null,
            "first_name" => (string) $entity->first_name ?: "",
            "last_name" => (string) $entity->last_name ?: "",
            "bio" => (string) $entity->bio ?: "",
            "token" => \set_token($entity)
        ];
    }

    public function timestamp()
    {
        return $this->updated_at->getTimestamp();
    }

    public function clear()
    {
        $this->data([
            "password" => null,
            "enabled" => null
        ]);
    }
}
