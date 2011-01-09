<?php

class sfTwitterAuthActions extends sfActions {
  public function executeLogin(sfRequest $request) {
    /* Consumer key from twitter */
    $consumer_key = sfConfig::get('app_sf_twitter_auth_consumer_key');
    $consumer_secret = sfConfig::get('app_sf_twitter_auth_consumer_secret');
    $user = $this->getUser(); /* @var $user myUser */
    /* Set state if previous session */
    $state = $user->getAttribute('sfTwitterAuth_oauth_state');
    /* If oauth_token is missing get it */
    if ($request->hasParameter('oauth_token') && ($state === 'start')) {
      $user->setAttribute('sfTwitterAuth_oauth_state', $state = 'returned');
    }

    /*
     * Switch based on where in the process you are
     *
     * 'default': Get a request token from twitter for new user
     * 'returned': The user has authorized the app on twitter
     */
    switch ($state) {
      default:
        /* Create TwitterOAuth object with app key/secret */
        $to = new TwitterOAuth($consumer_key, $consumer_secret);
        /* Request tokens from twitter */
        $tok = $to->getRequestToken($this->getController()->genUrl(array('sf_route' => 'login'), true));

        /* Save tokens for later */
        $user->setAttribute('sfTwitterAuth_oauth_request_token', $tok['oauth_token']);
        $user->setAttribute('sfTwitterAuth_oauth_request_token_secret', $tok['oauth_token_secret']);
        $user->setAttribute('sfTwitterAuth_oauth_state', 'start');

        /* Build the authorization URL */
        $request_link = $to->getAuthorizeURL($tok['oauth_token']);
        return $this->redirect($request_link);
        break;
      case 'returned':
        /* If the access tokens are already set skip to the API call */
        if ((!$user->getAttribute('sfTwitterAuth_oauth_access_token')) &&   (!$user->getAttribute('sfTwitterAuth_oauth_access_token_secret'))) {
          /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
          $to = new TwitterOAuth($consumer_key, $consumer_secret, $user->getAttribute('sfTwitterAuth_oauth_request_token'), $user->getAttribute('sfTwitterAuth_oauth_request_token_secret'));
          /* Request access tokens from twitter */
          $tok = $to->getAccessToken($request->getParameter('oauth_verifier'));
          /* Save the access tokens. These could be saved in a database as they don't
            currently expire. But our goal here is just to authenticate the session. */
          $user->setAttribute('sfTwitterAuth_oauth_access_token', $tok['oauth_token']);
          $user->setAttribute('sfTwitterAuth_oauth_access_token_secret', $tok['oauth_token_secret']);
        }
        /* Create TwitterOAuth with app key/secret and user access key/secret */
        $to = new TwitterOAuth($consumer_key, $consumer_secret, $user->getAttribute('sfTwitterAuth_oauth_access_token'), $user->getAttribute('sfTwitterAuth_oauth_access_token_secret'));
        /* Run request on twitter API as user. */
        $result = $to->OAuthRequest('https://twitter.com/account/verify_credentials.xml', 'GET', array());
        $xml = new SimpleXMLElement($result);

        if ($xml->id) {
          $guardUser = Doctrine::getTable('sfGuardUser')->findOneById($xml->id);

          if (!$guardUser) {
            // Make a new user here
            $guardUser = $this->createUser($xml);
          }

          $user->signIn($guardUser);

          // always redirect to a URL set in app.yml
          // or to the referer
          // or to the homepage
          $signinUrl = sfConfig::get('app_sf_twitter_auth_success_signin_url', $user->getReferer($request->getReferer()));

          return $this->redirect('' != $signinUrl ? $signinUrl : '@homepage');
        } else {
          $user->setAttribute('sfTwitterAuth_oauth_request_token', null);
          $user->setAttribute('sfTwitterAuth_oauth_request_token_secret', null);
          $user->setAttribute('sfTwitterAuth_oauth_state', null);
          $user->setAttribute('sfTwitterAuth_oauth_access_token', null);
          $user->setAttribute('sfTwitterAuth_oauth_access_token_secret', null);
          $this->redirect('sfTwitterAuth/failed');
        }
        break;
    }
  }

  private function createUser(SimpleXMLElement $xml) {
    $user = new sfGuardUser();
    $user->setId((int) $xml->id);
    $user->setUsername($xml->screen_name);
    $user->setEmailAddress($xml->screen_name);
    $user->setPassword($this->generatePassword());

    if(stristr($xml->name, ' ')) {
      $user->setFirstName(substr($xml->name, 0, strpos($xml->name, ' ')));
      $user->setLastName(substr($xml->name, strpos($xml->name, ' ') + 1, strlen($xml->name)));
    } else {
      $user->setFirstName($xml->name);
    }

    $profile = new sfGuardUserProfile();
    $profile->setId((int) $xml->id);
    $profile->setDescription($xml->description);
    $profile->setWebsite($xml->url);
    $profile->setImage($xml->profile_image_url);

    $user->setProfile($profile);
    $user->save();
    return $user;
  }

  private function generatePassword() {
    // Set a secure, random sfGuard password to ensure that this
    // account is not wide open if conventional logins are permitted
    $guid = '';

    for ($i = 0; ($i < 8); $i++) {
      $guid .= sprintf("%x", mt_rand(0, 15));
    }

    return $guid;
  }

  public function executeLogout(sfRequest $request) {
    $user = $this->getUser(); /* @var $user myUser */
    $user->signOut();

    // always redirect to a URL set in app.yml
    // or to the referer
    // or to the homepage
    $signoutUrl = sfConfig::get('app_sf_twitter_auth_success_signout_url', $user->getReferer($request->getReferer()));

    return $this->redirect('' != $signoutUrl ? $signoutUrl : '@homepage');
  }

  public function executeFailed() {

  }
}