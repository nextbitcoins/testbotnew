<?php

$apiKey = '7333937557:AAHaU4M025dD5dUNikPEyC4vWLFmZFmygxM'; // Your Telegram bot API key
$apiUrl = "https://api.telegram.org/bot$apiKey/";

// Get the incoming message
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Extract necessary information from the update
$chat_id = $update['message']['chat']['id']; // ID of the current user
$text = $update['message']['text'];
$message_id = $update['message']['message_id'];
$user_id = $update['message']['from']['id']; // Extract the user's ID for dynamic referral
$user_name = $update['message']['from']['first_name']; // Get user's first name

// Path to the image
$photoPath = __DIR__ . '/home.png'; // Absolute path to the image

// Check if the "/start" command has a referral
if (isset($update['message']['text']) && strpos($text, '/start') === 0) {
    // Extract the referrer ID from the referral link (if present)
    $referrer_id = null;
    if (strpos($text, '/start r') === 0) {
        $referrer_id = substr($text, 8); // Extract the referrer's ID after '/start r'
    }

    // If there's a referrer, notify them
    if ($referrer_id) {
        // Notify the referrer that someone joined from their link
        $notificationText = "$user_name joined using your referral link! 🎉";

        $ch_notify = curl_init();
        curl_setopt($ch_notify, CURLOPT_URL, $apiUrl . "sendMessage");
        curl_setopt($ch_notify, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch_notify, CURLOPT_POST, 1);

        $post_notify = [
            'chat_id' => $referrer_id,
            'text' => $notificationText
        ];

        curl_setopt($ch_notify, CURLOPT_POSTFIELDS, $post_notify);
        curl_setopt($ch_notify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_notify, CURLOPT_SSL_VERIFYHOST, 0);

        curl_exec($ch_notify);
        curl_close($ch_notify);
    }

    // Standard /start welcome message with image
    if ($text === '/start' || $referrer_id) {
        // Caption for the welcome message
              $caption = "
      
🎉🐼 Welcome to Bao Bao Panda - Your Meme Hub! 🐼🎉

Hey there, meme lover! 😄
Welcome to Bao Bao Panda, the ultimate Telegram mini app for endless laughs, trending memes, and wholesome panda vibes. 🎭✨

📌 What’s in it for you?

🐾 The funniest, freshest memes delivered daily.
🐾 A platform to share your own meme creations.
🐾 Meme battles, contests, and more exciting surprises!
🌍💖 Get ready to unleash your inner panda and join the fun. Let’s make memes, not war!

👉 Start exploring now: Open Bao Bao Panda Mini App

🐾 Tag your friends and spread the laughs! 🎉
        ";

        // If there was a referrer, include their ID in the link
        $referralLink = $referrer_id ? "https://play.baobaopanda.com/?ref=$referrer_id" : "https://play.baobaopanda.com";
 

        // Check if file exists
        if (file_exists($photoPath)) {
            $realPath = realpath($photoPath);

            // Send the image with the caption
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl . "sendPhoto");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);

           
            $post_fields = [
    'chat_id' => $chat_id,
    'photo' => new CURLFILE($realPath),
    'caption' => $caption,
    'parse_mode' => 'Markdown',
    'reply_markup' => json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'Official channel', 'url' => 'https://t.me/Mybaobaopanda'],
                ['text' => 'Twitter', 'url' => 'https://x.com/BaoBaoPandaMeme']
            ],
            [
                ['text' => 'Play', 'web_app' => ['url' => $referralLink]]
            ]
        ]
    ])
];


            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            $result = curl_exec($ch);
            if ($result === false) {
                error_log("CURL Error: " . curl_error($ch));
            }

            curl_close($ch);
        } else {
            error_log("Image not found: " . $photoPath);
        }
    }
}

?>
