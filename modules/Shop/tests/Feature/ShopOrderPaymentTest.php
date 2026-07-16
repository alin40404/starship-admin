<?php

namespace Modules\Shop\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use Modules\Shop\App\Models\Order;
use Modules\Shop\App\Models\Product;
use Modules\Shop\App\Services\OrderService;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Contracts\Payment\PaymentInterface;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

/**
 * Shop 支付闭环：无 pdo_sqlite 时回退 .env 的 mysql；表未迁移则跳过写库用例。
 */
class ShopOrderPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $connection = $this->dotenvValue('DB_CONNECTION') ?: 'mysql';
            if ($connection === 'sqlite') {
                $connection = 'mysql';
            }

            config([
                'database.default' => $connection,
                "database.connections.{$connection}.database" => $this->dotenvValue('DB_DATABASE') ?: 'starship',
                "database.connections.{$connection}.host" => $this->dotenvValue('DB_HOST') ?: '127.0.0.1',
                "database.connections.{$connection}.port" => $this->dotenvValue('DB_PORT') ?: '3306',
                "database.connections.{$connection}.username" => $this->dotenvValue('DB_USERNAME') ?: 'root',
                "database.connections.{$connection}.password" => $this->dotenvValue('DB_PASSWORD') ?? '',
            ]);
            DB::purge();
            DB::reconnect();
        }
    }

    protected function dotenvValue(string $key): ?string
    {
        $path = base_path('.env');
        if (! is_file($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            if (trim($name) !== $key) {
                continue;
            }

            return trim($value, " \t\"'");
        }

        return null;
    }

    #[Test]
    public function shop_module_is_enabled(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('shop', $names);
    }

    #[Test]
    public function order_flow_prepay_notify_and_refund(): void
    {
        if (! $this->shopTablesReady()) {
            $this->markTestSkipped('shop 表不可用，请先 php artisan migrate。');
        }

        $payment = Mockery::mock(PaymentInterface::class);
        $payment->shouldReceive('prepay')
            ->once()
            ->andReturn([
                'provider' => 'wechat',
                'mode' => 'sandbox',
                'prepay_id' => 'sandbox_prepay_demo',
                'status' => 'prepay_created',
            ]);
        $payment->shouldReceive('refund')
            ->once()
            ->andReturn([
                'provider' => 'wechat',
                'status' => 'refunded',
            ]);
        $this->app->instance(PaymentInterface::class, $payment);

        $product = Product::query()->create([
            'name' => '测试商品_'.substr(md5((string) microtime(true)), 0, 6),
            'price' => 1000,
            'stock' => 5,
            'status' => 1,
        ]);

        try {
            /** @var OrderService $service */
            $service = $this->app->make(OrderService::class);
            $order = $service->createFromProduct((int) $product->id, 2);

            $this->assertSame(Order::STATUS_PENDING, $order->status);
            $this->assertSame(2000, $order->amount);

            $prepay = $service->prepay($order);
            $this->assertSame('wechat', $prepay['order']->pay_channel);
            $this->assertSame('sandbox_prepay_demo', $prepay['order']->prepay_id);

            $paid = $service->handleNotify($order->order_no, [
                'status' => 'success',
                'trade_no' => 'T123',
                'provider' => 'wechat',
            ]);
            $this->assertSame(Order::STATUS_PAID, $paid->status);
            $this->assertSame('T123', $paid->trade_no);
            $this->assertSame(3, $product->fresh()->stock);

            $again = $service->handleNotify($order->order_no, ['status' => 'success']);
            $this->assertSame(Order::STATUS_PAID, $again->status);
            $this->assertSame(3, $product->fresh()->stock);

            $refund = $service->refund($paid->fresh());
            $this->assertSame(Order::STATUS_REFUNDED, $refund['order']->status);
            $this->assertSame(5, $product->fresh()->stock);

            Order::query()->whereKey($order->id)->forceDelete();
        } finally {
            Product::query()->whereKey($product->id)->forceDelete();
        }
    }

    #[Test]
    public function payment_notify_endpoint_marks_order_paid(): void
    {
        if (! $this->shopTablesReady()) {
            $this->markTestSkipped('shop 表不可用，请先 php artisan migrate。');
        }

        $product = Product::query()->create([
            'name' => '回调商品_'.substr(md5((string) microtime(true)), 0, 6),
            'price' => 500,
            'stock' => 2,
            'status' => 1,
        ]);

        $order = Order::query()->create([
            'order_no' => 'S'.now()->format('YmdHis').Str::upper(Str::random(6)),
            'product_id' => $product->id,
            'product_name' => $product->name,
            'amount' => 500,
            'quantity' => 1,
            'status' => Order::STATUS_PENDING,
        ]);

        try {
            $this->postJson('/api/shop/payments/notify', [
                'order_no' => $order->order_no,
                'status' => 'success',
                'trade_no' => 'NOTIFY001',
                'provider' => 'wechat',
            ])
                ->assertOk()
                ->assertJsonPath('data.order_no', $order->order_no)
                ->assertJsonPath('data.status', Order::STATUS_PAID);

            $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
            $this->assertSame(1, $product->fresh()->stock);
        } finally {
            Order::query()->whereKey($order->id)->forceDelete();
            Product::query()->whereKey($product->id)->forceDelete();
        }
    }

    protected function shopTablesReady(): bool
    {
        try {
            return Schema::hasTable('shop_products') && Schema::hasTable('shop_orders');
        } catch (\Throwable) {
            return false;
        }
    }
}
