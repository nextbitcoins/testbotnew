
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFilePath = __DIR__ . '/log.txt';

function findUserByUserId($userId) {
    try {
        // MongoDB connection
        $manager = new MongoDB\Driver\Manager("mongodb://Nbetadmin:Zoya%401996_%40%26190%23@146.190.119.235:27017/inayat?authSource=admin");

        // Create a query to find the user by userId
        $filter = ['userId' => (string)$userId]; // Ensure userId is a string
        $options = [];
        $query = new MongoDB\Driver\Query($filter, $options);

        // Execute the query
        $cursor = $manager->executeQuery('inayat.telegramUsers', $query);

        // Check if the user exists
        $user = current($cursor->toArray()); // Convert cursor to array and get the first result
        return $user ? true : false;
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Log the error
        writeLog("Error: " . $e->getMessage() . "\n");
        return false; // Return false in case of an error
    }
}



function insertUser($USERID, $FULLNAME, $FINALUSERNAME, $FIRSTNAME, $LASTNAME, $ISPREMIUM, $REFERRERID) {
    try {
        $manager = new MongoDB\Driver\Manager("mongodb://Nbetadmin:Zoya%401996_%40%26190%23@146.190.119.235:27017/inayat?authSource=admin");
        $bulk = new MongoDB\Driver\BulkWrite;

        $document = [
            'userId' => (string)$USERID, 
            'fullName' => $FULLNAME,
            'username' => $FINALUSERNAME,
            'firstName' => $FIRSTNAME,
            'lastName' => $LASTNAME,
            'selectedExchange' => [
                'id' => "selectex",
                'icon' => "/exchange.svg",
                'name' => "Choose exchange",
            ],
            'tonTransactions' => 0,
            'taskPoints' => 0,
            'checkinRewards' => 0,
            'miningPower' => 400,
            'premiumReward' => 0,
            'totalBalance' => 0,
            'miningTotal' => 0,
            'balance' => 0,
            'isPremium' => $ISPREMIUM ?: false, // Default to false if not set
            'lastActive' => new MongoDB\BSON\UTCDateTime(), // Use MongoDB DateTime
            'createdAt' => new MongoDB\BSON\UTCDateTime(), // Use MongoDB DateTime
            'refereeId' => $REFERRERID ?: null, // If no referrer, set as null
            'referrals' => [],
            'tonTasksAmount' => 0,
            'tonTrAmount' => 0,
            'tonRefTrAmount' => 0,
            'withdrawalLimit' => 0,
            'referralsTotal' => 0,
            'withdrawnAmount' => 0,
            'lastWithdrawal' => 0,
            'lastReferralReward' => 0,
            'banned' => false,
            'lastReferralClaim' => new MongoDB\BSON\UTCDateTime(), // Use MongoDB DateTime
            'newUser' => true,
        ];

        // Inserting the document
        $bulk->insert($document);

        // Execute the bulk write to the collection
        $manager->executeBulkWrite('inayat.telegramUsers', $bulk); // Use correct namespace (case-sensitive)
        
        // Log and return success message
        writeLog("User with userId: $USERID inserted successfully.\n");
        return "User with userId: $USERID inserted successfully.\n";
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Log and return error message
        writeLog("Error: " . $e->getMessage() . "\n");
        return "Error: " . $e->getMessage() . "\n";
    }
}


// Custom logging function
function writeLog($message)
{
    global $logFilePath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
}

// Display script start
writeLog("Script started");

// Telegram Bot API key
$apiKey = '7333937557:AAHaU4M025dD5dUNikPEyC4vWLFmZFmygxM'; // Your Telegram bot API key
$apiUrl = "https://api.telegram.org/bot$apiKey/";
writeLog("API URL set: $apiUrl");

// Get the incoming message
$content = file_get_contents("php://input");
if ($content === false) {
    writeLog("Failed to retrieve input");
} else {
    writeLog("Input retrieved: $content");
}
$update = json_decode($content, true);

