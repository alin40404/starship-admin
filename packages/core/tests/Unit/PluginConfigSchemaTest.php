<?php

namespace Starship\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Plugin\PluginConfigRepository;
use Starship\Core\Plugin\PluginManager;
use Starship\Core\Tests\TestCase;

class PluginConfigSchemaTest extends TestCase
{
    #[Test]
    public function mail_smtp_schema_exposes_typed_fields(): void
    {
        $plugin = $this->app->make(PluginManager::class)->findBySlug('mail-smtp');
        if ($plugin === null) {
            $this->markTestSkipped('MailSmtp 插件不存在');
        }

        $schema = $this->app->make(PluginConfigRepository::class)->schema('mail-smtp');
        $this->assertNotEmpty($schema);

        $byKey = [];
        foreach ($schema as $field) {
            $byKey[$field['key']] = $field;
        }

        $this->assertArrayHasKey('host', $byKey);
        $this->assertSame('string', $byKey['host']['type']);
        $this->assertNotSame('host', $byKey['host']['label']);

        $this->assertArrayHasKey('encryption', $byKey);
        $this->assertSame('select', $byKey['encryption']['type']);
        $this->assertNotEmpty($byKey['encryption']['options']);

        $this->assertArrayHasKey('password', $byKey);
        $this->assertTrue($byKey['password']['encrypted']);
    }

    #[Test]
    public function schema_keys_match_schema_order(): void
    {
        $repo = $this->app->make(PluginConfigRepository::class);
        if ($this->app->make(PluginManager::class)->findBySlug('sms-aliyun') === null) {
            $this->markTestSkipped('SmsAliyun 插件不存在');
        }

        $keys = $repo->schemaKeys('sms-aliyun');
        $schema = $repo->schema('sms-aliyun');
        $this->assertSame(array_column($schema, 'key'), $keys);
    }
}
