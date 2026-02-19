<?php

namespace Linkedcode\NotEnv\Exception;

use RuntimeException;

final class ConfigNotFoundException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Config key [$key] not found.");
    }
}
