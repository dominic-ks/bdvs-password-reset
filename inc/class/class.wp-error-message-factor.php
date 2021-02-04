<?php

class BDPWR_WP_Error_Message_Factory {
  public static function handle( Exception $e , string $registry ) {
    $errorMessage = $e->getMessage();
    $messages = $registry::ERROR_MESSAGES;

    $messageExists = isset( $messages[$errorMessage] );
    $message = $messageExists ? $messages[$errorMessage] : $errorMessage;
    $code = $messageExists ? $errorMessage : 'bad_request';
    $data =  array( 'status' => $e->getCode() );

    return new WP_Error( $code , $message , $data );
   }
}
