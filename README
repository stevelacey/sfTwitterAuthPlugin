# sfTwitterAuthPlugin #

## Installation and Usage ##

sfTwitterAuthPlugin provides Twitter API v1.1 integration including authentication for your applications.

This plugin requires sfDoctrineGuardPlugin as a starting point. sfGuardUser objects are created in the database for each Twitter user who has not been seen previously. The sfGuardUser username field will be set to the user's Twitter username.

To use this plugin, set up sfDoctrineGuardPlugin correctly, then set login_module to `sfTwitterAuth` and login_action to `login`.

Also, in `app.yml`, set your Twitter application apikey and secret:

    all:
      sfTwitterAuth:
        consumer_key: YOUR_CONSUMER_KEY
        consumer_secret: YOUR_CONSUMER_SECRET
        access_token: YOUR_ACCESS_TOKEN (optional)
        access_token_secret: YOUR_ACCESS_TOKEN_SECRET (optional)
        success_signin_url: ... (optional)

You can get these via the (http://twitter.com/oauth_clients Twitter Applications Page).

If you add your own access token and secret then you'll be able to use Twitter API methods without end-user authentication, useful for tasks and 'recent tweet' lists, etc. If a user is authenticated we'll default to their token to save your request limit.

Now all actions that require authentication will force the user to log in via Twitter first. You can also log the user in explicitly by redirecting or forwarding them to the sfTwitterAuth/login action. 

Redirects should now be handled gracefully alike sfDoctrineGuardPlugin, to success_signin_url (if set), to referrer if there was one or to @homepage.

Once authenticated you can use the Twitter class to retrieve an instance of the current TwitterOAuth connection and perform API requests on behalf of the user like so:

    Twitter::getConnection()->get('users/search', array('q' => 'Steve Lacey'))

## Credits ##

Copyright 2010, Thomas Boutell. Released under the MIT license (see LICENSE). Thomas Boutell develops Symfony-driven sites with (http://www.punkave.com/ P'unk Avenue), a design firm in Philadelphia, PA.

sfTwitterAuthPlugin contains TwitterOAuth by Abraham Williams, who has confirmed his willingness to release his code under the MIT license. TwitterOAuth contains OAuth.php by Andy Smith, also under the MIT license.

Updates by Steve Lacey are also subject to the license above, these include various general bugfixes and integration of version 0.2.0-beta2 of TwitterOAuth by Abraham Williams.
