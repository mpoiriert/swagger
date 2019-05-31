<?php namespace Draw\Swagger;

use Draw\Swagger\Schema\Mixed;
use Draw\Swagger\Schema\SecurityRequirement;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;

class JMSSerializerHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => SecurityRequirement::class,
                'method' => 'serializeSecurityRequirementToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => SecurityRequirement::class,
                'method' => 'deserializeSecurityRequirementToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Mixed::class,
                'method' => 'serializeMixedToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
                'type' => Mixed::class,
                'method' => 'deserializeMixedToJson',
            ],
        ];
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param SecurityRequirement $securityRequirement
     * @param array $type
     * @param Context $context
     * @return mixed
     */
    public function serializeSecurityRequirementToJson(
        JsonSerializationVisitor $visitor,
        SecurityRequirement $securityRequirement,
        array $type,
        Context $context
    ) {
        return $securityRequirement->getData();
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param SecurityRequirement $data
     * @param array $type
     * @param Context $context
     * @return SecurityRequirement
     */
    public function deserializeSecurityRequirementToJson(
        JsonDeserializationVisitor $visitor,
        $data,
        array $type,
        Context $context
    ) {
        $securityRequirement = new SecurityRequirement();
        $securityRequirement->setData($data);
        return $securityRequirement;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Mixed $mixed
     * @param array $type
     * @param Context $context
     * @return mixed
     */
    public function serializeMixedToJson(
        JsonSerializationVisitor $visitor,
        Mixed $mixed,
        array $type,
        Context $context
    ) {
        return $mixed->data;
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param SecurityRequirement $data
     * @param array $type
     * @param Context $context
     * @return Mixed
     */
    public function deserializeMixedToJson(
        JsonDeserializationVisitor $visitor,
        $data,
        array $type,
        Context $context
    ) {
        return new Mixed($data);
    }
}