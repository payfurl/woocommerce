<?php

namespace payFURL\Sdk;

/**
 * @copyright PayFURL
 */

use Exception;

class UrlTools
{
    static function AddItem($querystring, $key, $value): string
    {
        if ($querystring == "") {
            $querystring = "?";
        }
        else {
            $querystring = $querystring . "&";
        }

        return $querystring . $key . "=" . urlencode($value);
    }

    /**
     * @throws Exception
     */
    static function CreateQueryString($queryParameters, $validParameters = null): string
    {
        // check keys are valid
        if (!is_null($validParameters)) {
            $validParameters = $search_array = array_map('strtolower', $validParameters);
            foreach ($queryParameters as $key => $value) {
                if (!in_array(strtolower($key), $validParameters)) {
                    throw new Exception("Invalid Parameter: " . $key);
                }
            }
        }

        $querystring = "";
        foreach ($queryParameters as $key => $value) {
            $querystring = UrlTools::AddItem($querystring, $key, $value);
        }
        return $querystring;
    }
}
