<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker\Generator;

use Faker\Generator;
use InvalidArgumentException;
use stdClass;
use Tebru\SwaggerFaker\Config\GenerationConfig;
use Tebru\SwaggerFaker\Enum\SchemaType;
use Tebru\SwaggerFaker\Provider\SchemaProvider;

/**
 * Class SchemaGenerator
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SchemaGenerator
{
    /**
     * @var SchemaProvider
     */
    private $provider;

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var GenerationConfig
     */
    private $config;

    /**
     * @var array
     */
    private static $map = ['date-time' => 'iso8601', 'uri' => 'url', 'hostname' => 'domainName'];

    /**
     * Constructor
     *
     * @param SchemaProvider $provider
     * @param Generator $generator
     * @param GenerationConfig $config
     */
    public function __construct(SchemaProvider $provider, Generator $generator, GenerationConfig $config)
    {
        $this->provider = $provider;
        $this->generator = $generator;
        $this->config = $config;
    }

    /**
     * Initial generate method that delegates to specific type handlers
     *
     * @param stdClass $schema
     * @param bool $required
     * @param string|null $property
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function generate(stdClass $schema, $required = true, $property = null)
    {
        $type = new SchemaType($this->provider->getProperty($schema, 'type'));

        $result = null;
        switch ($type->getValue()) {
            case SchemaType::ARRY:
                $result = $this->handleArray($schema);
                break;
            case SchemaType::OBJECT:
                $result = $this->handleObject($schema);
                break;
            case SchemaType::INTEGER:
                $result = $this->handleInteger($schema, $required);
                break;
            case SchemaType::NUMBER:
                $result = $this->handleNumber($schema, $required);
                break;
            case SchemaType::STRING:
                $result = $this->handleString($schema, $required, $property);
                break;
            case SchemaType::BOOLEAN:
                $result = $this->handleBoolean($schema, $required);
                break;
        }
        
        return $result;
    }

    /**
     * Generate array data
     *
     * @param stdClass $schema
     * @return array
     * @throws InvalidArgumentException
     */
    private function handleArray(stdClass $schema)
    {
        $items = $this->provider->getProperty($schema, 'items');

        $maxItems = $this->provider->getProperty($schema, 'maxItems', $this->config->getMaxItems());
        $minItems = $this->provider->getProperty($schema, 'minItems', $this->config->getMinItems());
        $uniqueItems = $this->provider->getProperty($schema, 'uniqueItems', $this->config->isUniqueItems());

        $numberItems = $this->generator->numberBetween($minItems, $maxItems);

        $arrayData = [];
        for ($i = 0; $i < $numberItems; $i++) {
            $generated = $this->generate($items);

            if (null === $generated) {
                continue;
            }

            $arrayData[] = $generated;
        }

        if ($uniqueItems) {
            $arrayData = array_unique($arrayData);
        }

        return $arrayData;
    }

    /**
     * Generate object data
     *
     * @param stdClass $schema
     * @return stdClass
     * @throws InvalidArgumentException
     */
    private function handleObject(stdClass $schema)
    {
        $objectData = new stdClass();
        if ($this->provider->hasProperty($schema, 'properties')) {
            $properties = $this->provider->getProperty($schema, 'properties');
            $requiredValues = array_flip($this->provider->getProperty($schema, 'required', []));

            foreach (get_object_vars($properties) as $name => $value) {
                $property = $this->provider->getProperty($properties, $name);
                $generated = $this->generate($property, array_key_exists($name, $requiredValues), $name);

                if (null === $generated) {
                    continue;
                }

                $objectData->$name = $generated;
            }
        }

        // add allOf data if it exists
        $objects = [];
        if ($this->provider->hasProperty($schema, 'allOf')) {
            $allOf = $this->provider->getProperty($schema, 'allOf');

            foreach ($allOf as $element) {
                $objects[] = $this->generate($element);
            }

            foreach ($objects as $object) {
                foreach (get_object_vars($object) as $name => $value) {
                    $objectData->$name = $value;
                }
            }
        }

        return $objectData;
    }

    /**
     * Generate integer
     *
     * @param stdClass $schema
     * @param bool $required
     * @param string $property
     * @return int|null
     * @throws InvalidArgumentException
     */
    private function handleInteger($schema, $required, $property = null)
    {
        if (!$this->shouldHandle($required)) {
            return null;
        }

        $enum = $this->getEnum($schema);
        if (null !== $enum) {
            return $enum;
        }

        if ($this->hasFormatter($schema, $property)) {
            $formatter = $this->getFormatter($schema, $property);

            return $formatter();
        }

        return $this->getRandomNumber($schema);
    }

    /**
     * Generate a number
     *
     * @param stdClass $schema
     * @param bool $required
     * @param string $property
     * @return float|int|null
     * @throws InvalidArgumentException
     */
    private function handleNumber(stdClass $schema, $required, $property = null)
    {
        if (!$this->shouldHandle($required)) {
            return null;
        }

        $enum = $this->getEnum($schema);
        if (null !== $enum) {
            return $enum;
        }

        if ($this->hasFormatter($schema, $property)) {
            $formatter = $this->getFormatter($schema, $property);

            return $formatter();
        }

        return $this->getRandomNumber($schema, true);
    }

    /**
     * Generate a string
     *
     * @param $schema
     * @param $required
     * @param null $property
     * @return null|string
     * @throws InvalidArgumentException
     */
    private function handleString($schema, $required, $property = null)
    {
        if (!$this->shouldHandle($required)) {
            return null;
        }

        $enum = $this->getEnum($schema);
        if (null !== $enum) {
            return $enum;
        }

        if ($this->provider->hasProperty($schema, 'pattern')) {
            $pattern = $this->provider->getProperty($schema, 'pattern');

            return $this->generator->regexify($pattern);
        }

        if ($this->hasFormatter($schema, $property)) {
            $formatter = $this->getFormatter($schema, $property);

            return $formatter();
        }

        $maxLength = $this->provider->getProperty($schema, 'maxLength', $this->config->getMaxLength());
        $minLength = $this->provider->getProperty($schema, 'minLength', $this->config->getMinLength());

        $text = $this->generator->text($maxLength);

        while (strlen($text) < $minLength) {
            $text .= $this->generator->randomLetter;
        }

        return $text;
    }

    /**
     * Generate boolean value
     *
     * @param stdClass $schema
     * @param bool $required
     * @param string $property
     * @return bool|null
     * @throws InvalidArgumentException
     */
    private function handleBoolean(stdClass $schema, $required, $property = null)
    {
        if (!$this->shouldHandle($required)) {
            return null;
        }

        $enum = $this->getEnum($schema);
        if (null !== $enum) {
            return $enum;
        }

        if ($this->hasFormatter($schema, $property)) {
            $formatter = $this->getFormatter($schema, $property);

            return $formatter();
        }

        return $this->generator->boolean();
    }

    /**
     * If we should generate data for a non-required field
     *
     * @param bool $required
     * @return bool
     */
    private function shouldHandle($required)
    {
        if (!$required) {
            $required = $this->generator->boolean($this->config->getChanceRequired());
        }

        return $required;
    }

    /**
     * Get a random enum value
     *
     * @param stdClass $schema
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function getEnum(stdClass $schema)
    {
        if (!$this->provider->hasProperty($schema, 'enum')) {
            return null;
        }

        $enum = $this->provider->getProperty($schema, 'enum');
        $index = $this->generator->numberBetween(0, count($enum) - 1);

        return $enum[$index];
    }

    /**
     * Get a random number, float or integer
     *
     * @param stdClass $schema
     * @param bool $float
     * @return float|int
     * @throws InvalidArgumentException
     */
    private function getRandomNumber(stdClass $schema, $float = false)
    {
        $multipleOf = $this->provider->getProperty($schema, 'multipleOf', $this->config->getMultipleOf());
        $maximum = $this->provider->getProperty($schema, 'maximum', $this->config->getMaximum());
        $minimum = $this->provider->getProperty($schema, 'minimum', $this->config->getMinimum());
        $exclusiveMaximum = $this->provider->getProperty($schema, 'exclusiveMaximum', false);
        $exclusiveMinimum = $this->provider->getProperty($schema, 'exclusiveMinimum', false);

        if (true === $exclusiveMaximum) {
            $maximum--;
        }

        if (true === $exclusiveMinimum) {
            $maximum++;
        }

        $decimals = $float ? $this->generator->numberBetween(0, 4) : 0;
        $modifier = 1 / pow(10, $decimals);

        $number = $float
            ? $this->generator->randomFloat($decimals, $minimum, $maximum)
            : $this->generator->numberBetween($minimum, $maximum);

        while ($number % $multipleOf !== 0) {
            $number += $modifier;
        }

        return $number;
    }

    /**
     * Check if a formatter exists by format key or property name
     *
     * @param stdClass $schema
     * @param string $property
     * @return bool
     */
    private function hasFormatter(stdClass $schema, $property = null)
    {
        if ($this->provider->hasProperty($schema, 'format')) {
            $format = $this->provider->getProperty($schema, 'format');

            if (array_key_exists($format, self::$map)) {
                $format = self::$map[$format];
            }

            try {
                $this->generator->getFormatter($format);

                return true;
            } catch (InvalidArgumentException $exception) { }
        }


        if (null !== $property) {
            try {
                $this->generator->getFormatter($property);

                return true;
            } catch (InvalidArgumentException $exception) { }
        }


        return false;
    }

    /**
     * Get a faker formatter
     *
     * @param $schema
     * @param null $property
     * @return Callable
     * @throws InvalidArgumentException
     */
    private function getFormatter($schema, $property = null)
    {
        if ($this->provider->hasProperty($schema, 'format')) {
            $format = $this->provider->getProperty($schema, 'format');

            if (array_key_exists($format, self::$map)) {
                $format = self::$map[$format];
            }

            return $this->generator->getFormatter($format);
        }

        if (null !== $property) {
            return $this->generator->getFormatter($property);
        }

        throw new InvalidArgumentException('Formatter could not be found');
    }
}
