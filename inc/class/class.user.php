<?php


/**
*
* Class to handle user related actions
*
**/

class BDPWR_User extends WP_User {
  
  
  /**
  *
  * The class constructor
  * 
  * @param $user_id int the ID of the WP User
  * @return $this
  *
  **/
  
  public function __construct( $user_id = false ) {
    
    if( ! $user_id ) {
      throw new Exception( 'You must provide a $user_id to initiate a BDPWR_User object.' );
    }
    
    parent::__construct( $user_id );
    
  }
  
  
  /**
  *
  * Generate a password reset code and send it to the user
  *
  * @param void
  * @return bool true on success false on failure
  *
  **/
  
  public function send_reset_code() {
    
    $email = $this->get_email_address();
    $code = bdpwr_generate_4_digit_code();
    $expiration = bdpwr_get_new_code_expiration_time();
    
    $this->save_user_meta( 'bdpws-password-reset-code' , array(
      'code' => $code,
      'expiry' => $expiration,
    ));
    
    return bdpwr_send_password_reset_code_email( $email , $code , $expiration );
    
  }
  
  
  /**
  *
  * Set a new password
  *
  * @param $code str the code for the reset
  * @param $password str the new password
  * @return bool true on success, false on failure
  *
  **/
  
  public function set_new_password( $code , $password ) {
    
    $code_valid = $this->validate_code( $code );
    
    if( ! $code_valid ) {
      throw new Exception( 'There was a problem validating the code.' );
    }
    
    $this->delete_user_meta( 'bdpws-password-reset-code' );
    return wp_set_password( $password , $this->ID );
    
  }
  
  
  /**
  *
  * Validate a code
  *
  * @param $code str the code for the password reset
  * @return bool true on success, false on failure
  *
  **/
  
  public function validate_code( $code ) {
    
    $now = strtotime( 'now' );
    $stored_details = $this->get_user_meta( 'bdpws-password-reset-code' );
    
    if( ! $stored_details ) {
      throw new Exception( 'You must request a password reset code before you try to set a new password.' );
    }
    
    $stored_code = $stored_details['code'];
    $code_expiry = $stored_details['expiry'];
    
    if( $code !== $stored_code ) {
      throw new Exception( 'The reset code provided is not valid.' );
    }
    
    $expired = true;
    
    if( $code_expiry === -1 ) {
      $expired = false;
    }
    
    if( $now > $code_expiry ) {
      $expired = false;
    }
    
    if( ! $expired ) {
      throw new Exception( 'The reset code provided has expired.' );
    }
    
    return true;
    
  }
  
  
  /**
  *
  * Get user meta for this user
  *
  * @param $key str the meta_key to fetch
  * @param mixed str|bool the meta value or fale if it does not exist or has no value
  *
  **/
  
  private function get_user_meta( $key ) {    
    $value = get_user_meta( $this->ID , $key , true );    
    return ( $value !== '' ) ? $value : false;    
  }
  
  
  /**
  *
  * Save usermeta for this user
  *
  * @param $key str the meta_key to save
  * @param $value any the value to save
  * @return mixed int|bool the ID of the meta_value or false if it could not be saved
  *
  **/
  
  private function save_user_meta( $key , $value ) {
    return update_user_meta( $this->ID , $key , $value );
  }
  
  
  /**
  *
  * Delete usermeta
  *
  * @param $key str the meta_key to delete
  * @return bool true on success false on failure
  *
  **/
  
  private function delete_user_meta( $key ) {
    return delete_user_meta( $this->ID , $key );
  }
  
  
  /**
  *
  * Get a user's email address
  *
  * @param void
  * @return str the user's email address
  *
  **/
  
  private function get_email_address() {
    return $this->user_email;
  }
  
  
}