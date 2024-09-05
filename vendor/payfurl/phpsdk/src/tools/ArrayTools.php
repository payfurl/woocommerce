<?php

namespace payFURL\Sdk;

/**
 * @copyright PayFURL
 */
class ArrayTools
{
    static function CleanEmpty($array)
    {
        foreach ($array as $key => $value) {
            if (!isset($value)) {
                unset($array[$key]);
                continue;
            }
            if (is_array($value)) {
                $array[$key] = ArrayTools::CleanEmpty($value);
            }
        }
        return $array;
    }

    /**
     * @throws ResponseException
     */
    static function ValidateKeys($parameters, $requiredParameters)
    {
        foreach ($requiredParameters as $key => $value) {
            if (is_array($value)) {
                if (!array_key_exists($key, $parameters)) {
                    throw new ResponseException('"' . $key . "' is required", 0, 0, false);
                }
                self::ValidateKeys($parameters[$key], $value);
            }
            else if (!array_key_exists($value, $parameters)) {
                throw new ResponseException('"' . $value . "' is required", 0, 0, false);
            }
            else if (is_null($parameters[$value])) {
                throw new ResponseException('"' . $value . "' is required", 0, 0, false);
            }
        }
    }
}
