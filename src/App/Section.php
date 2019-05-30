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

class Section extends \Spot\Entity
{
    protected static $table = "sections";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "parent_id" => ["type" => "integer", "length" => 10, "value" => 0],
            "slug" => ["type" => "string", "length" => 255, "value" => 0, "comment" => "Slug"],
            "title" => ["type" => "string", "length" => 255, "value" => 0, "comment" => "Title"],
            "intro" => ["type" => "text", "comment" => "Excerpt"],
            "content_html" => ["type" => "text", "comment" => "Body"],
            "pic1_url" => ["type" => "string", "length" => 255, "comment" => "Main image"],
            "pic2_url" => ["type" => "string", "length" => 255, "comment" => "Secondary image"],
            "is_splash" => ["type" => "boolean", "default" => false, "value" => false],
            "is_navitem" => ["type" => "boolean", "default" => false, "value" => false],
            "is_footer" => ["type" => "boolean", "default" => false, "value" => false],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created"   => ["type" => "datetime", "default" => date('Y-m-d H:i:s'), "value" => new \DateTime()],
            "updated"   => ["type" => "datetime", "default" => date('Y-m-d H:i:s'), "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            //'parent' => $mapper->belongsTo($entity, 'App\Section', 'parent_id'),
            'posts' => $mapper->hasMany($entity, 'App\Post', 'section_id')
                ->order(["id" => "ASC"])
        ];
    }

    public function transform(Section $entity)
    {

        $posts = [];

        if($entity->posts){
            foreach($entity->posts as $post){
                $posts[] = (object) [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->title_slug,
                    'picture' => $post->pic1_url
                ];
            }
        }

        return [
            "id" => (string) $entity->id ?: "0",
            "title" => (string) $entity->title ?: "",
            "slug" => (string) $entity->slug ?: "",
            "intro" => (string) $entity->intro ?: "",
            "content" => (string) $entity->content_html ?: "",
            "picture" => (string) $entity->pic1_url ?: "",
            "posts" => $posts,
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
