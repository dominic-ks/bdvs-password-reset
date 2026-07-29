<?php
declare(strict_types=1);

namespace BDPWR\Tests\Integration\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PDO;

/**
 * Shared helper for integration tests.
 *
 * Provides:
 *  - HTTP client pre-configured against the WordPress base URL
 *  - Direct database access to read/manipulate WordPress data (e.g. fetching
 *    the stored reset code without needing to intercept email)
 *  - Convenience wrappers for each plugin endpoint
 */
class WPTestHelper
{
    private Client $http;
    private PDO $pdo;

    public string $subscriberEmail;
    public string $subscriberPassword;
    public int $subscriberId;

    public string $adminEmail;
    public string $adminPassword;
    public int $adminId;

    public function __construct()
    {
        $baseUrl = rtrim(getenv('WP_BASE_URL') ?: 'http://wordpress', '/');

        $this->http = new Client([
            'base_uri'        => $baseUrl,
            'timeout'         => 10,
            'http_errors'     => false,
            'allow_redirects' => true,
        ]);

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            getenv('TEST_DB_HOST') ?: 'db',
            getenv('TEST_DB_NAME') ?: 'wordpress'
        );
        $this->pdo = new PDO(
            $dsn,
            getenv('TEST_DB_USER') ?: 'wordpress',
            getenv('TEST_DB_PASSWORD') ?: 'wordpress',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->subscriberEmail    = getenv('TEST_SUBSCRIBER_EMAIL') ?: 'subscriber@bdpwr.test';
        $this->subscriberPassword = getenv('TEST_SUBSCRIBER_PASSWORD') ?: 'SubscriberPass123!';
        $this->subscriberId       = $this->getUserIdByEmail($this->subscriberEmail);

        $this->adminEmail    = getenv('WP_ADMIN_EMAIL') ?: 'admin@bdpwr.test';
        $this->adminPassword = getenv('WP_ADMIN_PASSWORD') ?: 'AdminPass123!';
        $this->adminId       = $this->getUserIdByEmail($this->adminEmail);
    }

    // -----------------------------------------------------------------------
    // Endpoint wrappers
    // -----------------------------------------------------------------------

    /**
     * POST /wp-json/bdpwr/v1/reset-password
     *
     * @param array<string,string> $payload
     * @return array{status: int, body: array<string,mixed>}
     */
    public function resetPassword(array $payload): array
    {
        return $this->post('/wp-json/bdpwr/v1/reset-password', $payload);
    }

    /**
     * POST /wp-json/bdpwr/v1/validate-code
     *
     * @param array<string,string> $payload
     * @return array{status: int, body: array<string,mixed>}
     */
    public function validateCode(array $payload): array
    {
        return $this->post('/wp-json/bdpwr/v1/validate-code', $payload);
    }

    /**
     * POST /wp-json/bdpwr/v1/set-password
     *
     * @param array<string,string> $payload
     * @return array{status: int, body: array<string,mixed>}
     */
    public function setPassword(array $payload): array
    {
        return $this->post('/wp-json/bdpwr/v1/set-password', $payload);
    }

    // -----------------------------------------------------------------------
    // Database helpers
    // -----------------------------------------------------------------------

    /**
     * Read the stored reset-code meta for a user directly from the database.
     *
     * @return array{code: string, expiry: int, attempt: int}|null
     */
    public function getStoredResetCode(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT meta_value
             FROM wp_usermeta
             WHERE user_id = :uid
               AND meta_key = 'bdpws-password-reset-code'
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $raw = $stmt->fetchColumn();

        if ($raw === false || $raw === '') {
            return null;
        }

        // WordPress stores meta values as PHP serialized strings
        $data = unserialize($raw);
        return is_array($data) ? $data : null;
    }

    /**
     * Delete any stored reset-code meta for a user (test isolation).
     */
    public function clearResetCode(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM wp_usermeta
             WHERE user_id = :uid
               AND meta_key = 'bdpws-password-reset-code'"
        );
        $stmt->execute([':uid' => $userId]);
    }

    /**
     * Read the current password hash for a user from the database.
     */
    public function getUserPasswordHash(int $userId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT user_pass FROM wp_users WHERE ID = :id'
        );
        $stmt->execute([':id' => $userId]);
        return (string) $stmt->fetchColumn();
    }

    /**
     * Retrieve a WordPress user ID by email address.
     */
    public function getUserIdByEmail(string $email): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT ID FROM wp_users WHERE user_email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new \RuntimeException(
                "Test user with email '{$email}' not found in the database. " .
                'Ensure the Docker setup script has run.'
            );
        }

        return (int) $id;
    }

    // -----------------------------------------------------------------------
    // Private
    // -----------------------------------------------------------------------

    /**
     * @param array<string,mixed> $payload
     * @return array{status: int, body: array<string,mixed>}
     */
    private function post(string $path, array $payload): array
    {
        $response = $this->http->post($path, ['json' => $payload]);

        return [
            'status' => $response->getStatusCode(),
            'body'   => json_decode((string) $response->getBody(), true) ?? [],
        ];
    }
}
