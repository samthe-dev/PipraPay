<?php
/**
 * SMS Notification — Save Settings API
 */

// Load PipraPay config for DB credentials
$config_file = __DIR__ . '/../../../../../pp-config.php';
if (!file_exists($config_file)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'false', 'title' => 'Error', 'message' => 'Config file not found']);
    exit;
}
require_once $config_file;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'false', 'title' => 'Error', 'message' => 'Invalid request method']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $addon_id = 'sms_notify';
    $allowed_fields = ['sms_provider', 'sms_api_key', 'sms_sender_id', 'sms_on_success', 'sms_success_template'];
    $now = date('Y-m-d H:i:s');

    foreach ($allowed_fields as $field) {
        $value = $_POST[$field] ?? '';

        $stmt = $pdo->prepare("SELECT id FROM {$db_prefix}addon_parameter WHERE addon_id = ? AND option_name = ?");
        $stmt->execute([$addon_id, $field]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE {$db_prefix}addon_parameter SET value = ?, updated_date = ? WHERE addon_id = ? AND option_name = ?");
            $stmt->execute([$value, $now, $addon_id, $field]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$db_prefix}addon_parameter (addon_id, option_name, value, created_date, updated_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$addon_id, $field, $value, $now, $now]);
        }
    }

    echo json_encode(['status' => 'true', 'title' => 'Success', 'message' => 'SMS settings saved successfully']);
} catch (Exception $e) {
    echo json_encode(['status' => 'false', 'title' => 'Error', 'message' => 'Database error: ' . $e->getMessage()]);
}
