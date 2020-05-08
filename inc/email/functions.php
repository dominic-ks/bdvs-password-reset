<?php

/**
*
* Send a password reset code email
*
* @param $email str the email address to send to
* @param $code the code to send
* @param $expiry int the time that the code will expire
* @return bool true on success false on failure
*
**/

function bdpwr_send_password_reset_code_email( $email = false , $code = false , $expiry = 0 ) {
  
  if( ! $email ) {
    throw new Exception( 'An email address is required for the reset code email.' );
  }
  
  if( ! $code ) {
    throw new Exception( 'No code was provided for the password reset email.' );
  }
  
  ob_start(); ?>

  A password reset was requested for your account and your password reset code is <?php echo $code; ?>.

  <?php if( $expiry !== 0 ) { ?>
    Please note that this code will expire at <?php echo bdpwr_get_formatted_date( $expiry ); ?>.
  <?php } ?>

  <?php
  $text = ob_get_contents();
  if( $text ) { ob_end_clean(); }
  
  /**
  *
  * Filter the subject of the email
  *
  * @param $subject str the subject of the email
  *
  **/
  
  $subject = apply_filters( 'bdpwr_code_email_subject' , 'Password Reset' );
  
  /**
  *
  * Filter the body of the email
  *
  * @param $text str the content of the email
  * @param $email str the email address being sent to
  * @param $code the code being sent
  * @param $expiry int the unix timestamp for the code's expiry
  *
  **/
  
  $text = apply_filters( 'bdpwr_code_email_text' , $text , $email , $code , $expiry );
  
  return wp_mail( $email , $subject , $text );
  
}
