<?php
declare(strict_types=1);

namespace BDPWR\Tests\Integration;

use BDPWR\Tests\Integration\Helpers\WPTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for POST /wp-json/bdpwr/v1/set-password
 */
class SetPasswordTest extends TestCase
{
    private WPTestHelper $wp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wp = new WPTestHelper();
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
        $response = $this->wp->setPassword([
            'code'     => 'SOMECD12',
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_email', $response['body']['code'] ?? null);
    }

    public function test_returns_400_when_code_is_missing(): void
    {
        $response = $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_code', $response['body']['code'] ?? null);
    }

    public function test_returns_400_when_password_is_missing(): void
    {
        $response = $this->wp->setPassword([
            'email' => $this->wp->subscriberEmail,
            'code'  => 'SOMECD12',
        ]);

        $this->assertSame(400, $response['status']);
        // Note: the plugin uses 'no_code' as the error code for a missing password —
        // this is a known bug in the plugin (should be 'no_password').
        $this->assertSame('no_code', $response['body']['code'] ?? null);
    }

    public function test_returns_500_when_email_does_not_exist(): void
    {
        $response = $this->wp->setPassword([
            'email'    => 'ghost@nowhere.invalid',
            'code'     => 'SOMECD12',
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_email', $response['body']['code'] ?? null);
    }

    // -----------------------------------------------------------------------
    // Code / role errors
    // -----------------------------------------------------------------------

    public function test_returns_500_when_no_reset_code_was_requested(): void
    {
        $response = $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'code'     => 'NOCODE12',
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);
    }

    public function test_returns_500_for_wrong_code(): void
    {
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);

        $response = $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'code'     => 'WRONGCD1',
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);
    }

    public function test_returns_500_when_admin_tries_to_reset(): void
    {
        // Administrators are excluded from the plugin by default
        $response = $this->wp->setPassword([
            'email'    => $this->wp->adminEmail,
            'code'     => 'SOMECD12',
            'password' => 'NewPass123!',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);
    }

    // -----------------------------------------------------------------------
    // Full happy-path flow
    // -----------------------------------------------------------------------

    public function test_full_flow_returns_200_and_changes_password_hash(): void
    {
        $hashBefore = $this->wp->getUserPasswordHash($this->wp->subscriberId);

        // 1. Request a reset code
        $resetResponse = $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);
        $this->assertSame(200, $resetResponse['status'], 'reset-password should succeed');

        // 2. Read the code directly from the database
        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);
        $this->assertNotNull($stored, 'Reset code must be stored in user meta');

        // 3. Set the new password using the code
        $newPassword  = 'Integration_Test_' . time() . '!';
        $setResponse  = $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'code'     => $stored['code'],
            'password' => $newPassword,
        ]);

        $this->assertSame(200, $setResponse['status']);
        $this->assertStringContainsStringIgnoringCase(
            'password reset successfully',
            $setResponse['body']['message'] ?? ''
        );

        // 4. Verify the password hash changed in the database
        $hashAfter = $this->wp->getUserPasswordHash($this->wp->subscriberId);
        $this->assertNotSame(
            $hashBefore,
            $hashAfter,
            'Password hash should have changed after a successful set-password call'
        );

        // 5. The reset code meta should be gone
        $this->assertNull(
            $this->wp->getStoredResetCode($this->wp->subscriberId),
            'Reset code meta should be deleted after password is set'
        );
    }

    public function test_code_cannot_be_reused_after_set_password(): void
    {
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);
        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);

        // Use the code once
        $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'code'     => $stored['code'],
            'password' => 'FirstNewPass1!',
        ]);

        // Attempt to reuse the same code
        $reuse = $this->wp->setPassword([
            'email'    => $this->wp->subscriberEmail,
            'code'     => $stored['code'],
            'password' => 'SecondNewPass1!',
        ]);

        $this->assertSame(500, $reuse['status'], 'A used code must not be accepted again');
        $this->assertSame('bad_request', $reuse['body']['code'] ?? null);
    }
}
