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
      throw new Exception( __( 'You must provide a $user_id to initiate a BDPWR_User object.' , 'bdvs-password-reset' ));
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
    
    $allowed_roles = bdpwr_get_allowed_roles();
    $allowed = false;
    
    foreach( $allowed_roles as $role ) {
      
      if( ! in_array( $role , (array) $this->roles )) {
        continue;
      }
      
      $allowed = true;
        
    }
    
    if( ! $allowed ) {
      throw new Exception( __( 'You cannot request a password reset for a user with this role.' , 'bdvs-password-reset' ));
    }
    
    $email = $this->get_email_address();
    $code = bdpwr_generate_4_digit_code();
    $expiration = bdpwr_get_new_code_expiration_time();
    
    $this->save_user_meta( 'bdpws-password-reset-code' , array(
      'code' => $code,
      'expiry' => $expiration,
      'attempt' => 0,
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
      throw new Exception( __( 'There was a problem validating the code.' , 'bdvs-password-reset' ));
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
      throw new Exception( __( 'You must request a password reset code before you try to set a new password.' , 'bdvs-password-reset' ));
    }
    
    $stored_code = $stored_details['code'];
    $code_expiry = $stored_details['expiry'];
    $attempt = ( isset( $stored_details['attempt'] )) ? $stored_details['attempt'] : 0;
    $attempt++;
    $attempts_string = '';
    
    /**
    *
    * Filter the maximum attempts that can be made on a given code. Set to -1 for unlimmited.
    *
    * @param $attempts int the maximum number of failed attempts allowed before a code is invalidated
    *
    **/
    
    $attempts_max = apply_filters( 'bdpwr_max_attempts' , 3 );
    
    if( $code !== $stored_code && $attempts_max !== -1 ) {
      
      $stored_details['attempt'] = $attempt;
      $remaining_attempts = $attempts_max - $attempt;
    
      $this->save_user_meta( 'bdpws-password-reset-code' , $stored_details );
      
      $attempts_string = sprintf(
        /* translators: %s: Number of remaining attempts */
        __( 'You have %s attempts remaining.' , 'bdvs-password-reset' ), 
        $remaining_attempts 
      );
      
      if( $remaining_attempts <= 0 ) {
        $attempts_string = __( 'You have used the maximum number of attempts allowed. You must request a new code.' , 'bdvs-password-reset' );
        $this->delete_user_meta( 'bdpws-password-reset-code' );
      }
              
      throw new Exception( __( 'The reset code provided is not valid. ' , 'bdvs-password-reset' ) . $attempts_string );
      
    }
    
    if( $code !== $stored_code ) {
      throw new Exception( __( 'The reset code provided is not valid.' , 'bdvs-password-reset' ));      
    }
    
    $expired = true;
    
    if( $code_expiry === -1 ) {
      $expired = false;
    }
    
    if( $now > $code_expiry ) {
      $expired = false;
    }
    
    if( ! $expired ) {
      $this->delete_user_meta( 'bdpws-password-reset-code' );
      throw new Exception( __( 'The reset code provided has expired.' , 'bdvs-password-reset' ));
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