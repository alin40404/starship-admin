<?php

namespace Starship\Core\Tests\Unit;

use Starship\Core\Support\StaticAssetResponse;
use Starship\Core\Tests\TestCase;

class StaticAssetResponseTest extends TestCase
{
    public function test_mime_type_for_common_extensions(): void
    {
        $this->assertSame('text/css; charset=UTF-8', StaticAssetResponse::mimeTypeFor('/a/app.css'));
        $this->assertSame('text/javascript; charset=UTF-8', StaticAssetResponse::mimeTypeFor('/a/app.js'));
        $this->assertSame('image/svg+xml', StaticAssetResponse::mimeTypeFor('/a/logo.svg'));
        $this->assertSame('application/octet-stream', StaticAssetResponse::mimeTypeFor('/a/unknown.bin'));
    }
}
