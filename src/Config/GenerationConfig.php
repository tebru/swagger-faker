<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker\Config;

/**
 * Class GenerationConfig
 *
 * @author Nate Brunette <n@tebru.net>
 */
class GenerationConfig
{
    /**
     * @var int
     */
    private $maxItems = 10;

    /**
     * @var int
     */
    private $minItems = 0;

    /**
     * @var bool
     */
    private $uniqueItems = false;

    /**
     * @var int
     */
    private $multipleOf = 1;

    /**
     * @var int
     */
    private $maximum = 1000000;

    /**
     * @var int
     */
    private $minimum = 0;

    /**
     * @var int
     */
    private $chanceRequired = 80;

    /**
     * @var int
     */
    private $maxLength = 255;

    /**
     * @var int
     */
    private $minLength = 0;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * Set config data
     *
     * @param array $config
     */
    private function setConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * @param int $maxItems
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $maxItems;
    }

    /**
     * @return int
     */
    public function getMinItems()
    {
        return $this->minItems;
    }

    /**
     * @param int $minItems
     */
    public function setMinItems($minItems)
    {
        $this->minItems = $minItems;
    }

    /**
     * @return boolean
     */
    public function isUniqueItems()
    {
        return $this->uniqueItems;
    }

    /**
     * @param boolean $uniqueItems
     */
    public function setUniqueItems($uniqueItems)
    {
        $this->uniqueItems = $uniqueItems;
    }

    /**
     * @return int
     */
    public function getMultipleOf()
    {
        return $this->multipleOf;
    }

    /**
     * @param int $multipleOf
     */
    public function setMultipleOf($multipleOf)
    {
        $this->multipleOf = $multipleOf;
    }

    /**
     * @return int
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * @param int $maximum
     */
    public function setMaximum($maximum)
    {
        $this->maximum = $maximum;
    }

    /**
     * @return int
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * @param int $minimum
     */
    public function setMinimum($minimum)
    {
        $this->minimum = $minimum;
    }

    /**
     * @return int
     */
    public function getChanceRequired()
    {
        return $this->chanceRequired;
    }

    /**
     * @param int $chanceRequired
     */
    public function setChanceRequired($chanceRequired)
    {
        $this->chanceRequired = $chanceRequired;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param int $maxLength
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @param int $minLength
     */
    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;
    }
}
