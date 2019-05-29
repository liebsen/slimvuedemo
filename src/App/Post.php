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

class Post extends \Spot\Entity
{
    protected static $table = "posts";

    public static function fields()
    {
        return [
            "id" => ["type" => "integer", "unsigned" => true, "primary" => true, "autoincrement" => true],
            "section_id" => ["type" => "integer", "unsigned" => true, "default" => 0, 'index' => true],
            "title" => ["type" => "string", "length" => 250],
            "title_slug" => ["type" => "string", "length" => 250],
            "intro" => ["type" => "text"],
            "button_link" => ["type" => "string", "length" => 250],
            "button_value" => ["type" => "string", "length" => 250],
            "pic1_url" => ["type" => "string", "length" => 255],
            "pic2_url" => ["type" => "string", "length" => 255],
            "pic3_url" => ["type" => "string", "length" => 255],
            "pic4_url" => ["type" => "string", "length" => 255],
            "pic5_url" => ["type" => "string", "length" => 255],
            "pic6_url" => ["type" => "string", "length" => 255],
            "picshare_url" => ["type" => "string", "length" => 255],
            "background_url" => ["type" => "string", "length" => 255],
            "youtube" => ["type" => "string", "length" => 50],
            "content_html" => ["type" => "text"],
            "from_datetime" => ["type" => "datetime", "value" => new \DateTime()],
            "to_datetime" => ["type" => "datetime", "value" => new \DateTime()],
            "deleted" => ["type" => "boolean", "value" => false, "notnull" => true],
            "enabled" => ["type" => "boolean", "default" => true, "value" => true],
            "created" => ["type" => "datetime", "value" => new \DateTime()],
            "updated" => ["type" => "datetime", "value" => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'section' => $mapper->belongsTo($entity, 'App\Section', 'section_id')
        ];
    }
    
    public function transform(Post $entity)
    {

        $slick = [];
        for($i=2;$i<7;$i++){
            if(!empty($entity->{'pic' . $i . '_url'})){
                $slick = $entity->{'pic' . $i . '_url'};
            }
        }

        $resolutions = explode(',',getenv('UPLOADS_RESOLUTIONS'));
        $sizes = [];

        foreach($resolutions as $res){
            $parts = explode('/',$entity->pic1_url);
            $name = $parts[count($parts)-1];
            $parts[count($parts)-1] = $res.$parts[count($parts)-1];
            $sizes[$res]=implode('/',$parts);
        }

        return [
            "id" => (integer) $entity->id ?: null,
            "title" => (string) $entity->title ?: "",
            "intro" => (string) $entity->intro ?: "",
            "button_value" => (string) $entity->button_value ?: "",
            "button_link" => (string) $entity->button_link ?: "",
            "slug" => (string) $entity->title_slug ?: "",
            "content" => (string) $entity->content_html ?: "",
            "picshare_url" => (string) $entity->picshare_url ?: "",
            "background_url" => (string) $entity->background_url ?: "",
            "picture" => (string) $entity->pic1_url ?: "",
            "position" => (string) $entity->position->slug ?: "",
            "sizes" => (array) $sizes,
            "slick" => (array) $slick,
            "status" => (string) $entity->status ?: ""
            //"created" => (string) $entity->created->format('U') ?: "",
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
