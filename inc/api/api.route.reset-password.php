<?php

/**
*
* Add an endpoint to reset a password
*
**/

add_action( 'rest_api_init', function () {  
  register_rest_route( 'bdpwr/v1' , '/reset-password' , array(

    'methods' => 'POST',

    'callback' => function( $data ) {

      if ( empty( $data['email'] ) || $data['email'] === '' ) {
        return new WP_Error( 'no_email' , 'You must provide an email address.' , array( 'status' => 400 ));
      }

      $exists = email_exists( $data['email'] );

      if( ! $exists ) {
        return new WP_Error( 'bad_email' , 'No user found with this email address.' , array( 'status' => 500 ));
      }
      
      try {
        $user = bdpwr_get_user( $exists );
        $user->send_reset_code();
      }
      
      catch( Exception $e ) {
        return new WP_Error( 'bad_request' , $e->getMessage() , array( 'status' => 500 ));
      }

      return array(
        'status' => 200,
        'result' => true,
        'message' => 'A password reset email has been sent to your email address.',
      );

    },

    'permission_callback' => function() {
      return true;
    },

  ));  
});
