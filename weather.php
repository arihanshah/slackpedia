<?php
/*
REQUIREMENTS
* A custom slash command on a Slack team
* A web server running PHP5 with cURL enabled
USAGE
* Place this script on a server running PHP5 with cURL.
* Set up a new custom slash command on your Slack team: 
  http://my.slack.com/services/new/slash-commands
* Under "Choose a command", enter whatever you want for 
  the command. /isitup is easy to remember.
* Under "URL", enter the URL for the script on your server.
* Leave "Method" set to "Post".
* Decide whether you want this command to show in the 
  autocomplete list for slash commands.
* If you do, enter a short description and usage hint.
*/
# Grab some of the values from the slash command, create vars for post back to Slack
$slack_webhook_url = "https://hooks.slack.com/services/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"; // replace that URL with your webhook URL

$wiki_lang = "en";
$search_limit = "3";
$user_agent = "testing1 (arihan.shah@gmail.com)";

$command = $_POST['command'];
$text = $_POST['text'];
$token = $_POST['token'];
$channel_id = $_POST['channel_id'];
$user_name = $_POST['user_name'];
$encoded_text = urlencode($text);

$wiki_url = "http://".$wiki_lang.".wikipedia.org/w/api.php?action=opensearch&search=".$encoded_text."&format=json&limit=".$search_limit;

$wiki_call = curl_init($wiki_url);
curl_setopt($wiki_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($wiki_call, CURLOPT_USERAGENT, $user_agent);

$wiki_respsonse = curl_exec($wiki_call);


curl_close($wiki_call);



if($wiki_response === FALSE ){
    $message_text = "There was a problem reaching Wikipedia. This might be helpful: The cURL error is " . curl_error($wiki_call);
} else {
    $wiki_array = json_decode($wiki_response);
    $other_options = $wiki_array[3];
    $first_item = array_shift($other_options);
    $other_options_count = count($other_options);
    $message_text = "<@".$user_id."|".$user_name."> searched for *".$text."*.\n";
    if (strpos($wiki_array[2][0],"may refer to:") !== false) {
        $disambiguation_check = TRUE;
    }
    $message_primary_title      =   $wiki_array[1][0];
    $message_primary_summary    =   $wiki_array[2][0];
    $message_primary_link       =   $wiki_array[3][0];

    if(count($wiki_array[1]) == 0){
        $message_text = "Sorry! I couldn't find anything like *".$text."*.";

    } else {
        if ($disambiguation_check == TRUE) { // see if it's a disambiguation page
            $message_text   .= "There are several possible results for ";
            $message_text   .= "*<".$message_primary_link."|".$text.">*.\n";
            $message_text   .= $message_primary_link;
            $message_other_title = "Here are some of the possibilities:";
        } else {
            $message_text   .=  "*<".$message_primary_link."|".$message_primary_title.">*\n";
            $message_text   .=  $message_primary_summary."\n";
            $message_text   .=  $message_primary_link;
            $message_other_title = "Here are a few other options:";
        }
        foreach ($other_options as $value) {
            $message_other_options .= $value."\n";
        }
    } // close the `if` where we check the count of `$wiki_array[1]`
} // close the `if` where we verify that `$wiki_response` is not FALSE
$data = array(
    "username" => "Slackipedia",
    "channel" => $channel_id,
    "text" => $message_text,
    "mrkdwn" => true,
    "attachments" => array(
         array(
            "color" => "#b0c4de",
        //  "title" => $message_primary_title,
            "fallback" => $message_attachment_text,
            "text" => $message_attachment_text,
            "mrkdwn_in" => array(
                "fallback",
                "text"
            ),
            "fields" => array(
                array(
                    "title" => $message_other_options_title,
                    "value" => $message_other_options
                )
            )
        )
    )
);
$json_string = json_encode($data);

$slack_call = curl_init($slack_webhook_url);
curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
curl_setopt($slack_call, CURLOPT_CRLF, true);
curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Content-Length: " . strlen($json_string))
);

$result = curl_exec($slack_call);
curl_close($slack_call);

