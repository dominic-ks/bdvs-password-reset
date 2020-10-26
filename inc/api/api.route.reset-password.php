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
      try {
        Reset_Password_Action::handle( $data['email'] );
      } 
      
      catch( Exception $e ) {
        return WP_Error_Message_Factory::handle( $e , Error_Message_Registry::class );
      }

      return Response_Repository::handle( 200 , 'A password reset email has been sent to your email address.' );
    },

    'permission_callback' => function() {
      return true;
    },

  ));  
});
