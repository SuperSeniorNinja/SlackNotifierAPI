<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SlackNotifier {

  protected $CI;

  public function __construct() {
    $this->CI =& get_instance();
  }

  // Send a message to a Gathr Films Slack channel
  // Message formatting docs: https://api.slack.com/block-kit
  // Message formatting builder: https://app.slack.com/block-kit-builder/
  public function sendMessageToChannel($channel_name, $plaintext_message, $formatted_message = NULL) {
    // Add the bot to the channel:
    $channel_id = $this->addBotToChannel($channel_name);

    $data = array(
      'channel' => $channel_id,
      'text' => $plaintext_message,
      'blocks' => $formatted_message
    );

    $data_string = json_encode($data);

    $result = $this->makePostRequest('chat.postMessage', $data);
    $parsedResult = json_decode($result);

    // debug:
    // var_dump($parsedResult);

    return $parsedResult->ok;
  }

  // Add the Gathr Bot to a Slack channel
  private function addBotToChannel($channel_name) {

    $channel_id = $this->getChannelIdFromChannelName($channel_name);

    $data = array(
      'channel' => $channel_id
    );

    $data_string = json_encode($data);

    $result = $this->makePostRequest('conversations.join', $data);
    $parsedResult = json_decode($result);

    // debug:
    // var_dump($parsedResult);

    if($parsedResult->ok) return $parsedResult->channel->id;
    return FALSE;
  }

  // Get a Slack channel ID from the channel name
  private function getChannelIdFromChannelName($channel_name) {
    $result = $this->makePostRequest('conversations.list?types=public_channel%2Cprivate_channel&limit=1000');
    $parsedResult = json_decode($result);

    // debug:
    // var_dump($parsedResult);

    if ($parsedResult->ok) $channels = $parsedResult->channels;
    else return FALSE;

    foreach($channels as $channel) {
      if ($channel_name == $channel->name) return $channel->id;
    }
    return FALSE;
  }

  // Submit the POST request to the Slack API and return the result
  private function makePostRequest($endpoint, $data = NULL) {
    $token = $this->CI->config->item('slack_bot_oauth_token');
    $data_string = json_encode($data);

    $ch = curl_init('https://slack.com/api/' . $endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json; charset=utf-8',
      'Authorization: Bearer ' . $token,
      'Content-Length: ' . strlen($data_string))
    );

    // Send POST request
    $result = curl_exec($ch);
    return $result;
  }

}
