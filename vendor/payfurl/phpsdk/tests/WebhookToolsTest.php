<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/TestBase.php");
require_once(__DIR__ . "/../src/tools/WebhookTools.php");

final class WebhookToolsTest extends TestBase
{
    private const TEST_WEBHOOK_SIGNATURE_KEY = "dCM6l9ngZMJXVappk73yS607k1K7byfyzTTdToaKMa8=";
    private const TEST_X_PAYFURL_SIGNATURE = "rDYP2MxMKvvmoV2KrbOi4pnelHnVJoFYdBegvCK7IQk=";
    private const TEST_REQUEST_BODY = '{"data":{"chargeId":"3f83ab8fdf624c649bc70bbba81d6c2b","providerChargeId":"ch_3MYd2tE9mXU4onpB0r5iTsiL","amount":20,"providerId":"a26c371f-94f6-40da-add2-28ec8e9da8ed","paymentInformation":{"paymentMethodId":"80da8c2d674b4d2e8c65a6520e89d070","card":{"cardNumber":"4111********1111","expiryDate":"12/25","type":"VISA","cardType":"CREDIT","cardIin":"411"},"type":"CARD"},"customerId":"025c73d9cd0540e9a5a997f8ba97c732","status":"SUCCESS","dateAdded":"2023-02-06T22:20:19.0461561Z","successDate":"2023-02-06T22:20:20.8655832Z","estimatedCost":0.20,"estimatedCostCurrency":"AUD","currency":"Aud","refunds":[],"threeDsVerified":false},"meta":{"messageId":"bc4f056315d6e0205ab085dde45c4a46","timestamp":"2023-01-19T20:37:12.8456589Z","type":"transaction","eventType":"transaction.status.changed"}}';

    public function testDeserializeTransactionSuccessfully(): void
    {
        $result = WebhookTools::DeserializeTransaction(self::TEST_REQUEST_BODY, self::TEST_X_PAYFURL_SIGNATURE, self::TEST_WEBHOOK_SIGNATURE_KEY);

        $this->assertNotNull($result->data);
        $this->assertNotNull($result->meta);
        $this->assertIsString($result->data->chargeId);
    }

    public function testDeserializeTransactionWithException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Request body is not from PayFURL");

        WebhookTools::DeserializeTransaction("", "InvalidSignature", self::TEST_WEBHOOK_SIGNATURE_KEY);
    }
}