<?php

namespace payFURL\Sdk;

class CaseConverter
{
    /**
     * Converts camelCase keys in an array to PascalCase.
     */
    public static function convertKeysToPascalCase(array $params): array
    {
        $converted = [];
        foreach ($params as $key => $value) {
            $pascalKey = ucfirst(preg_replace_callback('/_([a-z])/', fn ($matches) => strtoupper($matches[1]), $key));
            $converted[$pascalKey] = is_array($value) ? self::convertKeysToPascalCase($value) : $value;
        }
        return $converted;
    }
}
