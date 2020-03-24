<?php namespace Draw\Swagger;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\ManagerRegistry;
use Draw\Swagger\Extraction\Extractor\ConstraintExtractor;
use Draw\Swagger\Extraction\Extractor\DoctrineInheritanceExtractor;
use Draw\Swagger\Extraction\Extractor\JmsExtractor;
use Draw\Swagger\Extraction\Extractor\PhpDocOperationExtractor;
use Draw\Swagger\Extraction\Extractor\SwaggerParameterExtractor;
use Draw\Swagger\Extraction\Extractor\SwaggerTagExtractor;
use Draw\Swagger\Extraction\Extractor\TypeSchemaExtractor;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use ReflectionClass;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

class Builder
{
    /**
     * @var MetadataFactoryInterface
     */
    private $validatorMetadataFactory;

    /**
     * @var boolean
     */
    private $registerDefaultsExtractor = true;

    /**
     * @var SerializerInterface
     */
    private $jmsSerializer;

    /**
     * @var string[]
     */
    private $definitionAliases = [];

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @return MetadataFactoryInterface
     */
    public function getValidatorMetadataFactory()
    {
        if (is_null($this->validatorMetadataFactory)) {
            $this->validatorMetadataFactory = new LazyLoadingMetadataFactory(
                new AnnotationLoader($this->getAnnotationReader())
            );
        }

        return $this->validatorMetadataFactory;
    }

    /**
     * @return Reader
     */
    public function getAnnotationReader()
    {
        if (is_null($this->annotationReader)) {
            $this->annotationReader = new AnnotationReader();
        }

        return $this->annotationReader;
    }

    /**
     * @param Reader $annotationReader
     *
     * @return static
     */
    public function setAnnotationReader($annotationReader)
    {
        $this->annotationReader = $annotationReader;

        return $this;
    }

    /**
     * @param string $definition
     * @param string $alias
     *
     * @return static
     */
    public function registerDefinitionAlias($definition, $alias)
    {
        $this->definitionAliases[$definition] = $alias;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getDefinitionAliases()
    {
        return $this->definitionAliases;
    }

    /**
     * @param string[] $definitionAliases
     *
     * @return static
     */
    public function setDefinitionAliases($definitionAliases)
    {
        $this->definitionAliases = $definitionAliases;

        return $this;
    }

    /**
     * @param MetadataFactoryInterface $validatorMetadataFactory
     *
     * @return static
     */
    public function setValidatorMetadataFactory($validatorMetadataFactory)
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;

        return $this;
    }

    /**
     * @return SerializerInterface
     */
    public function getJmsSerializer()
    {
        if (is_null($this->jmsSerializer)) {
            $this->jmsSerializer = SerializerBuilder::create()->configureListeners(
                function (EventDispatcher $dispatcher) {
                    $dispatcher->addSubscriber(new JMSSerializerListener());
                }
            )
                ->setAnnotationReader($this->getAnnotationReader())
                ->build();
        }

        return $this->jmsSerializer;
    }

    /**
     * @param ManagerRegistry $managerRegistry
     *
     * @return $this
     */
    public function setManagerRegistry(ManagerRegistry $managerRegistry = null)
    {
        $this->managerRegistry = $managerRegistry;

        return $this;
    }

    /**
     * @param SerializerInterface $jmsSerializer
     *
     * @return static
     */
    public function setJmsSerializer(SerializerInterface $jmsSerializer)
    {
        $this->jmsSerializer = $jmsSerializer;

        return $this;
    }

    /**
     * Should we register or not the defaults extractor
     *
     * @param bool $register
     *
     * @return static
     */
    public function registerDefaultsExtractor($register = true)
    {
        $this->registerDefaultsExtractor = $register;

        return $this;
    }

    /**
     * @return Swagger
     */
    public function build()
    {
        $swagger = new Swagger($serializer = $this->getJmsSerializer());

        if ($this->registerDefaultsExtractor) {
            if ($serializer instanceof Serializer) {
                $serializer->serialize([], 'json', $context = new SerializationContext());
                $metaDataFactory = $context->getMetadataFactory();
                $swagger->registerExtractor(
                    new JmsExtractor(
                        $metaDataFactory,
                        new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy())
                    )
                );
            }

            foreach (glob(__DIR__ . '/Extraction/Extractor/Constraint/*.php') as $file) {
                $extractName = pathinfo($file, PATHINFO_FILENAME);
                $className = 'Draw\Swagger\Extraction\Extractor\Constraint\\' . $extractName;

                $reflection = new ReflectionClass($className);
                if (!$reflection->isInstantiable()) {
                    continue;
                }

                if (!$reflection->isSubclassOf(ConstraintExtractor::class)) {
                    continue;
                }

                /** @var ConstraintExtractor $extractor */
                $extractor = $reflection->newInstance();
                $extractor->setMetadataFactory($this->getValidatorMetadataFactory());
                $swagger->registerExtractor($extractor);
            }

            $swagger->registerExtractor(new PhpDocOperationExtractor(), 999);
            $swagger->registerExtractor(new SwaggerTagExtractor($this->getAnnotationReader()));
            $swagger->registerExtractor(new SwaggerParameterExtractor($this->getAnnotationReader()));
            $swagger->registerExtractor($typeExtractor = new TypeSchemaExtractor());

            foreach ($this->definitionAliases as $class => $alias) {
                $typeExtractor->registerDefinitionAlias($class, $alias);
            }

            if($this->managerRegistry) {
                $swagger->registerExtractor(new DoctrineInheritanceExtractor($this->managerRegistry));
            }
        }

        return $swagger;
    }

    public static function createBuilder()
    {
        return new static();
    }
}