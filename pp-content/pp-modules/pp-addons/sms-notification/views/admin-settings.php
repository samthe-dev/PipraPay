<?php
/**
 * SMS Notification — Admin Settings View
 *
 * @package    PipraPay
 * @subpackage Addons
 */

if (!defined('PipraPay_INIT')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

$settings = $addon_settings ?? [];
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
            </svg>
            SMS Notification Settings
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Send automatic SMS notifications to customers when their payment is confirmed.
            Currently supports <strong>BulkSMSBD</strong>.
        </p>

        <hr>

        <!-- Provider -->
        <div class="mb-3">
            <label class="form-label fw-semibold">SMS Provider</label>
            <select name="sms_provider" class="form-select" id="sms_provider">
                <option value="bulksmsbd" <?= ($settings['sms_provider'] ?? 'bulksmsbd') === 'bulksmsbd' ? 'selected' : '' ?>>
                    BulkSMSBD
                </option>
            </select>
        </div>

        <!-- API Key -->
        <div class="mb-3">
            <label class="form-label fw-semibold">API Key</label>
            <input type="text" name="sms_api_key" class="form-control" id="sms_api_key"
                   value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>"
                   placeholder="Enter your BulkSMSBD API key">
            <div class="form-text">Get your API key from <a href="https://bulksmsbd.com" target="_blank">bulksmsbd.com</a></div>
        </div>

        <!-- Sender ID -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Sender ID</label>
            <input type="text" name="sms_sender_id" class="form-control" id="sms_sender_id"
                   value="<?= htmlspecialchars($settings['sms_sender_id'] ?? '') ?>"
                   placeholder="e.g., PipraPay">
            <div class="form-text">Must be a registered sender ID with BulkSMSBD</div>
        </div>

        <hr>

        <!-- Enable/Disable -->
        <div class="mb-3">
            <div class="form-check form-switch">
                <input type="checkbox" name="sms_on_success" class="form-check-input" value="1"
                       id="sms_on_success" <?= ($settings['sms_on_success'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="sms_on_success">
                    Send SMS on Payment Success
                </label>
            </div>
        </div>

        <!-- Template -->
        <div class="mb-3">
            <label class="form-label fw-semibold">SMS Template</label>
            <textarea name="sms_success_template" class="form-control" rows="3" id="sms_success_template"
                      placeholder="Dear {name}, your payment of {amount} {currency} has been confirmed. TxnID: {txn_id}."
            ><?= htmlspecialchars($settings['sms_success_template'] ?? 'Dear {name}, your payment of {amount} {currency} has been confirmed. TxnID: {txn_id}. Thank you!') ?></textarea>
            <div class="form-text">
                Available placeholders: <code>{name}</code>, <code>{amount}</code>, <code>{currency}</code>, <code>{txn_id}</code>, <code>{gateway}</code>, <code>{brand}</code>
            </div>
        </div>

        <!-- Save Button -->
        <div class="mb-3">
            <button type="button" class="btn btn-primary" id="btn_save_sms_settings">
                Save SMS Settings
            </button>
            <span id="sms_save_status" class="ms-2"></span>
        </div>

        <hr>

        <!-- Test SMS -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Test SMS</label>
            <div class="input-group">
                <input type="text" id="test_sms_number" class="form-control" placeholder="Enter phone number (01XXXXXXXXX)">
                <button type="button" class="btn btn-outline-primary" onclick="sendTestSMS()">
                    Send Test
                </button>
            </div>
            <div id="test_sms_result" class="form-text mt-1"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn_save_sms_settings').addEventListener('click', function() {
    const btn = this;
    const status = document.getElementById('sms_save_status');
    const csrf = document.querySelector('input[name="csrf_token_default"]').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    status.innerHTML = '';

    const data = {
        action: 'sms-notification-save-settings',
        csrf_token: csrf,
        sms_provider: document.getElementById('sms_provider').value,
        sms_api_key: document.getElementById('sms_api_key').value,
        sms_sender_id: document.getElementById('sms_sender_id').value,
        sms_on_success: document.getElementById('sms_on_success').checked ? '1' : '0',
        sms_success_template: document.getElementById('sms_success_template').value,
    };

    fetch('<?php echo $site_url; ?>pp-content/pp-modules/pp-addons/sms-notification/api/save-settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data).toString()
    })
    .then(r => r.json())
    .then(resp => {
        btn.disabled = false;
        btn.innerHTML = 'Save SMS Settings';
        if (resp.status === 'true') {
            status.innerHTML = '<span class="text-success">✓ ' + resp.message + '</span>';
        } else {
            status.innerHTML = '<span class="text-danger">✗ ' + (resp.message || 'Save failed') + '</span>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = 'Save SMS Settings';
        status.innerHTML = '<span class="text-danger">✗ Error: ' + err.message + '</span>';
    });
});

function sendTestSMS() {
    const number = document.getElementById('test_sms_number').value.trim();
    const resultDiv = document.getElementById('test_sms_result');

    if (!number) {
        resultDiv.innerHTML = '<span class="text-danger">Please enter a phone number</span>';
        return;
    }

    resultDiv.innerHTML = '<span class="text-info">Sending...</span>';

    fetch('<?php echo $site_url; ?>pp-content/pp-modules/pp-addons/sms-notification/api/test.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ number: number })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<span class="text-success">✓ ' + data.message + '</span>';
        } else {
            resultDiv.innerHTML = '<span class="text-danger">✗ ' + data.message + '</span>';
        }
    })
    .catch(err => {
        resultDiv.innerHTML = '<span class="text-danger">✗ Error: ' + err.message + '</span>';
    });
}
</script>
