<?php

namespace Draw\Swagger\Schema;

use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;

class Mixed
{
    private $data;

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
}