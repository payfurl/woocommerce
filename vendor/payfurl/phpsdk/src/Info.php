<?php

namespace payFURL\Sdk;

require_once(__DIR__ . '/tools/HttpWrapper.php');
require_once(__DIR__ . '/tools/ArrayTools.php');
require_once(__DIR__ . '/tools/UrlTools.php');
require_once(__DIR__ . '/tools/CaseConverter.php');

/**
 * @copyright PayFURL
 */
class Info
{
    /**
     * @throws ResponseException
     */
    public function Info()
    {
        return HttpWrapper::CallApi('/info', 'GET', '');
    }

    /**
     * @throws ResponseException
     */
    public function Providers($params)
    {
        $params = CaseConverter::convertKeysToPascalCase($params);
        try {
            $url = '/info/providers' . UrlTools::CreateQueryString($params, ['Amount', 'Currency']);
        } catch (\Exception $ex) {
            throw new ResponseException($ex->getMessage(), 0, 0, false);
        }

        return HttpWrapper::CallApi($url, 'GET', '', ['sdk-version' => '4.5.7']);
    }

    /**
     * @throws ResponseException
     */
    public function DefaultFallback()
    {
        return HttpWrapper::CallApi('/info/default_fallback_provider', 'GET', '');
    }
}
