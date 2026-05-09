<?php
/**
 * PipraPay — SMS Notification Addon
 *
 * Sends SMS notifications to customers on payment events.
 * Supports multiple SMS gateway providers.
 *
 * @package    PipraPay
 * @subpackage Addons
 * @author     Sam (samthe-dev)
 * @license    AGPL-3.0
 * @version    1.0.0
 */

if (!defined('PipraPay_INIT')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

class SmsNotificationAddon
{
    /**
     * Plugin metadata
     */
    public function info(): array
    {
        return [
            'id'          => 'sms_notification',
            'title'       => 'SMS Notification',
            'description' => 'Send SMS notifications to customers on payment success, failure, and other transaction events.',
            'version'     => '1.0.0',
            'author'      => 'Sam',
            'icon'        => 'assets/icon.svg',
        ];
    }

    /**
     * Render configuration form for PipraPay admin
     * Called by edit.php via $addonObj->configuration()
     */
    public function configuration(): string
    {
        $settings = $this->get_settings();
        ob_start();
        include __DIR__ . '/views/admin-settings.php';
        return ob_get_clean();
    }

    /**
     * Admin settings fields
     */
    public function settings_fields(): array
    {
        return [
            [
                'name'    => 'sms_provider',
                'label'   => 'SMS Provider',
                'type'    => 'select',
                'options' => [
                    'bulksmsbd' => 'BulkSMSBD',
                ],
                'default' => 'bulksmsbd',
            ],
            [
                'name'    => 'sms_api_key',
                'label'   => 'API Key',
                'type'    => 'text',
                'help'    => 'Your BulkSMSBD API key',
            ],
            [
                'name'    => 'sms_sender_id',
                'label'   => 'Sender ID',
                'type'    => 'text',
                'help'    => 'Registered sender ID (e.g., PipraPay)',
            ],
            [
                'name'    => 'sms_on_success',
                'label'   => 'SMS on Payment Success',
                'type'    => 'toggle',
                'default' => '1',
            ],
            [
                'name'    => 'sms_success_template',
                'label'   => 'Success SMS Template',
                'type'    => 'textarea',
                'default' => 'Dear {name}, your payment of {amount} {currency} has been confirmed. TxnID: {txn_id}. Thank you!',
                'help'    => 'Available placeholders: {name}, {amount}, {currency}, {txn_id}, {gateway}, {brand}',
            ],
        ];
    }

    /**
     * Get plugin settings from database
     */
    private function get_settings(): array
    {
        global $db_prefix;

        $result = json_decode(
            getData($db_prefix . 'addon_settings', 'WHERE addon_id = "sms_notification"', '* FROM'),
            true
        );

        if ($result['status'] === true && !empty($result['response'])) {
            return json_decode($result['response'][0]['settings'] ?? '{}', true);
        }

        return [];
    }

    /**
     * Send SMS via BulkSMSBD
     *
     * @param string $to      Recipient phone number
     * @param string $message SMS body
     * @param array  $config  Provider config [api_key, sender_id]
     * @return array [success => bool, message => string]
     */
    private function send_via_bulksmsbd(string $to, string $message, array $config): array
    {
        $api_key   = $config['sms_api_key']   ?? '';
        $sender_id = $config['sms_sender_id'] ?? '';

        if (empty($api_key) || empty($sender_id)) {
            return ['success' => false, 'message' => 'BulkSMSBD API key or sender ID not configured'];
        }

        // Format phone number — ensure 880 prefix
        $to = $this->format_phone($to);
        if ($to === false) {
            return ['success' => false, 'message' => 'Invalid phone number: ' . $to];
        }

        $url = 'https://bulksmsbd.net/api/smsapi';
        $params = [
            'api_key'  => $api_key,
            'senderid' => $sender_id,
            'number'   => $to,
            'message'  => $message,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['success' => false, 'message' => 'cURL error: ' . $curl_error];
        }

        $result = json_decode($response, true);

        if ($http_code === 200 && isset($result['success']) && $result['success'] === true) {
            return ['success' => true, 'message' => 'SMS sent successfully'];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Unknown error from BulkSMSBD (HTTP ' . $http_code . ')',
        ];
    }

    /**
     * Format phone number to 880XXXXXXXXXX
     */
    private function format_phone(string $phone): string|false
    {
        // Strip non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 0, add 880
        if (strlen($phone) === 11 && $phone[0] === '0') {
            $phone = '880' . substr($phone, 1);
        }

        // Already has 880 prefix
        if (strlen($phone) === 13 && strpos($phone, '880') === 0) {
            return $phone;
        }

        return false;
    }

    /**
     * Replace placeholders in SMS template
     */
    private function parse_template(string $template, array $data): string
    {
        $placeholders = [
            '{name}'     => $data['name']     ?? 'Customer',
            '{amount}'   => $data['amount']   ?? '0',
            '{currency}' => $data['currency'] ?? 'BDT',
            '{txn_id}'   => $data['txn_id']   ?? 'N/A',
            '{gateway}'  => $data['gateway']  ?? 'N/A',
            '{brand}'    => $data['brand']    ?? 'N/A',
        ];

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template
        );
    }

    /**
     * Log SMS to database
     */
    private function log_sms(array $data): void
    {
        global $db_prefix;

        insertData($db_prefix . 'sms_logs', [
            'addon_id',
            'recipient',
            'message',
            'provider',
            'status',
            'response',
            'created_date',
        ], [
            'sms_notification',
            $data['recipient'],
            $data['message'],
            $data['provider'],
            $data['status'],
            $data['response'],
            getCurrentDatetime('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Main entry point — called by PipraPay transaction hook
     *
     * @param array $transaction Transaction data from pp_set_transaction_status
     * @return void
     */
    public function on_transaction_completed(array $transaction): void
    {
        $settings = $this->get_settings();

        // Check if SMS on success is enabled
        if (($settings['sms_on_success'] ?? '1') !== '1') {
            return;
        }

        // Get customer phone
        $customer_info = json_decode($transaction['customer_info'] ?? '{}', true);
        $phone = $customer_info['mobile'] ?? '';

        if (empty($phone)) {
            return;
        }

        // Parse template
        $template = $settings['sms_success_template'] ?? 'Dear {name}, your payment of {amount} {currency} has been confirmed. TxnID: {txn_id}.';
        $message = $this->parse_template($template, [
            'name'     => $customer_info['name'] ?? 'Customer',
            'amount'   => $transaction['amount'] ?? '0',
            'currency' => $transaction['currency'] ?? 'BDT',
            'txn_id'   => $transaction['ref'] ?? 'N/A',
            'gateway'  => $transaction['gateway'] ?? 'N/A',
            'brand'    => $transaction['brand_name'] ?? 'N/A',
        ]);

        // Send via configured provider
        $provider = $settings['sms_provider'] ?? 'bulksmsbd';

        switch ($provider) {
            case 'bulksmsbd':
                $result = $this->send_via_bulksmsbd($phone, $message, $settings);
                break;
            default:
                $result = ['success' => false, 'message' => 'Unknown SMS provider: ' . $provider];
        }

        // Log the attempt
        $this->log_sms([
            'recipient' => $phone,
            'message'   => $message,
            'provider'  => $provider,
            'status'    => $result['success'] ? 'sent' : 'failed',
            'response'  => $result['message'],
        ]);
    }
}
