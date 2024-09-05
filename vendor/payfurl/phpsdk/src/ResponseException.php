<?php
namespace payFURL\Sdk;

/**
 * @copyright PayFURL
 */
final class ResponseException extends \Exception
{
    public $httpCode;
    public $isRetryable;

    function __construct($Message, $Code, $HttpCode, $IsRetryable)
    {
        $this->httpCode = $HttpCode;
        $this->isRetryable = $IsRetryable;
        parent::__construct($Message, $Code);
    }
}
