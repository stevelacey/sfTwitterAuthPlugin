<?php

class Twitter {
  private static $connection = null;

  public static function getConnection() {
    $user = sfContext::hasInstance() && sfContext::getInstance()->getUser()->isAuthenticated() ? sfContext::getInstance()->getUser() : false;

    if(self::$connection === null) {
      self::$connection = new TwitterOAuth(
        sfConfig::get('app_sf_twitter_auth_consumer_key'),
        sfConfig::get('app_sf_twitter_auth_consumer_secret'),
        $user ? $user->getAttribute('oauth_access_token', null, 'sfTwitterAuth') : sfConfig::get('app_sf_twitter_auth_access_token') ,
        $user ? $user->getAttribute('oauth_access_token_secret', null, 'sfTwitterAuth') : sfConfig::get('app_sf_twitter_auth_access_token_secret')
      );
    }

    return self::$connection;
  }
}