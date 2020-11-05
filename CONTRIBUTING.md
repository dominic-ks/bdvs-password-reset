# Contributing Guidelines
Thinking of contributing to this repo? Awesome! Please follow this guide when doing so.

## Before you begin
Before getting stuck in, doing some work and submitting a PR, it's a good idea to create an issue, or check for a new one if it exists and start some discussion about what you want to do.

## Getting set up for development
This is a relatively simple plugin only utilising php (currently) so the development set up is relatively easy, we recommend the following:
 - Get yourself a LAMP type server set up, this plugin currently supports back as far as php5.6, so this is a good place to start. Please make sure PRs work with php5.6 as well as php7+.
 - Install WordPress, the plugin currently supports as far back as 4.2 officially, so this is a good version to use. Please make sure PRs work with 4.2+.
 - Go to /wp-content/plguins and clone this repo.
 - Activate the plugin in WordPress.
 - Using Postman, or whatever method you like for testing API calls, ensure that all the API calls documented work as expected as well as any filters that may be impacted by your changes. At a minimum you should confirm these scenarios are working:
   - A call to `/wp-json/bdpwr/v1/reset-password` with a valid user email returns a success message and you receive an email with a code
   - A call to `/wp-json/bdpwr/v1/reset-password` with an invalid user email returns an error message
   - A call to `/wp-json/bdpwr/v1/validate-code` with a valid code returns a success message
   - A call to `/wp-json/bdpwr/v1/validate-code` with an invalid code returns an error message
   - A call to `/wp-json/bdpwr/v1/set-password` with a valid code returns a success message
   - A call to `/wp-json/bdpwr/v1/set-password` with an invalid code returns an error message

## Coding Standards
Generally please follow the [WordPress coding standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) and try and stick to the general layout and organisation of the existing code in the plugin.
Some specific points:
 - Class names should be prefixed `BDPWR_`, use caps for the start of words and all caps for acronyms, e.g. `BDPWR_WP_Class`.
 - Function and method names should be prefixed `bdpwr_`, be all lower case with words separated by underscores, e.g. `bdprw_my_function_name`.
 - Use spaces before and after parameter names, e.g. `bdpwr_my_function_name( $parameter_1 , $parameter_2 );`.
 - Braces should open on the same line as class / method / function name or if / loop statement etc. etc and close on their own line, e.g.
 ```
 bdpwr_my_function_name( $parameter_1 , $parameter_2 ) {
    //do your thang
 }
 ```
