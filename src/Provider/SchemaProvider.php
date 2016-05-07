<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker\Provider;

use InvalidArgumentException;
use League\JsonGuard\Dereferencer;
use League\JsonGuard\Reference;
use stdClass;

/**
 * Class SchemaProvider
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SchemaProvider
{
    /**
     * @var string
     */
    private $swaggerFile;

    /**
     * Constructor
     *
     * @param $swaggerFile
     */
    public function __construct($swaggerFile)
    {
        if (false === strpos($swaggerFile, '//')) {
            $swaggerFile = 'file:///' . $swaggerFile;
        }

        $this->swaggerFile = $swaggerFile;
    }

    /**
     * Get the schema from the swagger doc
     *
     * @param string $path
     * @param string $operation
     * @param int $response
     * @return stdClass
     * @throws InvalidArgumentException
     */
    public function getSchema($path, $operation, $response)
    {
        $dereferencer = new Dereferencer();
        $swagger = $dereferencer->dereference($this->swaggerFile);

        $basePath = $this->getProperty($swagger, 'basePath', '');
        $path = str_replace($basePath, '', $path);
        $paths = $this->getProperty($swagger, 'paths');
        $pathObject = $this->getProperty($paths, $path);
        $operationObject = $this->getProperty($pathObject, $operation);
        $operationResponses = $this->getProperty($operationObject, 'responses');

        $operationResponse = null;
        if ($this->hasProperty($operationResponses, $response)) {
            $operationResponse = $this->getProperty($operationResponses, $response);
        } else {
            $operationResponse = $this->getProperty($operationResponses, 'default');
        }

        $schema = new stdClass();
        if (null !== $operationResponse && $this->hasProperty($operationResponse, 'schema')) {
            $schema = $this->getProperty($operationResponse, 'schema');
        }

        return $schema;
    }

    /**
     * Get property from a stdClass, optionally specifying a default value
     *
     * @param stdClass $object
     * @param string $property
     * @param mixed $default
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function getProperty(stdClass $object, $property, $default = null)
    {
        if (!$this->hasProperty($object, $property)) {
            if (null !== $default) {
                return $default;
            }

            throw new InvalidArgumentException('Property does not exist in object');
        }

        $property = $object->$property;

        if ($property instanceof Reference) {
            $property = $property->resolve();
        }

        return $property;
    }

    /**
     * Check if property exists on class
     *
     * @param stdClass $object
     * @param string $property
     * @return bool
     */
    public function hasProperty(stdClass $object, $property)
    {
        return property_exists($object, $property);
    }
}
