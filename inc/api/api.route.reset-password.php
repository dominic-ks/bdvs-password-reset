<?php

/**
*
* Add an endpoint to reset a password
*
**/

add_action( 'rest_api_init', function () {  
  $route_namespace = apply_filters( 'bdpwr_route_namespace' , 'bdpwr/v1' );
  register_rest_route( $route_namespace , '/reset-password' , array(

    'methods' => 'POST',

    'callback' => function( $data ) {

      if ( empty( $data['email'] ) || $data['email'] === '' ) {
        return new WP_Error( 'no_email' , __( 'You must provide an email address.' , 'bdvs-password-reset' ) , array( 'status' => 400 ));
      }

      $exists = email_exists( $data['email'] );

      if( ! $exists ) {
        return new WP_Error( 'bad_email' , __( 'No user found with this email address.' , 'bdvs-password-reset' ) , array( 'status' => 500 ));
      }
      
      try {
        $user = bdpwr_get_user( $exists );
        $user->send_reset_code();
      }
      
      catch( Exception $e ) {
        return new WP_Error( 'bad_request' , $e->getMessage() , array( 'status' => 500 ));
      }

      return array(
        'data' => array(
          'status' => 200,
        ),
        'message' => __( 'A password reset email has been sent to your email address.' , 'bdvs-password-reset' ),
      );

    },

    'permission_callback' => function() {
      return true;
    },

  ));  
});
