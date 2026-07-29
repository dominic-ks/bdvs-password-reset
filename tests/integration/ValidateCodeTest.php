<?php
declare(strict_types=1);

namespace BDPWR\Tests\Integration;

use BDPWR\Tests\Integration\Helpers\WPTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for POST /wp-json/bdpwr/v1/validate-code
 */
class ValidateCodeTest extends TestCase
{
    private WPTestHelper $wp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wp = new WPTestHelper();
        $this->wp->clearResetCode($this->wp->subscriberId);
    }

    protected function tearDown(): void
    {
        $this->wp->clearResetCode($this->wp->subscriberId);
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Validation errors (no WP state required)
    // -----------------------------------------------------------------------

    public function test_returns_400_when_email_is_missing(): void
    {
        $response = $this->wp->validateCode(['code' => 'SOMECD12']);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_email', $response['body']['code'] ?? null);
    }

    public function test_returns_400_when_code_is_missing(): void
    {
        $response = $this->wp->validateCode(['email' => $this->wp->subscriberEmail]);

        $this->assertSame(400, $response['status']);
        $this->assertSame('no_code', $response['body']['code'] ?? null);
    }

    public function test_returns_500_when_email_does_not_exist(): void
    {
        $response = $this->wp->validateCode([
            'email' => 'ghost@nowhere.invalid',
            'code'  => 'SOMECD12',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_email', $response['body']['code'] ?? null);
    }

    // -----------------------------------------------------------------------
    // Code state errors
    // -----------------------------------------------------------------------

    public function test_returns_500_when_no_reset_code_has_been_requested(): void
    {
        // No reset email was sent, so no code exists in meta
        $response = $this->wp->validateCode([
            'email' => $this->wp->subscriberEmail,
            'code'  => 'NOCODE12',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);
        $this->assertStringContainsStringIgnoringCase(
            'request a password reset code',
            $response['body']['message'] ?? ''
        );
    }

    public function test_returns_500_and_increments_attempt_on_wrong_code(): void
    {
        // Trigger a real reset first so a code exists
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);

        $response = $this->wp->validateCode([
            'email' => $this->wp->subscriberEmail,
            'code'  => 'WRONGCD1',
        ]);

        $this->assertSame(500, $response['status']);
        $this->assertSame('bad_request', $response['body']['code'] ?? null);

        // The attempt counter should have been incremented
        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);
        $this->assertNotNull($stored);
        $this->assertSame(1, $stored['attempt']);
    }

    public function test_deletes_code_meta_after_max_attempts_exceeded(): void
    {
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);

        // Three wrong guesses exhaust the default limit (3)
        for ($i = 0; $i < 3; $i++) {
            $this->wp->validateCode([
                'email' => $this->wp->subscriberEmail,
                'code'  => 'WRONGCD' . $i,
            ]);
        }

        // Meta should have been deleted after the third failure
        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);
        $this->assertNull($stored, 'Reset code meta should be deleted after max attempts');
    }

    // -----------------------------------------------------------------------
    // Success path
    // -----------------------------------------------------------------------

    public function test_returns_200_for_correct_code(): void
    {
        $this->wp->resetPassword(['email' => $this->wp->subscriberEmail]);

        $stored = $this->wp->getStoredResetCode($this->wp->subscriberId);
        $this->assertNotNull($stored, 'Precondition: stored code must exist');

        $response = $this->wp->validateCode([
            'email' => $this->wp->subscriberEmail,
            'code'  => $stored['code'],
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertStringContainsStringIgnoringCase(
            'valid',
            $response['body']['message'] ?? ''
        );
    }
}
