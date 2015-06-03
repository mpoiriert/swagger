<?php

namespace Draw\Swagger\Schema;

use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;

class Mixed
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @JMS\HandlerCallback("json", direction="serialization")
     */
    public function serialize(JsonSerializationVisitor $visitor)
    {
        return $this->data;
    }

    /**
     * @JMS\HandlerCallback("json", direction="deserialization")
     */
    public function deserialize(JsonDeserializationVisitor $visitor, $data)
    {
        $this->data = $data;
    }

    public static function convert($value, $valueIsArray = false)
    {
        if(is_null($value)) {
            return $value;
        }

        if($valueIsArray && is_array($value)) {
            foreach($value as $key => $data) {
                $value[$key] = static::convert($data);
            }
            return $value;
        }

        if($value instanceof Mixed) {
            return $value;
        }

        return new static($value);
    }
}