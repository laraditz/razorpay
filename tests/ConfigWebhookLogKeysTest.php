<?php

namespace Laraditz\Razorpay\Tests;

class ConfigWebhookLogKeysTest extends TestCase
{
    public function test_log_webhook_calls_defaults_to_true(): void
    {
        $raw = require __DIR__ . '/../config/config.php';

        $this->assertTrue($raw['log_webhook_calls']);
    }

    public function test_webhook_log_retention_days_defaults_to_30(): void
    {
        $raw = require __DIR__ . '/../config/config.php';

        $this->assertSame(30, $raw['webhook_log_retention_days']);
    }
}
