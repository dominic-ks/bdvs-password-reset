<?php

/**
*
* Generate a random 4 digit code
*
* @param void
* @return str a 4 digit code
*
**/

function bdpwr_generate_4_digit_code() {
  
  /**
  *
  * Filter the length of the code
  *
  * @param $length int the number of digits for the code
  *
  **/
  
  $length = apply_filters( 'bdpwr_code_length' , 4 );
  
  /**
  *
  * Filter whether or not to include letters in the code
  *
  * @param $include boolean
  *
  **/
  
  $include_letters = apply_filters( 'bdpwr_include_letters' , false );
  
  $selection_string = ( $include_letters ) ? '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
  
  /**
  *
  * Filter the selection string to use any characters you like
  *
  * @param $string str the string to select a code from
  *
  **/
  
  $selection_string = apply_filters( 'bdpwr_selection_string' , $selection_string );
  
  return substr( str_shuffle( $selection_string ) , 0 , $length );
  
}


/**
*
* Get new code expiration time
*
* @param void
* @return int the unix timestamp for a code expiry
*
**/

function bdpwr_get_new_code_expiration_time() {
  
  /**
  *
  * Filter the number of seconds codes should be valid for
  * Set -1 for no expiry
  *
  * @param $seconds int the number of seconds the code will be valid for
  *
  **/
  
  $valid_seconds = apply_filters( 'bdpwr_code_expiration_seconds' , 900 );
  $time_string = '+' . $valid_seconds . ' seconds';
  return strtotime( $time_string );
  
}


/**
*
* Get date from unix timestamp
*
* @param $time str the unix timestamp
* @return str the formatted date
*
**/

function bdpwr_get_formatted_date( $time = false ) {
  
  if( ! $time ) {
    $time = strtotime( 'now' );
  }
  
  /**
  *
  * Filter the date format used in this plugin
  *
  * @param $format str the php date format string
  *
  **/
  
  $format = apply_filters( 'bdpwd_date_format' , 'H:i' );
  
  $date = new DateTime();
  $date->setTimestamp( $time );
  $date->setTimezone( wp_timezone());

  return date_format( $date , $format );
  
}


/**
*
* Get a list of the roles allowed to reset their password with this plugin
*
* @param void
* @return arr an array of role slugs
*
**/

function bdpwr_get_allowed_roles() {
  
  $all_roles = wp_roles()->roles;
  $roles_array = array();
  
  foreach( $all_roles as $slug => $role ) {
    $roles_array[] = $slug;
  }
  
  /**
  *
  * Filter the roles allowed to use this plugin to reset a password
  *
  * @param $roles arr the array of allowed roles
  *
  **/
  
  return apply_filters( 'bdpwr_allowed_roles' , $roles_array );
  
}


/**
*
* Get a user
*
* @param $user_id int the ID of the WP User
* @return obj a BDPWR_User user object
*
**/

function bdpwr_get_user( $user_id = false ) {
  return new BDPWR_User( $user_id );
}


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
    throw new Exception( __( 'An email address is required for the reset code email.' , 'bdvs-password-reset' ));
  }
  
  if( ! $code ) {
    throw new Exception( __( 'No code was provided for the password reset email.' , 'bdvs-password-reset' ));
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


/**
*
* BACKWARDS COMPATIBILITY FILLS
*
* The following declares new functions available from WP 5.3.0
* in the case that these have not already been declared, i.e. WP is < 5.3.0
*
**/

/**
*
* Retrieves the timezone from site settings as a string.
*
* Uses the `timezone_string` option to get a proper timezone if available,
* otherwise falls back to an offset.
*
* @since 5.3.0
*
* @return string PHP timezone string or a ±HH:MM offset.
*
**/

if( ! function_exists( 'wp_timezone_string' )) {
  function wp_timezone_string() {
    $timezone_string = get_option( 'timezone_string' );

    if ( $timezone_string ) {
      return $timezone_string;
    }

    $offset  = (float) get_option( 'gmt_offset' );
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );

    $sign      = ( $offset < 0 ) ? '-' : '+';
    $abs_hour  = abs( $hours );
    $abs_mins  = abs( $minutes * 60 );
    $tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

    return $tz_offset;
  }
}

/**
*
* Retrieves the timezone from site settings as a `DateTimeZone` object.
*
* Timezone can be based on a PHP timezone string or a ±HH:MM offset.
*
* @return DateTimeZone Timezone object.
* 
**/

if( ! function_exists( 'wp_timezone' )) {
  function wp_timezone() {
    return new DateTimeZone( wp_timezone_string() );
  }
}
