<?php

class sfTwitterAuthActions extends sfActions {
  public function executeLogin(sfRequest $request) {
    /* Consumer key from twitter */
    $consumer_key = sfConfig::get('app_sf_twitter_auth_consumer_key');
    $consumer_secret = sfConfig::get('app_sf_twitter_auth_consumer_secret');
    $user = $this->getUser(); /* @var $user myUser */

    /* If oauth_token is missing get it */
    if ($request->hasParameter('oauth_token') && $user->getAttribute('oauth_state', null, 'sfTwitterAuth') == 'start') {
      $user->setAttribute('oauth_state', 'returned', 'sfTwitterAuth');
    }

    /*
     * Switch based on where in the process you are
     *
     * 'default': Get a request token from twitter for new user
     * 'returned': The user has authorized the app on twitter
     */
    switch ($user->getAttribute('oauth_state', null, 'sfTwitterAuth')) {
      default:
        /* Create TwitterOAuth object with app key/secret */
        $connection = new TwitterOAuth($consumer_key, $consumer_secret);
        /* Request tokens from twitter */
        $tok = $connection->getRequestToken($this->getController()->genUrl(array('sf_route' => 'login'), true));

        /* Save tokens for later */
        $user->setAttribute('oauth_request_token', $tok['oauth_token'], 'sfTwitterAuth');
        $user->setAttribute('oauth_request_token_secret', $tok['oauth_token_secret'], 'sfTwitterAuth');
        $user->setAttribute('oauth_state', 'start', 'sfTwitterAuth');

        /* Build the authorization URL */
        $request_link = $connection->getAuthorizeURL($tok['oauth_token']);
        return $this->redirect($request_link);
        break;
      case 'returned':
        /* If the access tokens are already set skip to the API call */
        if ((!$user->getAttribute('oauth_access_token', null, 'sfTwitterAuth')) &&   (!$user->getAttribute('oauth_access_token_secret', null, 'sfTwitterAuth'))) {
          /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
          $connection = new TwitterOAuth($consumer_key, $consumer_secret, $user->getAttribute('oauth_request_token', null, 'sfTwitterAuth'), $user->getAttribute('oauth_request_token_secret', null, 'sfTwitterAuth'));
          /* Request access tokens from twitter */
          $tok = $connection->getAccessToken($request->getParameter('oauth_verifier'));
          /* Save the access tokens. These could be saved in a database as they don't
            currently expire. But our goal here is just to authenticate the session. */
          $user->setAttribute('oauth_access_token', $tok['oauth_token'], 'sfTwitterAuth');
          $user->setAttribute('oauth_access_token_secret', $tok['oauth_token_secret'], 'sfTwitterAuth');
        }
        /* Create TwitterOAuth with app key/secret and user access key/secret */
        $connection = new TwitterOAuth($consumer_key, $consumer_secret, $user->getAttribute('oauth_access_token', null, 'sfTwitterAuth'), $user->getAttribute('oauth_access_token_secret', null, 'sfTwitterAuth'));
        /* Run request on twitter API as user. */
        $result = $connection->get('account/verify_credentials');

        if ($result->id) {
          $guardUser = Doctrine::getTable('sfGuardUser')->findOneById($result->id);

          if (!$guardUser) {
            // Make a new user here
            $guardUser = $this->createUser($result);
          }

          $user->signIn($guardUser);

          // always redirect to a URL set in app.yml
          // or to the referer
          // or to the homepage
          $signinUrl = sfConfig::get('app_sf_twitter_auth_success_signin_url', $user->getReferer($request->getReferer()));

          return $this->redirect('' != $signinUrl ? $signinUrl : '@homepage');
        } else {
          $user->getAttributeHolder()->removeNamespace('sfTwitterAuth');
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

  public function executeSecure($request) {
    $this->getResponse()->setStatusCode(403);
  }

  public function executeFailed() {
    $this->getResponse()->setStatusCode(403);
  }

  public function executeLogout(sfRequest $request) {
    $user = $this->getUser(); /* @var $user myUser */
    $user->getAttributeHolder()->removeNamespace('sfTwitterAuth');
    $user->signOut();

    // always redirect to a URL set in app.yml
    // or to the referer
    // or to the homepage
    $signoutUrl = sfConfig::get('app_sf_twitter_auth_success_signout_url', $user->getReferer($request->getReferer()));

    return $this->redirect('' != $signoutUrl ? $signoutUrl : '@homepage');
  }
}