<?php

class Twitter {
  private static $connection = null;

  public static function getConnection() {
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
      return false;
    }

    if(self::$connection === null) {
      self::$connection = new TwitterOAuth(sfConfig::get('app_sf_twitter_auth_consumer_key'), sfConfig::get('app_sf_twitter_auth_consumer_secret'), $user->getAttribute('oauth_access_token', null, 'sfTwitterAuth'), $user->getAttribute('oauth_access_token_secret', null, 'sfTwitterAuth'));
    }

    return self::$connection;
  }
}