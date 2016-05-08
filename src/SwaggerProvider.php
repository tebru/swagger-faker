<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\SwaggerFaker;

use Faker\Provider\Base;
use InvalidArgumentException;
use Tebru\SwaggerFaker\Config\GenerationConfig;
use Tebru\SwaggerFaker\Generator\SchemaGenerator;
use Tebru\SwaggerFaker\Provider\SchemaProvider;

/**
 * Class SwaggerProvider
 *
 * @author Nate Brunette <n@tebru.net>
 */
class SwaggerProvider extends Base
{
    /**
     * Create fake json based on a swagger schema
     *
     * @param string $swaggerFile
     * @param string $path
     * @param string $operation
     * @param int $response
     * @param array $config
     * @return string
     * @throws InvalidArgumentException
     */
    public function swaggerSchema($swaggerFile, $path, $operation, $response, array $config = [])
    {
        $config = new GenerationConfig($config);
        $provider = new SchemaProvider($swaggerFile);
        $generator = new SchemaGenerator($provider, $this->generator, $config);
        $schema = $provider->getSchema($path, $operation, $response);

        if ([] === get_object_vars($schema)) {
            return null;
        }

        return $generator->generate($schema);
    }
}
