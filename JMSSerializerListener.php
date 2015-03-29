<?php

namespace Draw\Swagger;

use Draw\Swagger\Schema\VendorExtensionSupportInterface;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class JMSSerializerListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            array('event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'),
            array('event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize'),
            array('event' => Events::POST_SERIALIZE, 'method' => 'onPostSerialize')
        );
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

    public function onPreDeserialize(PreDeserializeEvent $event)
    {
        $data = $event->getData();

        $type = $event->getType();

        if(!class_exists($type['name'])) {
           return;
        }

        if(!is_array($data)) {
            return;
        }

        $vendorData = array();

        foreach($data as $key => $value) {
            if(!is_string($key)) {
                continue;
            }

            if(strpos($key,'x-') !== 0) {
                continue;
            }

            unset($data[$key]);
            $vendorData[$key] = $value;
        }

        if(!$vendorData) {
            return;
        }

        $reflectionClass = new \ReflectionClass($type['name']);
        if(!$reflectionClass->implementsInterface('Draw\Swagger\Schema\VendorExtensionSupportInterface')) {
            return;
        }

        $data['vendor'] = $vendorData;
        $event->setData($data);
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();

        $visitor = $event->getVisitor();
        /* @var $visitor \JMS\Serializer\JsonSerializationVisitor */

        if($object instanceof VendorExtensionSupportInterface) {
            foreach($object->getVendorData() as $key => $value) {
                $visitor->addData($key, $value->data);
            }
        }
    }
}