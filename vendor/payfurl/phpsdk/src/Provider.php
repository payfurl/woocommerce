<?php

namespace payFURL\Sdk;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Provider
{
    /**
     * @throws ResponseException
     */
    public function Create($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, ['Type', 'Name', 'Environment', 'Currency', 'AuthenticationParameters']);

        $sourceParams = ['Type' => 1, 'Name' => 1, 'Environment' => 1, 'Currency' => 1, 'AuthenticationParameters' => 1, 'ProviderCountry' => 1];
        $data = array_intersect_key($params, $sourceParams);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/provider', 'POST', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Update($providerId, $params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        ArrayTools::ValidateKeys($params, []);

        $sourceParams = ['Name' => 1, 'Currency' => 1, 'AuthenticationParameters' => 1, 'ProviderCountry' => 1];
        $data = array_intersect_key($params, $sourceParams);

        $data = ArrayTools::CleanEmpty($data);

        return HttpWrapper::CallApi('/provider/' . $providerId, 'PUT', json_encode($data));
    }

    /**
     * @throws ResponseException
     */
    public function Delete($providerId)
    {
        return HttpWrapper::CallApi('/provider/' . $providerId, 'DELETE', '');
    }
}
