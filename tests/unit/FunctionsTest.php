<?php
declare(strict_types=1);

namespace BDPWR\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Tests for inc/functions.php
 *
 * Brain\Monkey auto-stubs apply_filters (pass-through), __ (returns first arg),
 * add_action, add_filter. All other WP functions must be mocked per-test.
 */
class FunctionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // bdpwr_generate_4_digit_code
    // -----------------------------------------------------------------------

    public function test_generate_code_returns_default_length(): void
    {
        $code = bdpwr_generate_4_digit_code();
        $this->assertSame(8, strlen($code));
    }

    public function test_generate_code_characters_are_from_selection_string(): void
    {
        $code = bdpwr_generate_4_digit_code();
        $selection = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!£$%^&*()_+-={}[]@~\#<>?/|\\';

        for ($i = 0; $i < strlen($code); $i++) {
            $this->assertStringContainsString(
                $code[$i],
                $selection,
                "Character '{$code[$i]}' at position $i not found in selection string"
            );
        }
    }

    public function test_generate_code_respects_filtered_length(): void
    {
        Filters\expectApplied('bdpwr_code_length')->once()->andReturn(12);

        $code = bdpwr_generate_4_digit_code();
        $this->assertSame(12, strlen($code));
    }

    public function test_generate_code_respects_filtered_selection_string(): void
    {
        Filters\expectApplied('bdpwr_code_length')->once()->andReturn(6);
        Filters\expectApplied('bdpwr_selection_string')->once()->andReturn('ABC');

        $code = bdpwr_generate_4_digit_code();

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABC]+$/', $code);
    }

    // -----------------------------------------------------------------------
    // bdpwr_get_new_code_expiration_time
    // -----------------------------------------------------------------------

    public function test_get_expiration_time_returns_future_timestamp(): void
    {
        $before = time();
        $expiry = bdpwr_get_new_code_expiration_time();
        $after  = time();

        $this->assertGreaterThan($before, $expiry, 'Expiry must be in the future');
        // Default is +900 seconds; allow a couple of seconds of slack
        $this->assertLessThanOrEqual($after + 902, $expiry);
        $this->assertGreaterThanOrEqual($before + 898, $expiry);
    }

    public function test_get_expiration_time_respects_filtered_seconds(): void
    {
        Filters\expectApplied('bdpwr_code_expiration_seconds')->once()->andReturn(300);

        $before = time();
        $expiry = bdpwr_get_new_code_expiration_time();

        $this->assertGreaterThanOrEqual($before + 298, $expiry);
        $this->assertLessThanOrEqual($before + 302, $expiry);
    }

    // -----------------------------------------------------------------------
    // bdpwr_get_formatted_date
    // -----------------------------------------------------------------------

    public function test_get_formatted_date_returns_string_for_timestamp(): void
    {
        // wp_timezone_string / wp_timezone are defined by the compat fills in
        // functions.php; wp_timezone_string calls get_option.
        Functions\when('get_option')->alias(function (string $option) {
            if ($option === 'timezone_string') {
                return 'UTC';
            }
            return '0';
        });

        $result = bdpwr_get_formatted_date(mktime(14, 30, 0, 1, 1, 2024));

        $this->assertIsString($result);
        $this->assertSame('14:30', $result);
    }

    public function test_get_formatted_date_respects_format_filter(): void
    {
        Functions\when('get_option')->alias(function (string $option) {
            return $option === 'timezone_string' ? 'UTC' : '0';
        });

        Filters\expectApplied('bdpwd_date_format')->once()->andReturn('d/m/Y');

        $timestamp = mktime(0, 0, 0, 6, 15, 2024);
        $result    = bdpwr_get_formatted_date($timestamp);

        $this->assertSame('15/06/2024', $result);
    }

    // -----------------------------------------------------------------------
    // bdpwr_get_allowed_roles
    // -----------------------------------------------------------------------

    public function test_get_allowed_roles_excludes_administrator(): void
    {
        $rolesObject        = new \stdClass();
        $rolesObject->roles = [
            'administrator' => ['name' => 'Administrator'],
            'editor'        => ['name' => 'Editor'],
            'subscriber'    => ['name' => 'Subscriber'],
        ];
        Functions\when('wp_roles')->justReturn($rolesObject);

        $allowed = bdpwr_get_allowed_roles();

        $this->assertNotContains('administrator', $allowed);
        $this->assertContains('editor', $allowed);
        $this->assertContains('subscriber', $allowed);
    }

    public function test_get_allowed_roles_contains_standard_non_admin_roles(): void
    {
        $rolesObject        = new \stdClass();
        $rolesObject->roles = [
            'administrator' => ['name' => 'Administrator'],
            'editor'        => ['name' => 'Editor'],
            'author'        => ['name' => 'Author'],
            'contributor'   => ['name' => 'Contributor'],
            'subscriber'    => ['name' => 'Subscriber'],
        ];
        Functions\when('wp_roles')->justReturn($rolesObject);

        $allowed = bdpwr_get_allowed_roles();

        foreach (['editor', 'author', 'contributor', 'subscriber'] as $role) {
            $this->assertContains($role, $allowed);
        }
    }

    public function test_get_allowed_roles_can_be_overridden_by_filter(): void
    {
        $rolesObject        = new \stdClass();
        $rolesObject->roles = ['administrator' => [], 'subscriber' => []];
        Functions\when('wp_roles')->justReturn($rolesObject);

        Filters\expectApplied('bdpwr_allowed_roles')->once()->andReturn(['editor']);

        $allowed = bdpwr_get_allowed_roles();

        $this->assertSame(['editor'], $allowed);
    }

    // -----------------------------------------------------------------------
    // bdpwr_send_password_reset_code_email
    // -----------------------------------------------------------------------

    public function test_send_email_throws_when_no_email_provided(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/email address is required/i');

        bdpwr_send_password_reset_code_email(false, 'CODE123');
    }

    public function test_send_email_throws_when_no_code_provided(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/No code was provided/i');

        bdpwr_send_password_reset_code_email('user@example.com', false);
    }

    public function test_send_email_calls_wp_mail(): void
    {
        Functions\when('get_option')->alias(function (string $option) {
            return $option === 'timezone_string' ? 'UTC' : '0';
        });

        Functions\expect('wp_mail')
            ->once()
            ->with('user@example.com', 'Password Reset', \Mockery::type('string'))
            ->andReturn(true);

        $result = bdpwr_send_password_reset_code_email('user@example.com', 'ABCD1234', time() + 900);

        $this->assertTrue($result);
    }

    public function test_send_email_subject_can_be_filtered(): void
    {
        Functions\when('get_option')->alias(function (string $option) {
            return $option === 'timezone_string' ? 'UTC' : '0';
        });

        Filters\expectApplied('bdpwr_code_email_subject')->once()->andReturn('Custom Subject');

        Functions\expect('wp_mail')
            ->once()
            ->with('user@example.com', 'Custom Subject', \Mockery::type('string'))
            ->andReturn(true);

        bdpwr_send_password_reset_code_email('user@example.com', 'CODE1234', time() + 900);
    }
}
