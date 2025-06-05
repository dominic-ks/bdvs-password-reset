# Password Reset with Code for WordPress REST API

A simple plugin that adds a password reset facility to the WordPress REST API using a code. The process is a two step process:

1. User requests a password reset. A code is emailed to their registered email address
2. The user enters the code when setting a new password, which is only set if the code is valid and has not expired

It is also possible to check the validity of a code without resetting the password which enables the possibility of setting the password by other means, or having a two stage process for checking the code and resetting the password if desired.

Default settings are to use an 8 digit code consisting of numbers, upper and lower case letters and special characters, which has a life span of 15 minutes, afterwhich a new code would need to be requested. By default a user can attempt to use or validate a code up to 3 times before automatically invalidating it.

# IMPORTANT NOTICE

This plugin was recently updated due to a reported security vulnerability. Changes to the default settings may not be backwards compatible depending on your integration with the plugin. The latest version may not currently be available through the WordPress.org repository.

The following key updates were made:

- By default users with the administrator role are no longer able to reset their password using this plugin. This can be changed using the `bdpwr_allowed_roles` filter.
- The default length of the code that is generated has been increased from 4 to 8 characters. This can be changed using the `bdpwr_code_length` filter.
- The default characters that are used to generate the code have been increased to include upper and lower case letters as well as special characters. This can be changed using the `bdpwr_selection_string` filter.

## Endpoints

The plugin adds three new endpoints to the REST API:

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
Contributors are definitely welcome. Please checkout the [CONTRIBUTING.md](https://github.com/dominic-ks/bdvs-password-reset/blob/master/CONTRIBUTING.md) file for info and guidelines.

## Security Vulnerabilities
Please report security bugs found in the source code of the bdvs-password-reset plugin through the Patchstack Vulnerability Disclosure Program. The Patchstack team will assist you with verification, CVE assignment, and notify the developers of this plugin.
[Report a security vulnerability.](https://patchstack.com/database/vdp/bdvs-password-reset) 

## Change Log
- 0.0.17
  - switched to a cryptographically secure function to generate reset codes
  - updated compatibility to 6.8.1
 - 0.0.16
   - updated compatibility to 6.3
   - By default users with the administrator role are no longer able to reset their password using this plugin
   - The default length of the code that is generated has been increased from 4 to 8 characters
   - The default characters that are used to generate the code have been increased to include upper and lower case letters as well as special characters
 - 0.0.15
   - updated compatibility to 6.1.1
   - added github actions to auto-deploy to wordpress.org
 - 0.0.14
   - updated compatibility to 5.9.3
 - 0.0.13
   - updated to min version 4.6 to allow translations
 - 0.0.12
   - resolved file include errors
 - 0.0.11
   - resolved php warnings
 - 0.0.10
   - relocated email send function
   - added translation functions, should be translation ready! get in contact to get involved!
 - 0.0.9
   - fixed invalid plugin header issue
 - 0.0.8
   - fixed minor typos in docs
   - added filter to use custom namespace
   - fixed bug with time format filter
 - 0.0.7
   - Added maximum allowed failed attempts to validate a code before automatically expiring it, default has been set to 3
   - Added filters to include letters and well as numbers in the reset code as well as allowing you to specify your own string
   - Added filters to allow the exclusion of certain roles from being able to reset their password, e.g. if you want to exclude Administrators
 - 0.0.6
   - Added support for WP versions earlier than 5.2.0 due to timezone function availability
 - 0.0.5
   - Replaced missing api file
 - 0.0.4
   - Added /validate-code to allow checking a code's validity without actually resetting the password
 - 0.0.3
   - Fixed bug causing 500 error where WordPress TimeZone was set to a manual UTC offsite
