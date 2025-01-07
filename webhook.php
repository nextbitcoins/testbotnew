<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFilePath = __DIR__ . '/log.txt';

// MongoDB connection
function getMongoManager() {
    return new MongoDB\Driver\Manager("mongodb://Nbetadmin:Zoya%401996_%40%26190%23@146.190.119.235:27017/inayat?authSource=admin");
}

// Log messages
function writeLog($message) {
    global $logFilePath;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
}

// Find user by userId
function findUserByUserId($userId) {
    try {
        $manager = getMongoManager();
        $filter = ['userId' => (string)$userId];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery('inayat.telegramUsers', $query);
        return current($cursor->toArray()) ? true : false;
    } catch (MongoDB\Driver\Exception\Exception $e) {
        writeLog("Error in findUserByUserId: " . $e->getMessage());
        return false;
    }
}

// Insert a new user
function insertUser($userId, $fullName, $username, $firstName, $lastName, $isPremium, $referrerId) {
    try {
        $manager = getMongoManager();
        $bulk = new MongoDB\Driver\BulkWrite;
        $document = [
            'userId' => (string)$userId,
            'fullName' => $fullName,
            'username' => $username ?: $userId,
            'firstName' => $firstName,
            'lastName' => $lastName,
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
            'isPremium' => $isPremium ?: false,
            'lastActive' => new MongoDB\BSON\UTCDateTime(),
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'refereeId' => $referrerId ?: null,
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
            'lastReferralClaim' => new MongoDB\BSON\UTCDateTime(),
            'newUser' => true,
        ];
        $bulk->insert($document);
        $manager->executeBulkWrite('inayat.telegramUsers', $bulk);
        writeLog("User with userId: $userId inserted successfully.");
        return true;
    } catch (MongoDB\Driver\Exception\Exception $e) {
        writeLog("Error in insertUser: " . $e->getMessage());
        return false;
    }
}

// Telegram API setup
$apiKey = '7333937557:AAHaU4M025dD5dUNikPEyC4vWLFmZFmygxM';
$apiUrl = "https://api.telegram.org/bot$apiKey/";

// Handle incoming updates
$content = file_get_contents("php://input");
if ($content === false) {
    writeLog("Failed to retrieve input.");
    exit;
}
$update = json_decode($content, true);
if ($update === null) {
    writeLog("Failed to decode JSON: " . json_last_error_msg());
    exit;
}

// Extract message details
$message = $update['message'] ?? [];
$chatId = $message['chat']['id'] ?? null;
$text = $message['text'] ?? null;
$userId = $message['from']['id'] ?? null;
$firstName = $message['from']['first_name'] ?? null;
$lastName = $message['from']['last_name'] ?? null;
$username = $message['from']['username'] ?? null;
$isPremium = $message['from']['is_premium'] ?? false;
$chatType = $message['chat']['type'] ?? null;

// Validate incoming message
if ($chatType !== 'private') {
    exit;
}

if (!$chatId || !$text) {
    writeLog("Missing chat ID or text in the update.");
    exit;
}

// Process the "/start" command
if (strpos($text, '/start') === 0) {
    $referrerId = null;
    if (strpos($text, '/start r') === 0) {
        $referrerId = substr($text, 8);
    }

    if (!findUserByUserId($userId)) {
        $insertResult = insertUser($userId, "$firstName $lastName", $username, $firstName, $lastName, $isPremium, $referrerId);
        if ($insertResult && $referrerId) {
            $notificationText = "$username joined using your referral link! 🎉";
            file_get_contents($apiUrl . "sendMessage?chat_id=$referrerId&text=" . urlencode($notificationText));
        }
    }
}

// Send welcome message with image
$photoPath = __DIR__ . '/banner.jpg';
if (file_exists($photoPath)) {
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
    $referralLink = $referrerId ? "https://testone.nextbitcoin.pro/?ref=$referrerId" : "https://testone.nextbitcoin.pro";

    $postFields = [
        'chat_id' => $chatId,
        'photo' => new CURLFile($photoPath),
        'caption' => $caption,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => 'Start Mining', 'url' => $referralLink]],
            ],
        ]),
    ];

    $ch = curl_init($apiUrl . "sendPhoto");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_exec($ch);
    curl_close($ch);
}
