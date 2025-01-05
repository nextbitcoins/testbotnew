<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log script start
error_log("Script started");

// Telegram Bot API key
$apiKey = '7333937557:AAHaU4M025dD5dUNikPEyC4vWLFmZFmygxM'; // Your Telegram bot API key
$apiUrl = "https://api.telegram.org/bot$apiKey/";
error_log("API URL set: $apiUrl");

// Get the incoming message
$content = file_get_contents("php://input");
if ($content === false) {
    error_log("Failed to retrieve input");
} else {
    error_log("Input retrieved: $content");
}
$update = json_decode($content, true);

if ($update === null) {
    error_log("Failed to decode JSON: " . json_last_error_msg());
} else {
    error_log("JSON decoded successfully");
}

// Extract necessary information from the update
$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? null;
$message_id = $update['message']['message_id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$user_name = $update['message']['from']['first_name'] ?? null;

if (!$chat_id || !$text) {
    error_log("Missing necessary information: chat_id or text");
} else {
    error_log("Message details extracted - Chat ID: $chat_id, Text: $text");
}

// Path to the image
$photoPath = realpath(__DIR__ . '/banner.jpg'); // Absolute path to the image
if (!$photoPath || !file_exists($photoPath)) {
    error_log("Image file not found: $photoPath");
} else {
    error_log("Image file found: $photoPath");
}

// Check if the "/start" command has a referral
if (isset($text) && strpos($text, '/start') === 0) {
    error_log("Received /start command");

    // Extract the referrer ID from the referral link (if present)
    $referrer_id = null;
    if (strpos($text, '/start r') === 0) {
        $referrer_id = substr($text, 8); // Extract the referrer's ID after '/start r'
        error_log("Referral ID extracted: $referrer_id");
    }

    // Notify the referrer if applicable
    if ($referrer_id) {
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

        $notify_result = curl_exec($ch_notify);
        if ($notify_result === false) {
            error_log("Error notifying referrer: " . curl_error($ch_notify));
        } else {
            error_log("Notification sent to referrer: $referrer_id");
        }

        curl_close($ch_notify);
    }

    // Standard /start welcome message with image
    $caption = "
📜 NBET Mining Game Rules 📜

⛏️ 1. Mine to Earn: Start mining and earn Next Bitcoin Energy Tokens (NBET). The more you mine, the more you earn!

🏅 2. Leaderboard: Compete globally to top the leaderboard with the most NBET!

🎯 3. Daily Missions: Complete daily tasks for bonus rewards!

🎁 4. Daily Rewards: Log in daily for free rewards!

👥 5. Refer & Earn: Invite friends for bigger rewards and higher withdrawal limits.

💼 6. Withdrawal Rules:

*More referrals = Higher withdrawal limits.
*Complete missions for eligibility.
*Monthly withdrawal caps based on referrals and activity.
⚙️ 7. Customize: Adjust settings to suit your style!

Start mining now and earn rewards! 💸
";

    $referralLink = $referrer_id ? "https://testone.nextbitcoin.pro/?ref=$referrer_id" : "https://testone.nextbitcoin.pro";

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
                        ['text' => 'Whitepaper', 'url' => 'https://next-bitcoin-protocol.gitbook.io/next-bitcoin-enegry-token/v/nbet-white-paper'],
                        ['text' => 'Channel', 'url' => 'https://t.me/nextbitcoinpro'],
                    ],
                    [
                        ['text' => 'Play Now', 'web_app' => ['url' => $referralLink]]
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
        } else {
            error_log("Image and welcome message sent successfully");
            error_log("Response: $result");
        }

        curl_close($ch);
    } else {
        error_log("Image file not found: " . $photoPath);
    }
}
?>
