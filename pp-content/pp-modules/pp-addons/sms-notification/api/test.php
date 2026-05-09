<?php
/**
 * SMS Notification — Test SMS API
 */

$config_file = __DIR__ . '/../../../../../pp-config.php';
if (!file_exists($config_file)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Config file not found']);
    exit;
}

require_once $config_file;

if (!defined('PipraPay_INIT')) {
    define('PipraPay_INIT', true);
}

require_once __DIR__ . '/../class.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$number = trim($payload['number'] ?? '');
if ($number === '') {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $settings = [];
    $stmt = $pdo->prepare("SELECT option_name, value FROM {$db_prefix}addon_parameter WHERE addon_id = ?");
    $stmt->execute(['sms_notify']);
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['option_name']] = $row['value'];
    }

    $addon = new SmsNotificationAddon();
    $result = $addon->send_test_sms($number);

    // Best-effort logging for standalone test runs.
    try {
        $stmt = $pdo->prepare("INSERT INTO {$db_prefix}sms_logs (addon_id, recipient, message, provider, status, response, created_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'sms_notification',
            $number,
            'PipraPay test SMS: your SMS notification addon is working.',
            $settings['sms_provider'] ?? 'bulksmsbd',
            ($result['success'] ?? false) ? 'sent' : 'failed',
            $result['message'] ?? 'Unknown result',
            date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $logError) {
        // Silent fail — logging should never block the API response.
    }

    echo json_encode([
        'success' => $result['success'] ? true : false,
        'message' => $result['message'] ?? ($result['success'] ? 'Test SMS sent' : 'Test SMS failed'),
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Test SMS error: ' . $e->getMessage()]);
}
