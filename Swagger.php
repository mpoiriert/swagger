<?php

namespace Draw\Swagger;

use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Draw\Swagger\Schema\Swagger as Schema;

/**
 * Class Generator
 *
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class Swagger
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(EventDispatcherInterface $eventDispatcher = null, SerializerInterface $serializer = null)
    {
        if(is_null($eventDispatcher)) {
            $eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        }
        $this->eventDispatcher = $eventDispatcher;

        if(is_null($serializer)) {
            $serializer = SerializerBuilder::create()->configureListeners(
                function(EventDispatcher $dispatcher) {
                    $dispatcher->addSubscriber(new JMSSerializerListener());
                }
            )->build();

        }
        $this->serializer = $serializer;
    }

    /**
     * @param Schema|null $schema The base swagger schema to start from
     * @return \Draw\Swagger\Schema\Swagger
     */
    public function build(Schema $schema = null)
    {
        if (is_null($schema)) {
            $schema = new Schema();
        }

        $this->eventDispatcher->dispatch(
            GenerateEvent::NAME,
            new GenerateEvent($this, array('swagger' => $schema))
        );

        return $schema;
    }

    /**
     * @param Schema $schema
     * @return string
     */
    public function dump(Schema $schema)
    {
        return $this->serializer->serialize($schema,'json');
    }

    /**
     * @api
     * @param string $jsonSchema
     * @return Schema
     */
    public function extract($jsonSchema)
    {
        return $this->serializer->deserialize($jsonSchema, 'Draw\Swagger\Schema\Swagger','json');
    }
} 