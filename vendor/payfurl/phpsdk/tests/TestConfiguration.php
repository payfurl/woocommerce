<?php

class TestConfiguration
{
    private static array $config = [];
    private static string $fileName = "/config.json";
    private static int $numToken = 0;

    /**
     * @throws Exception
     */
    public static function setUp(): void
    {
        if (!file_exists(__DIR__ . TestConfiguration::$fileName)) {
            throw new Exception("Configuration file not found");
        }

        if (array_key_exists("Environment", TestConfiguration::$config)) {
            return;
        }

        $json = file_get_contents(__DIR__ . TestConfiguration::$fileName);
        TestConfiguration::$config = json_decode($json, true);
    }

    /**
     * @throws Exception
     */
    public static function getEnvironment(): string
    {
        if (!array_key_exists("Environment", TestConfiguration::$config)) {
            throw new Exception("Wrong configuration");
        }

        return TestConfiguration::$config["Environment"];
    }

    /**
     * @throws Exception
     */
    public static function getSecretKey(): string
    {
        if (!array_key_exists("SecretKey", TestConfiguration::$config)) {
            throw new Exception("Wrong configuration");
        }

        return TestConfiguration::$config["SecretKey"];
    }

    /**
     * @throws Exception
     */
    public static function getProviderId(): string
    {
        if (!array_key_exists("ProviderId", TestConfiguration::$config)) {
            throw new Exception("Wrong configuration");
        }

        return TestConfiguration::$config["ProviderId"];
    }

    /**
     * @throws Exception
     */
    public static function getToken(): string
    {
        if (!array_key_exists("Tokens", TestConfiguration::$config)) {
            throw new Exception("Wrong configuration");
        }

        return TestConfiguration::$config["Tokens"][TestConfiguration::$numToken++];
    }
}