if ($update === null) {
    writeLog("Failed to decode JSON: " . json_last_error_msg());
} else {
    writeLog("JSON decoded successfully");
}

// Extract necessary information from the update
$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? null;
$message_id = $update['message']['message_id'] ?? null;
$user_id = $update['message']['from']['id'] ?? null;
$firstName = $update['message']['from']['first_name'] ?? null;
$lastName = $update['message']['from']['last_name'] ?? null;
$username = $update['message']['from']['username'] ?? null;
$is_premium = $update['message']['from']['is_premium'];
$chat_type = $update['message']['chat']['type'];

writeLog("INFO: Received a message update.");
writeLog("Chat ID: $chat_id");
writeLog("Text: $text");
writeLog("Message ID: $message_id");
writeLog("User ID: $user_id");
writeLog("First Name: $firstName");
writeLog("Last Name: $lastName");
writeLog("Username: $username");
writeLog("Is Premium: " . ($is_premium ? 'Yes' : 'No'));
writeLog("Chat Type: $chat_type");

if($chat_type !== 'private'){
    die;
}

if (!$chat_id || !$text) {
    writeLog("Missing necessary information: chat_id or text");
} else {
    writeLog("Message details extracted - Chat ID: $chat_id, Text: $text");
}

// Path to the image
$photoPath = realpath(__DIR__ . '/banner.jpg'); // Absolute path to the image
if (!$photoPath || !file_exists($photoPath)) {
    writeLog("Image file not found: $photoPath");
} else {
    writeLog("Image file found: $photoPath");
}

// Check if the "/start" command has a referral
if (isset($text) && strpos($text, '/start') === 0) {
    writeLog("Received /start command");

   $userAlreadyExist =  findUserByUserId($user_id);
   

    // Extract the referrer ID from the referral link (if present)
    $referrer_id = null;
    if (strpos($text, '/start r') === 0) {
        $referrer_id = substr($text, 8); // Extract the referrer's ID after '/start r'
        writeLog("Referral ID extracted: $referrer_id");
    }
    if(!$userAlreadyExist){
        insertUser($user_id, $first_name." ".$lastName, $username, $firstName, $lastName, $is_premium, $referrer_id) 
    }else{

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
            writeLog("Error notifying referrer: " . curl_error($ch_notify));
        } else {
            writeLog("Notification sent to referrer: $referrer_id");
        }
        if(!$username){
            writeLog("Private :  $username");
            $username = $user_id;
        }

        curl_close($ch_notify);
    }


    // Standard /start welcome message with image
    $caption = "
    📜 NBGT Mining Game Rules 📜

⛏️ 1. Mine to Earn: Start mining and earn Next Bitcoin Governance Tokens (NBGT). The more you mine, the more you earn!

🏅 2. Leaderboard: Compete globally to top the leaderboard with the most NBGT and showcase your mining dominance!

🎯 3. Daily Missions: Complete daily tasks and missions to unlock bonus rewards and maximize your earnings!

🎁 4. Daily Rewards: Log in every day to claim free daily rewards and keep your mining streak active!

👥 5. Refer & Earn: Invite your friends and community to join and earn bigger rewards and higher withdrawal limits for every successful referral.

💼 6. Withdrawal Rules:
- More Referrals = Higher Withdrawal Limits.
- Complete Missions to become eligible for withdrawals.
- Monthly Withdrawal Caps are determined by your referral activity and mining performance.

⚙️ 7. Customize: Adjust your mining settings and preferences to suit your style and optimize your rewards!

Start mining now and earn rewards with NBGT! 💸🚀

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
            writeLog("CURL Error: " . curl_error($ch));
        } else {
            writeLog("Image and welcome message sent successfully");
            writeLog("Response: $result");
        }

        curl_close($ch);
    } else {
        writeLog("Image file not found: " . $photoPath);
    }
}
?>