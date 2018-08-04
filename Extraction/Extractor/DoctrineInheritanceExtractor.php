<?php namespace Draw\Swagger\Extraction\Extractor;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Draw\Swagger\Extraction\ExtractionContextInterface;
use Draw\Swagger\Extraction\ExtractionImpossibleException;
use Draw\Swagger\Extraction\ExtractorInterface;
use Draw\Swagger\Schema\Schema;

class DoctrineInheritanceExtractor implements ExtractorInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function canExtract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$source instanceof \ReflectionClass) {
            return false;
        }

        if (!$target instanceof Schema) {
            return false;
        }

        if (!$this->managerRegistry->getManagerForClass($source->name)) {
            return false;
        }

        return true;
    }

    /**
     * @param \ReflectionClass $source
     * @param Schema $target
     * @param ExtractionContextInterface $extractionContext
     *
     * @throws ExtractionImpossibleException
     */
    public function extract($source, $target, ExtractionContextInterface $extractionContext)
    {
        if (!$this->canExtract($source, $target, $extractionContext)) {
            throw new ExtractionImpossibleException();
        }


        $metaData = $this->managerRegistry->getManagerForClass($source->name)->getClassMetadata($source->name);
        if (!$metaData instanceof ClassMetadata) {
            return;
        }

        if ($metaData->isInheritanceTypeNone()) {
            return;
        }

        $swagger = $extractionContext->getSwagger();

        if ($metaData->isRootEntity()) {
            $target->discriminator = $metaData->discriminatorColumn['name'];
            $target->required[] = $target->discriminator;
            foreach ($metaData->discriminatorMap as $key => $class) {
                $schema = new Schema();
                $swagger->extract($class, $schema, $extractionContext);
            }
            $target->properties[$metaData->discriminatorColumn['name']] = $property = new Schema();
            $property->type = 'string';
            $property->description = 'The concrete class of the inheritance.';
            $property->enum = array_keys($metaData->discriminatorMap);
        } else {
            if(isset($target->properties[$metaData->discriminatorColumn['name']])) {
                $property = $target->properties[$metaData->discriminatorColumn['name']];
                $property->description = 'Discriminator property. Value will be ';
                $property->type = 'string';
                $property->enum = [$metaData->discriminatorValue];
            }
        }
    }
}
