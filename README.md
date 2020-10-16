# Password Reset with Code for WordPress REST API

A simple plugin that adds a password reset facility to the WordPress REST API using a code. The process is a two step process:

1. User requests a password reset. A 4 digit code is emailed to their registered email address
2. The user enters the code when setting a new password, which is only set if the code is valid and has not expired

It is also possible to check the validity of a code without resetting the password which enables the possibility of setting the password by other means, or having a two stage process for checking the code and resetting the password if desired.

Default settings are to use a 4 digit numerical code which has a life span of 15 minutes, afterwhich a new code would need to be requested. By default a user can attempt to use or validate a code up to 3 times before automatically invalidating it.

## Endpoints

The plugin adds two new endpoints to the REST API:
Also, two new endpoints are added to this namespace.

| Endpoint                              | HTTP Verb | Parameters (**all required**)      |
| ------------------------------------- | --------- | ---------------------------------- |
| */wp-json/bdpwr/v1/reset-password*    | POST      |  email                             |
| */wp-json/bdpwr/v1/set-password*      | POST      |  email <br /> password <br /> code |
| */wp-json/bdpwr/v1/validate-code*     | POST      |  email <br /> code                 |

## Installation & Docs
The plugin is hosted on and can be downloaded from the wordpress.org plugin repo, you will also find here more general info about how to use and customise the plugin
 - [https://wordpress.org/plugins/bdvs-password-reset/](https://wordpress.org/plugins/bdvs-password-reset/)

## Support
Plugin support is provided via the plugin support page on wordpress.org:
- [https://wordpress.org/support/plugin/bdvs-password-reset/](https://wordpress.org/support/plugin/bdvs-password-reset/)

## Issues and Enhancements
If you find any issues or have ideas for the plugin, please feel free to raise an issue here on GitHub.

## Contributions
Contributors are definitely welcome. A few general guidelines:
 - If on doesn't exist, create an issue here on GitHub with the proposed changes.
 - Use the relevant GitHub issue to discuss and agree what will be done
 - Generally please follow the [WordPress coding standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) and try and stick to the general layout and organisation of the existing code in the plugin

## Change Log
 - 0.0.7
 -- Added maximum allowed failed attempts to validate a code before automatically expiring it, default has been set to 3
 -- Added filters to include letters and well as numbers in the reset code as well as allowing you to specify your own string
 -- Added filters to allow the exclusion of certain roles from being able to reset their password, e.g. if you want to exclude Administrators
 - 0.0.6
 -- Added support for WP versions earlier than 5.2.0 due to timezone function availability
 - 0.0.5
 -- Replaced missing api file
 - 0.0.4
 -- Added /validate-code to allow checking a code's validity without actually resetting the password
 - 0.0.3
 -- Fixed bug causing 500 error where WordPress TimeZone was set to a manual UTC offsite
