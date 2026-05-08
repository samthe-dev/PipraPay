<?php
/**
 * PipraPay — SMS Notification Addon Hook
 *
 * Include this file to hook into PipraPay transaction events.
 * Add this to pp-content/pp-include/pp-functions.php after pp_set_transaction_status():
 *
 *   // SMS Notification Hook
 *   if (file_exists(__DIR__ . '/../pp-modules/pp-addons/sms-notification/hook.php')) {
 *       require_once __DIR__ . '/../pp-modules/pp-addons/sms-notification/hook.php';
 *   }
 *
 * @package    PipraPay
 * @subpackage Addons
 */

if (!defined('PipraPay_INIT')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

/**
 * Hook: Transaction completed
 *
 * Call this function inside pp_set_transaction_status() right after
 * the status is set to 'completed' and the transaction data is available.
 *
 * @param array $transaction Full transaction row from database
 * @return void
 */
function pp_sms_on_transaction_completed(array $transaction): void
{
    $addon_file = __DIR__ . '/class.php';

    if (!file_exists($addon_file)) {
        return;
    }

    require_once $addon_file;

    if (!class_exists('SmsNotificationAddon')) {
        return;
    }

    try {
        $addon = new SmsNotificationAddon();
        $addon->on_transaction_completed($transaction);
    } catch (\Throwable $e) {
        // Silent fail — never break payment flow
        error_log('[SMS Notification] Error: ' . $e->getMessage());
    }
}
