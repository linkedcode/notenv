<?php

namespace Linkedcode\NotEnv;

use Linkedcode\NotEnv\Exception\ConfigNotFoundException;

final class Config
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function all(): array
    {
        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->values;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                if (func_num_args() === 2) return $default;
                throw new ConfigNotFoundException($key);
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        try {
            $this->get($key);
            return true;
        } catch (ConfigNotFoundException) {
            return false;
        }
    }
}
