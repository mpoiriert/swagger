<?php namespace Draw\Swagger;

use Draw\Swagger\Schema\PathItem;
use Draw\Swagger\Schema\Schema;
use Draw\Swagger\Schema\Swagger as SwaggerSchema;

/**
 * This class is to clean up the schema before dumping it.
 * It will remove duplicate definition alias
 *
 * @internal
 */
class SchemaCleaner
{
    /**
     * @param SwaggerSchema $swaggerSchema
     * @return SwaggerSchema The cleaned schema
     */
    public function clean(SwaggerSchema $swaggerSchema)
    {
        // This is to "clone" the object recursively
        /** @var SwaggerSchema $swaggerSchema */
        $swaggerSchema = unserialize(serialize($swaggerSchema));

        do {
            $definitionSchemasByObject = [];
            foreach($swaggerSchema->definitions as $name => $definitionSchema) {
                $definitionSchemasByObject[parse_url($name)['path']][$name] = $definitionSchema;
            }

            $replaceSchemas = [];
            foreach ($definitionSchemasByObject as $objectName => $definitionSchemas) {
                /** @var Schema[] $selectedSchemas */
                $selectedSchemas = [];
                array_walk($definitionSchemas,
                    function (Schema $schema, $name) use (&$selectedSchemas, &$replaceSchemas) {
                        foreach ($selectedSchemas as $selectedName => $selectedSchema) {
                            if ($this->isEqual($selectedSchema, $schema)) {
                                $replaceSchemas[$name] = $selectedName;
                                return;
                            }
                        }
                        $selectedSchemas[$name] = $schema;
                    });
            }

            foreach($replaceSchemas as $toReplace => $replaceWith) {
                $this->replaceSchemaReference(
                    $swaggerSchema,
                    '#/definitions/' . $toReplace,
                    '#/definitions/' . $replaceWith
                );

                unset($swaggerSchema->definitions[$toReplace]);
            }
        } while (count($replaceSchemas));

        do {
            $suppressionOccurred = false;
            foreach ($swaggerSchema->definitions as $name => $definitionSchema) {
                if (!$this->hasSchemaReference($swaggerSchema, '#/definitions/' . $name)) {
                    unset($swaggerSchema->definitions[$name]);
                    $suppressionOccurred = true;
                }
            }
        } while ($suppressionOccurred);

        // Rename aliases in case of skip to be cleaner (e.g.: [User?3, User?6] => [User, User?1])
        $definitionsToRename = [];
        foreach($swaggerSchema->definitions as $name => $definitionSchema) {
            $definitionsToRename[parse_url($name)['path']][] = $name;
        }

        foreach ($definitionsToRename as $objectName => $names) {
            array_walk($names,
                function ($name, $index) use ($objectName, $swaggerSchema) {
                    $replaceWith = $objectName . ($index ? '?' . $index : '');
                    // If the replace name is the same as the current index we do not do anything
                    if($replaceWith == $name) {
                        return;
                    }
                    $swaggerSchema->definitions[$replaceWith] = $swaggerSchema->definitions[$name];
                    unset($swaggerSchema->definitions[$name]);
                    $this->replaceSchemaReference(
                        $swaggerSchema,
                        '#/definitions/' . $name,
                        '#/definitions/' . $replaceWith
                    );
                });
        }

        return $swaggerSchema;
    }

    private function hasSchemaReference($data, $reference)
    {
        if(!is_object($data) && !is_array($data)) {
            return false;
        }

        if(is_object($data)) {
            if($data instanceof Schema || $data instanceof PathItem) {
                if($data->ref == $reference) {
                    return true;
                }
            }
        }

        foreach ($data as &$value) {
            if ($this->hasSchemaReference($value, $reference)) {
                return true;
            }
        }

        return false;
    }

    private function replaceSchemaReference($data, $definitionToReplace, $definitionToReplaceWith)
    {
        if(!is_object($data) && !is_array($data)) {
            return;
        }

        if(is_object($data)) {
            if($data instanceof Schema || $data instanceof PathItem) {
                if($data->ref == $definitionToReplace) {
                    $data->ref = $definitionToReplaceWith;
                }
            }
        }

        foreach($data as &$value) {
            $this->replaceSchemaReference($value, $definitionToReplace, $definitionToReplaceWith);
        }
    }

    /**
     * @param Schema $schemaA
     * @param Schema $schemaB
     * @return bool
     */
    private function isEqual(Schema $schemaA, Schema $schemaB)
    {
        return $schemaA == $schemaB;
    }
}