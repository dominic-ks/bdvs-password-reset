<?php
declare(strict_types=1);

namespace BDPWR\Tests\Integration;

use BDPWR\Tests\Integration\Helpers\WPTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for POST /wp-json/bdpwr/v1/reset-password
 *
 * Requires a running WordPress environment provisioned by bin/setup-wordpress.sh.
 * Run via: make test-integration  (or phpunit --configuration phpunit.integration.xml)
 */
class ResetPasswordTest extends TestCase
{
    private WPTestHelper $wp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wp = new WPTestHelper();
        // Ensure no leftover codes from previous runs
        $this->wp->clearResetCode($this->wp->subscriberId);
        $this->wp->clearResetCode($this->wp->adminId);
    }

    protected function tearDown(): void
    {
        $this->wp->clearResetCode($this->wp->subscriberId);
        $this->wp->clearResetCode($this->wp->adminId);
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Validation errors
    // -----------------------------------------------------------------------

    public function test_returns_400_when_email_is_missing(): void
    {
        $response = $this->wp->resetPassword([]);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_email', $response['body']['code'] ?? null);
    }

    public function test_returns_400_when_email_is_empty_string(): void
    {
        $response = $this->wp->resetPassword(['email' => '']);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_email', $response['body']['code'] ?? null);
    }

    public function test_returns_500_when_email_does_not_exist_in_wordpress(): void
    {
        $response = $this->wp->resetPassword(['email' => 'nobody@nowhere-at-all.invalid']);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_email', $response['body']['code'] ?? null);
    }

    // -----------------------------------------------------------------------
    // Role restrictions
    // -----------------------------------------------------------------------

    public function test_returns_500_when_email_belongs_to_administrator(): void
    {
        // Administrators are excluded from the allowed roles by default
        $response = $this->wp->resetPassword(['email' => $this->wp->adminEmail]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);
        $this->assertStringContainsStringIgnoringCase(
            'role',
            $response['body']['message'] ?? ''
        );
    }

    // -----------------------------------------------------------------------
    // Success path
    // -----------------------------------------------------------------------

    public function test_returns_200_and_stores_reset_code_for_subscriber(): void
    {
        $response = $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsStringIgnoringCase(
            'password reset email',
            $response['body']['message'] ?? ''
        );

        // A reset code must have been stored in user meta
        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);
        $this->assertNotNull($stored, 'Reset code should have been saved in user meta');
        $this->assertArrayHasKey('code', $stored);
        $this->assertArrayHasKey('expiry', $stored);
        $this->assertArrayHasKey('attempt', $stored);
        $this->assertSame(8, strlen($stored['code']));
        $this->assertGreaterThan(time(), $stored['expiry']);
        $this->assertSame(0, $stored['attempt']);
    }

    public function test_reset_replaces_existing_code_on_second_request(): void
    {
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);
        $firstStored = $this->wp->getStoredResetCode($this->wp->subscriberId);

        // Brief pause so we can confirm the code changes (codes are random, so
        // comparison is the reliable signal, but we add a tiny sleep to help)
        usleep(100_000);

        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);
        $secondStored = $this->wp->getStoredResetCode($this->wp->subscriberId);

        $this->assertNotNull($firstStored);
        $this->assertNotNull($secondStored);
        // The new code replaces the old one (same meta key is overwritten)
        $this->assertNotSame(
            $firstStored['code'],
            $secondStored['code'],
            'A second reset request should generate a fresh code'
        );
    }
}
