<?php

/**
*
* Add an endpoint to set a new password
*
**/

add_action( 'rest_api_init', function () {  
  $route_namespace = apply_filters( 'bdpwr_route_namespace' , 'bdpwr/v1' );
  register_rest_route( $route_namespace , '/set-password' , array(

    'methods' => 'POST',

    'callback' => function( $data ) {

      if ( empty( $data['email'] ) || $data['email'] === '' ) {
        return new WP_Error( 'no_email' , __( 'You must provide an email address.' , 'bdvs-password-reset' ) , array( 'status' => 400 ));
      }

      if( empty( $data['code'] ) || $data['code'] === '' ) {
        return new WP_Error( 'no_code' , __( 'You must provide a code.' , 'bdvs-password-reset' ) , array( 'status' => 400 ) );
      }

      if( empty( $data['password'] ) || $data['password'] === '' ) {
        return new WP_Error( 'no_code' , __( 'You must provide a new password.' , 'bdvs-password-reset' ) , array( 'status' => 400 ) );
      }

      $exists = email_exists( $data['email'] );

      if( ! $exists ) {
        return new WP_Error( 'bad_email' , __( 'No user found with this email address.' , 'bdvs-password-reset' ) , array( 'status' => 500 ));
      }
      
      try {
        $user = bdpwr_get_user( $exists );
        $user->set_new_password( $data['code'] , $data['password'] );
      }
      
      catch( Exception $e ) {
        return new WP_Error( 'bad_request' , $e->getMessage() , array( 'status' => 500 ));
      }

      return array(
        'data' => array(
          'status' => 200,
        ),
        'message' => __( 'Password reset successfully.' , 'bdvs-password-reset' ),
      );

    },

    'permission_callback' => function() {
      return true;
    },

  ));  
});
