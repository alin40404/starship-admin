<?php

namespace Modules\Member\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Member\App\Models\Member;
use PHPUnit\Framework\Attributes\Test;
use Starship\Core\Module\ModuleManager;
use Tests\TestCase;

/**
 * Member 模块冒烟：本机若无 pdo_sqlite，则写库用例切回 mysql 连接。
 */
class MemberModuleSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml 默认 sqlite；本机无 pdo_sqlite 时回退 .env 的 mysql
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
    public function member_module_is_enabled(): void
    {
        $names = app(ModuleManager::class)
            ->enabled()
            ->map(static fn ($module) => strtolower($module->name()))
            ->values()
            ->all();

        $this->assertContains('member', $names);
    }

    #[Test]
    public function member_login_page_is_ok(): void
    {
        $this->get('/member/login')
            ->assertOk()
            ->assertSee('会员登录', false);
    }

    #[Test]
    public function member_register_page_is_ok(): void
    {
        $this->get('/member/register')
            ->assertOk()
            ->assertSee('会员注册', false);
    }

    #[Test]
    public function member_can_register_login_and_account(): void
    {
        if (! $this->membersTableReady()) {
            $this->markTestSkipped('members 表不可用，跳过会员写库冒烟。');
        }

        $username = 'demo_'.substr(md5((string) microtime(true)), 0, 8);

        $this->post('/member/register', [
            'username' => $username,
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
            'nickname' => 'Demo',
        ])->assertRedirect(route('member.account'));

        $this->assertAuthenticated('member');

        $this->get('/member/account')
            ->assertOk()
            ->assertSee($username, false);

        $this->post('/member/logout')->assertRedirect(route('member.login'));
        $this->assertGuest('member');

        Member::query()->where('username', $username)->forceDelete();
    }

    #[Test]
    public function disabled_member_cannot_login(): void
    {
        if (! $this->membersTableReady()) {
            $this->markTestSkipped('members 表不可用，跳过会员写库冒烟。');
        }

        $username = 'disabled_'.substr(md5((string) microtime(true)), 0, 8);

        Member::query()->create([
            'username' => $username,
            'password' => 'secret12',
            'nickname' => 'X',
            'status' => 0,
        ]);

        try {
            $this->from('/member/login')
                ->post('/member/login', [
                    'account' => $username,
                    'password' => 'secret12',
                ])
                ->assertRedirect('/member/login')
                ->assertSessionHasErrors('account');

            $this->assertGuest('member');
        } finally {
            Member::query()->where('username', $username)->forceDelete();
        }
    }

    protected function membersTableReady(): bool
    {
        try {
            Member::query()->limit(1)->get();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
