<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker\Enum;

use Tebru\Enum\AbstractEnum;

/**
 * Class SchemaType
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SchemaType extends AbstractEnum
{
    const BOOLEAN = 'boolean';
    const INTEGER = 'integer';
    const NUMBER = 'number';
    const STRING = 'string';
    const ARRY = 'array';
    const OBJECT = 'object';

    /**
     * Return an array of enum class constants
     *
     * @return array
     */
    public static function getConstants()
    {
        return [
            self::BOOLEAN,
            self::INTEGER,
            self::NUMBER,
            self::STRING,
            self::ARRY,
            self::OBJECT,
        ];
    }
}
