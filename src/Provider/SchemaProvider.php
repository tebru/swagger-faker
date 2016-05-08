<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker\Provider;

use InvalidArgumentException;
use League\JsonGuard\Dereferencer;
use League\JsonGuard\Reference;
use League\JsonGuard\Validator;
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
        $pathObject = $this->findInPaths($paths, $path, $operation);
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

    /**
     * @param stdClass $paths
     * @param string $path
     * @param string $operation
     * @return stdClass
     * @throws InvalidArgumentException
     */
    private function findInPaths(stdClass $paths, $path, $operation)
    {
        $pathsArray = get_object_vars($paths);

        if (array_key_exists($path, $pathsArray)) {
            return $pathsArray[$path];
        }

        $pathParts = explode('/', $path);

        $foundPath = null;
        foreach ($pathsArray as $pathKey => $value) {
            // assume we found it
            $foundPath = $value;

            $pathKeyParts = explode('/', $pathKey);

            if (count($pathParts) !== count($pathKeyParts)) {
                continue;
            }

            if (false === strpos($pathKey, '{')) {
                $foundPath = null;
                continue;
            }

            // skip if we don't have the right operation
            if (!$this->hasProperty($value, $operation)) {
                $foundPath = null;
                continue;
            }

            $operationProperty = $this->getProperty($value, $operation);

            // skip if there aren't any parameters
            if (!$this->hasProperty($operationProperty, 'parameters')) {
                $foundPath = null;
                continue;
            }

            $parameters = $this->getProperty($operationProperty, 'parameters');

            foreach ($pathKeyParts as $index => $part) {
                // if the key we're checking doesn't exist, exit
                if (!array_key_exists($index, $pathParts)) {
                    $foundPath = null;
                    break;
                }

                if (0 === strpos($part, '{')) {
                    $name = substr($part, 1, -1);

                    foreach ($parameters as $parameter) {
                        $inProperty = $this->getProperty($parameter, 'in', '');
                        if ($inProperty !== 'path') {
                            $foundPath = null;
                            continue;
                        }

                        $nameProperty = $this->getProperty($parameter, 'name', '');
                        if ($name !== $nameProperty) {
                            $foundPath = null;
                            continue;
                        }

                        // required is not valid in v4 of json schema
                        if ($this->hasProperty($parameter, 'required')) {
                            unset($parameter->required);
                        }

                        $validator = new Validator($pathParts[$index], $parameter);
                        if ($validator->fails()) {
                            $foundPath = null;
                            break;
                        } else {
                            break;
                        }
                    }
                } else {
                    // if they're not equal, exit
                    if ($part !== $pathParts[$index]) {
                        $foundPath = null;
                        break;
                    }
                }
            }

            if (null !== $foundPath) {
                break;
            }
        }

        if (null === $foundPath) {
            throw new InvalidArgumentException('Could not find swagger path');
        }

        return $foundPath;
    }
}
