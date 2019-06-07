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

        return $swaggerSchema;
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