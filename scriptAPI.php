<?php
header('Content-Type: application/json');

// Get webhook URL from environment variable
$webhookUrl = getenv('WEBHOOK_URL');
if (!$webhookUrl) {
    http_response_code(500);
    echo json_encode(['error' => 'Webhook URL not configured']);
    exit;
}

// Get username from query parameter
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

try {
    // Fetch data from the API using cURL
    $apiUrl = "https://api.tricko.pro/voxiom/player/" . urlencode($username);
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    
    if ($response === false) {
        throw new Exception('API request failed: ' . curl_error($ch));
    }
    
    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['data']['nickname']) && $data['data']['nickname'] === $username) {
        // Username is valid, send to webhook using cURL
        $webhookData = json_encode(['username' => $username]);
        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $webhookData);
        $webhookResult = curl_exec($ch);

        if ($webhookResult !== false) {
            http_response_code(200);
            echo json_encode(['message' => "Username \"$username\" is valid and sent to webhook"]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send to webhook: ' . curl_error($ch)]);
        }
        curl_close($ch);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid username']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error verifying username']);
    error_log('API error: ' . $e->getMessage());
}

?>
