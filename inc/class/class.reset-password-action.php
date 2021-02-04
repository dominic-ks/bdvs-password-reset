<?php

class BDPWR_Reset_Password_Action
{
  public static function handle( array $data ) {
    static::validateEmail( $data );
    static::sendResetCode( $data['email'] );
  }

  protected static function validateEmail( array $data ) {
    if ( empty( $data['email'] ) || $data['email'] === '' ) {
      throw new InvalidArgumentException( 'no_email' , 400 );
    }
  }

  protected static function sendResetCode( string $email ) {
    $user = static::getUserByEmail( $email );
    $user->send_reset_code();
  }

  protected static function getUserByEmail( string $email ) {
    $userId = email_exists( $email );

    if ( ! $userId ) {
      throw new InvalidArgumentException( 'bad_email' , 500 );
    }

    return bdpwr_get_user( $userId );
  }
}