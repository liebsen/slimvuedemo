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

class DocItem extends \Spot\Entity
{
    protected static $table = "docs_items";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "doc_id" => ["type" => "integer", "length" => 10, "value" => 0],
            "name" => ["type" => "string", "length" => 250],
            "title" => ["type" => "string", "length" => 250],
            "excerpt" => ["type" => "string", "length" => 250],
            "link1" => ["type" => "string", "length" => 250],
            "link2" => ["type" => "string", "length" => 250],
            "comment" => ["type" => "text"],
            "attachment1_url" => ["type" => "string", "length" => 255],
            "attachment2_url" => ["type" => "string", "length" => 255],
            "attachment3_url" => ["type" => "string", "length" => 255],            
            "desc_att1" => ["type" => "string", "length" => 255],
            "desc_att2" => ["type" => "string", "length" => 255],
            "desc_att3" => ["type" => "string", "length" => 255],            
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'doc' => $mapper->belongsTo($entity, 'App\Doc', 'doc_id')
        ];
    }
    
    public function transform(DocItem $entity)
    {
        return [
            "id" => (integer) $entity->id ?: null,
            "doc_id" => (string) $entity->doc_id ?: "",
            "title" => (string) $entity->title ?: "",
            "name" => (string) $entity->name ?: "",
            "link1" => (string) $entity->link1 ?: "",
            "link2" => (string) $entity->link2 ?: "",
            "comment" => (string) $entity->comment ?: "",
            "attachment1" => (string) $entity->attachment1 ?: "",
            "attachment2" => (string) $entity->attachment2 ?: "",
            "attachment3" => (string) $entity->attachment3 ?: "",
            "attachment_text1" => (string) $entity->desc_att1 ?: "",
            "attachment_text2" => (string) $entity->desc_att2 ?: "",
            "attachment_text3" => (string) $entity->desc_att3 ?: ""
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
