<?php
/**
 * Tests for REST API endpoints.
 */

class BDPWR_Api_Endpoints_Test extends WP_UnitTestCase {
    /**
     * User ID created for testing.
     *
     * @var int
     */
    protected static $user_id;

    /**
     * Set up user fixtures.
     *
     * @param WP_UnitTest_Factory $factory Factory instance.
     */
    public static function wpSetUpBeforeClass( $factory ) {
        self::$user_id = $factory->user->create(
            array(
                'user_email' => 'jane.doe@example.com',
                'user_login' => 'jane.doe',
                'role'       => 'subscriber',
            )
        );
    }

    public function set_up(): void {
        parent::set_up();

        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        do_action( 'rest_api_init' );
    }

    public function tear_down(): void {
        global $wp_rest_server;
        $wp_rest_server = null;

        parent::tear_down();
    }

    public function test_reset_password_requires_email(): void {
        $response = $this->dispatch_request( 'POST', '/bdpwr/v1/reset-password' );
        $data     = $response->get_data();

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'no_email', $data['code'] );
    }

    public function test_reset_password_rejects_unknown_email(): void {
        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/reset-password',
            array( 'email' => 'missing@example.com' )
        );
        $data     = $response->get_data();

        $this->assertSame( 500, $response->get_status() );
        $this->assertSame( 'bad_email', $data['code'] );
    }

    public function test_reset_password_sends_code_for_valid_user(): void {
        $user     = get_user_by( 'id', self::$user_id );
        $email    = $user->user_email;
        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/reset-password',
            array( 'email' => $email )
        );
        $meta     = get_user_meta( self::$user_id, 'bdpws-password-reset-code', true );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'A password reset email has been sent to your email address.', $response->get_data()['message'] );
        $this->assertIsArray( $meta );
        $this->assertArrayHasKey( 'code', $meta );
    }

    public function test_set_password_requires_code_and_password(): void {
        $email    = get_user_by( 'id', self::$user_id )->user_email;
        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/set-password',
            array( 'email' => $email )
        );
        $data     = $response->get_data();

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'no_code', $data['code'] );
    }

    public function test_set_password_updates_password_with_valid_code(): void {
        $code    = 'RESET123';
        $email   = get_user_by( 'id', self::$user_id )->user_email;
        $expiry  = strtotime( '+10 minutes' );
        $payload = array(
            'code'    => $code,
            'expiry'  => $expiry,
            'attempt' => 0,
        );

        update_user_meta( self::$user_id, 'bdpws-password-reset-code', $payload );

        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/set-password',
            array(
                'email'    => $email,
                'code'     => $code,
                'password' => 'new-password-123',
            )
        );
        $user     = get_user_by( 'id', self::$user_id );

        $this->assertSame( 200, $response->get_status() );
        $this->assertTrue( wp_check_password( 'new-password-123', $user->user_pass, $user->ID ) );
        $this->assertEmpty( get_user_meta( self::$user_id, 'bdpws-password-reset-code', true ) );
    }

    public function test_validate_code_requires_code(): void {
        $email    = get_user_by( 'id', self::$user_id )->user_email;
        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/validate-code',
            array( 'email' => $email )
        );
        $data     = $response->get_data();

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'no_code', $data['code'] );
    }

    public function test_validate_code_accepts_valid_code(): void {
        $code   = 'VALID123';
        $email  = get_user_by( 'id', self::$user_id )->user_email;
        $expiry = strtotime( '+15 minutes' );

        update_user_meta(
            self::$user_id,
            'bdpws-password-reset-code',
            array(
                'code'    => $code,
                'expiry'  => $expiry,
                'attempt' => 0,
            )
        );

        $response = $this->dispatch_request(
            'POST',
            '/bdpwr/v1/validate-code',
            array(
                'email' => $email,
                'code'  => $code,
            )
        );

        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'The code supplied is valid.', $response->get_data()['message'] );
    }

    /**
     * Convenience helper to dispatch REST requests.
     *
     * @param string $method HTTP method.
     * @param string $route  Route path.
     * @param array  $body   Body parameters.
     *
     * @return WP_REST_Response REST response object.
     */
    private function dispatch_request( $method, $route, array $body = array() ) {
        $request = new WP_REST_Request( $method, $route );

        if ( ! empty( $body ) ) {
            $request->set_body_params( $body );
        }

        return rest_do_request( $request );
    }
}
