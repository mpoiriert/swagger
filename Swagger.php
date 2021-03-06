<?php

namespace Draw\Swagger;

use Doctrine\Common\Annotations\AnnotationReader;
use Draw\Swagger\Extraction\ExtractionContext;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Extraction\Extractor\SwaggerSchemaExtractor;
use InvalidArgumentException;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Draw\Swagger\Schema\Swagger as Schema;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;

/**
 * Class Generator
 *
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
class Swagger
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var array
     */
    private $extractors = array();

    /**
     * @var ExtractorInterface[]
     */
    private $sortedExtractors;

    /**
     * Whether or not we want to clean the schema on dump
     *
     * @var bool
     */
    private $cleanOnDump = false;

    /**
     * @var SchemaCleaner
     */
    private $schemaCleaner;

    public function __construct(SerializerInterface $serializer = null, SchemaCleaner $schemaCleaner = null)
    {
        if (is_null($serializer)) {
            $serializer = SerializerBuilder::create()
                ->configureListeners(
                    function (EventDispatcher $dispatcher) {
                        $dispatcher->addSubscriber(new JMSSerializerListener());
                    }
                )
                ->configureHandlers(
                    function (HandlerRegistry $handlerRegistry) {
                        $handlerRegistry->registerSubscribingHandler(new JMSSerializerHandler());
                    }
                )
                ->build();

        }

        $this->serializer = $serializer;

        $this->schemaCleaner = $schemaCleaner ?: new SchemaCleaner();

        $this->registerExtractor(new SwaggerSchemaExtractor($this->serializer), -1, 'swagger');
    }

    public function registerExtractor(ExtractorInterface $extractorInterface, $position = 0, $section = 'default')
    {
        $this->extractors[$section][$position][] = $extractorInterface;
        $this->sortedExtractors = null;
    }

    /**
     * @return bool
     */
    public function getCleanOnDump()
    {
        return $this->cleanOnDump;
    }

    /**
     * @param bool $cleanOnDump
     */
    public function setCleanOnDump($cleanOnDump)
    {
        $this->cleanOnDump = $cleanOnDump;
    }

    /**
     * @param Schema $schema
     * @param boolean $validate
     * @return string
     */
    public function dump(Schema $schema, $validate = true)
    {
        if($this->cleanOnDump) {
            $schema = $this->schemaCleaner->clean($schema);
        }

        if ($validate) {
            $this->validate($schema);
        }

        return $this->serializer->serialize($schema, 'json');
    }

    /**
     * @param Schema $schema
     */
    public function validate(Schema $schema)
    {
        /** @var ConstraintViolationList $result */
        $result = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(new AnnotationReader())
            ->getValidator()
            ->validate($schema, null, array(Constraint::DEFAULT_GROUP));

        if (count($result)) {
            throw new InvalidArgumentException("" . $result);
        }
    }

    /**
     * @param $source
     * @param null $type
     * @param ExtractionContextInterface|null $extractionContext
     * @return Schema
     */
    public function extract($source, $type = null, ExtractionContextInterface $extractionContext = null)
    {
        if (is_null($type)) {
            $type = new Schema();
        }

        if (is_null($extractionContext)) {
            $extractionContext = new ExtractionContext($this, $type);
        }

        foreach ($this->getSortedExtractors() as $extractor) {
            if ($extractor->canExtract($source, $type, $extractionContext)) {
                $extractor->extract($source, $type, $extractionContext);
            }
        }

        return $type;
    }

    /**
     * @return ExtractorInterface[]
     */
    private function getSortedExtractors()
    {
        if (is_null($this->sortedExtractors)) {
            $this->sortedExtractors = array();
            foreach ($this->extractors as $section => $extractors) {
                ksort($extractors);
                array_unshift($extractors, $this->sortedExtractors);
                $this->sortedExtractors = call_user_func_array('array_merge', $extractors);
            }
        }

        return $this->sortedExtractors;
    }
} 