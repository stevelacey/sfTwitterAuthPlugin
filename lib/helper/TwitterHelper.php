<?php

/**
* twitter_display_config.php
* Configuration options for the Twitter display plugin
* Latest copy of this code: http://140dev.com/free-twitter-api-source-code-library/
* @author Adam Green <140dev@gmail.com>
* @license GNU Public License
* @version BETA 0.10
*/

// Number of tweets displayed when the tweet list is first displayed
// and when the View More button is clicked
define('TWEET_DISPLAY_COUNT', 30);

// Text for View More button
define ('MORE_BUTTON', 'tweets displayed - View More');

// Options for hashtag links
define('HASHTAG_TITLE', 'View tweets for tag: ');
define('HASHTAG_URL', 'http://search.twitter.com/search?q=%23');

// Options for user mention links
define('USER_MENTION_TITLE', 'View tweets by user: ');
define('USER_MENTION_URL', 'http://twitter.com/');

// Href title for tweet date
define('TWEET_DISPLAY_TITLE', 'View this tweet on Twitter.com');

// Number of seconds between checking server for new tweet count
define('NEW_COUNT_REFRESH', 30);

// Message for new tweet count at top of tweet list
define('NEW_TWEET_MESSAGE', '1 new tweet available. <a href="">Refresh</a> to see it.');
define('NEW_TWEETS_MESSAGE', ' new tweets available. <a href="">Refresh</a> to see them.');


/**
* linkify.php
* Convert entities into links within a tweet's text
* Latest copy of this code: http://140dev.com/free-twitter-api-source-code-library/
* @author Adam Green <140dev@gmail.com>
* @license GNU Public License
* @version BETA 0.10
*/
function linkify($tweet_text, $entities) {

  // $entities is an object delivered by the Twitter API for each tweet with
  // the user @mentions, hastags, and URLs broken out along with their positions

  // Create an array of entities with the starting point of the entity as the key
  // This will allow the processing of the tweet in a character by character loop
  // Entities can be replaced by their hyperlink versions within this loop
  $entity_map = array();

  // Extract user mentions
  foreach ($entities->user_mentions as $user_mention) {
    $start = $user_mention->indices[0];
    $entity_map[$start] = array('screen_name'=> $user_mention->screen_name,
      'name' => $user_mention->name,
      'type' => 'user_mention' );
  }

  // Extract hashtags
  foreach ($entities->hashtags as $hashtag) {
  $start = $hashtag->indices[0];
  $entity_map[$start] = array('text'=> $hashtag->text,
    'type' => 'hashtag');
  }

  // Extract URLs
  foreach ($entities->urls as $url) {
    $start = $url->indices[0];
    $entity_map[$start] = array('url'=> $url->url,
      'expanded_url'=> $url->expanded_url,
      'type' => 'url');
  }

  // Loop through the tweet text one character at a time
  $charptr = 0;
  $text_end = strlen($tweet_text) - 1;
  // Construct a new version of the text with entities converted to links
  $new_text = '';
  while ($charptr <= $text_end) {

    // Does the current character have a matching element in the $entity_map array?
    if (isset($entity_map[$charptr])) {
      switch ($entity_map[$charptr]['type']) {
      case 'user_mention':
        $new_text .= '<a href="' . USER_MENTION_URL .
          $entity_map[$charptr]['screen_name'] .
          '" title="' . USER_MENTION_TITLE .
          $entity_map[$charptr]['screen_name'] .
          ' (' . $entity_map[$charptr]['name'] . ')">@' .
          $entity_map[$charptr]['screen_name'] . '</a>';

        $charptr += strlen($entity_map[$charptr]['screen_name']) + 1;
        break;

      case 'hashtag':
        $new_text .= '<a href="' . HASHTAG_URL .
        $entity_map[$charptr]['text'] .
          '" title="' . HASHTAG_TITLE .
        $entity_map[$charptr]['text'] . '">#' .
          $entity_map[$charptr]['text'] . '</a>';

        $charptr += strlen($entity_map[$charptr]['text']) + 1;
        break;

      case 'url':
        $new_text .= '<a href="';
        if ($entity_map[$charptr]['expanded_url']) {
          $new_text .= $entity_map[$charptr]['expanded_url'];
        } else {
          $new_text .= $entity_map[$charptr]['url'];
        }
        $new_text .= '">' . $entity_map[$charptr]['url'] . '</a>';

        $charptr += strlen($entity_map[$charptr]['url']) + 1;
        break;
      }
    } else {
      $new_text .= substr($tweet_text,$charptr,1);
      ++$charptr;
    }
  }

  return $new_text;
}