# SMS Notification Addon for PipraPay

Send automatic SMS notifications to customers when their payment is confirmed.

## Features

- **BulkSMSBD** integration (Bangladesh)
- Customizable SMS templates with placeholders
- Toggle SMS on/off per event
- SMS logging for audit trail
- Test SMS from admin panel
- Silent fail — never breaks payment flow

## Installation

1. Copy the `sms-notification` folder to `pp-content/pp-modules/pp-addons/`
2. Run the SQL in `install.sql` to create required tables
3. Add the hook include to `pp-content/pp-include/pp-functions.php`:

```php
// SMS Notification Hook — add after pp_set_transaction_status() function
if (file_exists(__DIR__ . '/../pp-modules/pp-addons/sms-notification/hook.php')) {
    require_once __DIR__ . '/../pp-modules/pp-addons/sms-notification/hook.php';
}
```

4. Call the hook inside `pp_set_transaction_status()` when status is 'completed':

```php
// After line: updateData($db_prefix.'transaction', $columns, $values, $condition);
// Add:
pp_sms_on_transaction_completed($response_transaction['response'][0]);
```

5. Go to Admin → Addons → SMS Notification → Configure

## Configuration

| Setting | Description |
|---------|-------------|
| SMS Provider | Select gateway (BulkSMSBD) |
| API Key | Your BulkSMSBD API key |
| Sender ID | Registered sender ID |
| SMS on Success | Toggle automatic SMS |
| SMS Template | Customizable message template |

## Placeholders

Available in SMS templates:

- `{name}` — Customer name
- `{amount}` — Payment amount
- `{currency}` — Currency code (BDT, USD)
- `{txn_id}` — Transaction reference ID
- `{gateway}` — Payment gateway name
- `{brand}` — Brand/company name

## Architecture

```
sms-notification/
├── class.php              # Main plugin class
├── hook.php               # PipraPay hook integration
├── install.sql            # Database schema
├── assets/
│   └── icon.svg           # Plugin icon
├── views/
│   └── admin-settings.php # Admin panel UI
└── README.md
```

## Provider Abstraction

To add a new SMS provider:

1. Add provider option in `settings_fields()`
2. Add `send_via_{provider}()` method in `class.php`
3. Add case in `on_transaction_completed()` switch

## Security

- API keys stored in database (not hardcoded)
- Phone number validation and formatting
- Silent error handling — payment flow never breaks
- Input sanitization on all settings

## License

AGPL-3.0 (same as PipraPay)
