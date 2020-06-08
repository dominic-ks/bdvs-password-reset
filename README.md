# Password Reset with Code for WordPress REST API

A simple plugin that adds a password reset facility to the WordPress REST API using a code. The process is a two step process:

1. User requests a password reset. A 4 digit code is emailed to their registered email address
2. The user enters the code when setting a new password, which is only set if the code is valid and has not expired

It is also possible to check the validity of a code without resetting the password which enables the possibility of setting the password by other means, or having a two stage process for checking the code and resetting the password if desired.

Default settings are to use a 4 digit numerical code which has a life span of 15 minutes, afterwhich a new code would need to be requested.

## Endpoints

The plugin adds two new endpoints to the REST API:
Also, two new endpoints are added to this namespace.

| Endpoint                              | HTTP Verb | Parameters (**all required**)      |
| ------------------------------------- | --------- | ---------------------------------- |
| */wp-json/bdpwr/v1/reset-password*    | POST      |  email                             |
| */wp-json/bdpwr/v1/set-password*      | POST      |  email <br /> password <br /> code |
| */wp-json/bdpwr/v1/validate-code*     | POST      |  email <br /> code                 |

## Example Requests (jQuery)

### Reset Password

```
$.ajax({
  url: '/wp-json/bdpwr/v1/reset-password',
  method: 'POST',
  data: {
    email: 'example@example.com',
  },
  success: function( response ) {
    console.log( response );
  },
  error: function( response ) {
    console.log( response );
  },
});
```

### Set New Password

```
$.ajax({
  url: '/wp-json/bdpwr/v1/set-password',
  method: 'POST',
  data: {
    email: 'example@example.com',
    code: '1234',
    password: 'Pa$$word1',
  },
  success: function( response ) {
    console.log( response );
  },
  error: function( response ) {
    console.log( response );
  },
});
```

### Validate Code

```
$.ajax({
  url: '/wp-json/bdpwr/v1/validate-code',
  method: 'POST',
  data: {
    email: 'example@example.com',
    code: '1234',
  },
  success: function( response ) {
    console.log( response );
  },
  error: function( response ) {
    console.log( response );
  },
});
```

## Example Success Responses (JSON)

### Reset Password

```json
{
    "data": {
        "status": 200
    },
    "message": "A password reset email has been sent to your email address."
}
```

### Set New Password

```json
{
    "data": {
        "status": 200
    },
    "message": "Password reset successfully."
}
```

### Validate Code

```json
{
    "data": {
        "status": 200
    },
    "message": "The code supplied is valid."
}
```

## Example Error Responses (JSON)

### Reset Password

```json
{
    "code": "bad_email",
    "message": "No user found with this email address.",
    "data": {
        "status": 500
    }
}
```

### Set New Password

```json
{
    "code": "bad_request",
    "message": "You must request a password reset code before you try to set a new password.",
    "data": {
        "status": 500
    }
}
```

### Validate Code

```json
{
    "code": "bad_request",
    "message": "The reset code provided is not valid.",
    "data": {
        "status": 500
    }
}
```

## Filters

A number of WordPress filters have been added to help customise the process, please feel free to request additional filters or submit a pull request with any that you required.

### Filter the length of the code
```
add_filter( 'bdpwr_code_length' , function( $length ) {
  return 4;
}, 10 , 1 );
```

### Filter Expiration Time
```
add_filter( 'bdpwr_code_expiration_seconds' , function( $seconds ) {
  return 900;
}, 10 , 1 );
```

### Filter the date format used by the plugin to display expiration times
```
add_filter( 'bdpwd_date_format' , function( $format ) {
  return 'h:i';
}, 10 , 1 );
```

### Filter the reset email subject
```
add_filter( 'bdpwr_code_email_subject' , function( $subject ) {
  return 'Password Reset';
}, 10 , 1 );
```

### Filter the email content
```
add_filter( 'bdpwr_code_email_text' , function( $text , $email , $code , $expiry ) {
  return $text;
}, 10 , 4 );
```
