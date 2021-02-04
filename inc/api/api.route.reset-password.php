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

    'callback' => function( WP_REST_Request $data ) {
      try {
        BDPWR_Reset_Password_Action::handle( $data->get_body_params() );
      } 
      
      catch( Exception $e ) {
        return BDPWR_WP_Error_Message_Factory::handle( $e , BDPWR_Error_Message_Registry::class );
      }

      return BDPWR_Response_Repository::handle( 200 , 'A password reset email has been sent to your email address.' );
    },

    'permission_callback' => function() {
      return true;
    },

  ));  
});
