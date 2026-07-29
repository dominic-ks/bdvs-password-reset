<?php
declare(strict_types=1);

namespace BDPWR\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Tests for inc/class/class.user.php (BDPWR_User)
 *
 * The WP_User base class is stubbed in bootstrap.php.
 * Brain\Monkey auto-stubs: apply_filters (pass-through), __ (first arg).
 */
class UserClassTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const USER_ID    = 42;
    private const USER_EMAIL = 'testuser@example.com';

    /** @var \BDPWR_User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        $this->user              = new \BDPWR_User(self::USER_ID);
        $this->user->user_email  = self::USER_EMAIL;
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Constructor
    // -----------------------------------------------------------------------

    public function test_constructor_throws_without_user_id(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/must provide a \$user_id/i');

        new \BDPWR_User(0);
    }

    public function test_constructor_sets_user_id(): void
    {
        $user = new \BDPWR_User(99);
        $this->assertSame(99, $user->ID);
    }

    // -----------------------------------------------------------------------
    // send_reset_code
    // -----------------------------------------------------------------------

    public function test_send_reset_code_throws_for_disallowed_role(): void
    {
        $this->user->roles = ['administrator'];

        $this->mockWpRoles(['administrator' => [], 'subscriber' => []]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/cannot request a password reset.*role/i');

        $this->user->send_reset_code();
    }

    public function test_send_reset_code_succeeds_for_subscriber(): void
    {
        $this->user->roles = ['subscriber'];

        $this->mockWpRoles(['administrator' => [], 'subscriber' => []]);
        $this->mockGetOptionForTimezone();

        Functions\when('update_user_meta')->justReturn(1);
        Functions\when('wp_mail')->justReturn(true);

        $result = $this->user->send_reset_code();
        $this->assertTrue($result);
    }

    public function test_send_reset_code_saves_meta_with_code_and_expiry(): void
    {
        $this->user->roles = ['subscriber'];

        $this->mockWpRoles(['administrator' => [], 'subscriber' => []]);
        $this->mockGetOptionForTimezone();

        Functions\expect('update_user_meta')
            ->once()
            ->with(self::USER_ID, 'bdpws-password-reset-code', \Mockery::on(function ($meta) {
                return isset($meta['code'], $meta['expiry'], $meta['attempt'])
                    && is_string($meta['code'])
                    && strlen($meta['code']) === 8
                    && is_int($meta['expiry'])
                    && $meta['expiry'] > time()
                    && $meta['attempt'] === 0;
            }))
            ->andReturn(1);

        Functions\when('wp_mail')->justReturn(true);

        $this->user->send_reset_code();
    }

    public function test_send_reset_code_sends_email_to_user(): void
    {
        $this->user->roles = ['subscriber'];

        $this->mockWpRoles(['administrator' => [], 'subscriber' => []]);
        $this->mockGetOptionForTimezone();

        Functions\when('update_user_meta')->justReturn(1);

        Functions\expect('wp_mail')
            ->once()
            ->with(self::USER_EMAIL, \Mockery::type('string'), \Mockery::type('string'))
            ->andReturn(true);

        $this->user->send_reset_code();
    }

    // -----------------------------------------------------------------------
    // validate_code
    // -----------------------------------------------------------------------

    public function test_validate_code_throws_when_no_stored_meta(): void
    {
        // get_user_meta returns '' (no meta) → private method converts to false
        Functions\when('get_user_meta')->justReturn('');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/must request a password reset code/i');

        $this->user->validate_code('ANYCODE1');
    }

    public function test_validate_code_returns_true_for_correct_non_expired_code(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'VALIDCOD',
            'expiry'  => time() + 900,
            'attempt' => 0,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);

        $result = $this->user->validate_code('VALIDCOD');
        $this->assertTrue($result);
    }

    public function test_validate_code_throws_for_wrong_code(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'RIGHTCOD',
            'expiry'  => time() + 900,
            'attempt' => 0,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);
        Functions\when('update_user_meta')->justReturn(1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/reset code provided is not valid/i');

        $this->user->validate_code('WRONGCOD');
    }

    public function test_validate_code_increments_attempt_counter_on_wrong_code(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'RIGHTCOD',
            'expiry'  => time() + 900,
            'attempt' => 1,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);

        Functions\expect('update_user_meta')
            ->once()
            ->with(self::USER_ID, 'bdpws-password-reset-code', \Mockery::on(function ($meta) {
                return $meta['attempt'] === 2;
            }))
            ->andReturn(1);

        try {
            $this->user->validate_code('WRONGCOD');
        } catch (\Exception $e) {
            // expected
        }
    }

    public function test_validate_code_deletes_meta_when_max_attempts_exceeded(): void
    {
        // attempt is already at 2 (0-indexed), so the next wrong guess hits the limit of 3
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'RIGHTCOD',
            'expiry'  => time() + 900,
            'attempt' => 2,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);
        Functions\when('update_user_meta')->justReturn(1);

        Functions\expect('delete_user_meta')
            ->once()
            ->with(self::USER_ID, 'bdpws-password-reset-code')
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/maximum number of attempts/i');

        $this->user->validate_code('WRONGCOD');
    }

    public function test_validate_code_throws_for_expired_code(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'EXPIRCOD',
            'expiry'  => time() - 1,   // one second in the past
            'attempt' => 0,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);
        Functions\when('delete_user_meta')->justReturn(true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/has expired/i');

        $this->user->validate_code('EXPIRCOD');
    }

    public function test_validate_code_unlimited_attempts_when_max_is_minus_one(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'RIGHTCOD',
            'expiry'  => time() + 900,
            'attempt' => 99,  // many prior attempts, should not delete meta
        ]);

        // Override the max_attempts filter to return -1 (unlimited)
        Filters\expectApplied('bdpwr_max_attempts')->once()->andReturn(-1);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/reset code provided is not valid/i');

        // With unlimited attempts, no delete_user_meta should be called
        Functions\expect('delete_user_meta')->never();

        $this->user->validate_code('WRONGCOD');
    }

    // -----------------------------------------------------------------------
    // set_new_password
    // -----------------------------------------------------------------------

    public function test_set_new_password_calls_wp_set_password_on_valid_code(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'VALIDCOD',
            'expiry'  => time() + 900,
            'attempt' => 0,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);
        Functions\when('delete_user_meta')->justReturn(true);

        Functions\expect('wp_set_password')
            ->once()
            ->with('NewPass123!', self::USER_ID)
            ->andReturnNull(); // wp_set_password returns void/null

        $this->user->set_new_password('VALIDCOD', 'NewPass123!');
    }

    public function test_set_new_password_throws_for_invalid_code(): void
    {
        Functions\when('get_user_meta')->justReturn(''); // no stored meta

        $this->expectException(\Exception::class);

        $this->user->set_new_password('BADCODE1', 'NewPass123!');
    }

    public function test_set_new_password_deletes_meta_after_setting_password(): void
    {
        Functions\when('get_user_meta')->justReturn([
            'code'    => 'VALIDCOD',
            'expiry'  => time() + 900,
            'attempt' => 0,
        ]);
        Functions\when('apply_filters')->alias(fn($hook, $value) => $value);

        Functions\expect('delete_user_meta')
            ->once()
            ->with(self::USER_ID, 'bdpws-password-reset-code')
            ->andReturn(true);

        Functions\when('wp_set_password')->justReturn(null);

        $this->user->set_new_password('VALIDCOD', 'NewPass123!');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function mockWpRoles(array $roles): void
    {
        $obj        = new \stdClass();
        $obj->roles = $roles;
        Functions\when('wp_roles')->justReturn($obj);
    }

    private function mockGetOptionForTimezone(): void
    {
        Functions\when('get_option')->alias(function (string $option) {
            return $option === 'timezone_string' ? 'UTC' : '0';
        });
    }
}
