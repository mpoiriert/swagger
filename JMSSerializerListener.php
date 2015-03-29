<?php

namespace Draw\Swagger;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class JMSSerializerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize']
        ];
    }

    /**
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();
        if (is_object($object) &&
            is_subclass_of($object, 'Draw\Swagger\Schema\BaseParameter') &&
            get_class($object) !== $event->getType()['name']
        ) {
            $event->setType(get_class($event->getObject()));
        }
    }
}