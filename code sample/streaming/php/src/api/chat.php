<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['querySearch'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing querySearch field']);
    exit;
}

// Hardcoded values
$uid = 'irfan@zygy.com';
$bearerToken = 'sk-nervesis-TWrKd0LyaFDMj9WoK6mcODNxZnt0GyHgzOEPtu3R60w';
$apiUrl = 'https://biz.zygy.com/api/v1/response';
// $mode = 'filesearch';
// $selectworkspace = 'user workspace';
// $selectfile = ["Meeting test"];
// $selectfolder = ["test upload"];


$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'querySearch' => $input['querySearch'],
    'uid' => $uid,
    // 'mode' => $mode,
    // 'selectworkspace' => $selectworkspace,
    // 'selectfile' => $selectfile,
    // 'selectfolder' => $selectfolder
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $bearerToken
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);

// Set up custom callback to stream the response and parse JSON
$buffer = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer) {
    $buffer .= $data;
    
    // Process complete lines
    while (($newlinePos = strpos($buffer, "\n")) !== false) {
        $line = substr($buffer, 0, $newlinePos);
        $buffer = substr($buffer, $newlinePos + 1);
        
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        // Remove 'data: ' prefix if present
        if (strpos($line, 'data: ') === 0) {
            $line = substr($line, 6);
        }
        
        // Try to decode JSON
        $json = json_decode($line, true);
        if ($json && isset($json['choices'][0]['delta']['content'])) {
            $content = $json['choices'][0]['delta']['content'];
            echo $content;
            flush();
        }
    }
    
    return strlen($data);
});

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    http_response_code(500);
    echo "Error: " . $error;
}
?>
