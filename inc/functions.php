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
  return substr( str_shuffle( '0123456789' ) , 0 , $length );
  
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
  
  $format = apply_filters( 'bdpwd_date_format' , 'h:i' );
  
  $date = new DateTime();
  $date->setTimestamp( $time );
  $date->setTimezone( new DateTimeZone( get_option('timezone_string' )));

  return date_format( $date , 'h:i' );
  
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