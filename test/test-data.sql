-- PipraPay SMS Notification — Test Data
-- Run after PipraPay installation

-- Insert test addon settings
INSERT INTO `pp_addon_settings` (`addon_id`, `settings`, `status`, `created_date`, `updated_date`)
VALUES (
    'sms_notification',
    '{
        "sms_provider": "bulksmsbd",
        "sms_api_key": "test-api-key-placeholder",
        "sms_sender_id": "PipraPay",
        "sms_on_success": "1",
        "sms_success_template": "Dear {name}, your payment of {amount} {currency} has been confirmed. TxnID: {txn_id}. Thank you!"
    }',
    1,
    NOW(),
    NOW()
);

-- Insert test brand
INSERT INTO `pp_brands` (`brand_id`, `brand_name`, `brand_email`, `brand_url`, `status`, `created_date`)
VALUES (
    'test_brand_001',
    'Test Brand',
    'test@example.com',
    'http://localhost:8080',
    'active',
    NOW()
);

-- Insert test gateway
INSERT INTO `pp_gateways` (`gateway_id`, `brand_id`, `display`, `gateway_type`, `currency`, `status`, `created_date`)
VALUES (
    'test_gateway_001',
    'test_brand_001',
    'Test Gateway',
    'manual',
    'BDT',
    'active',
    NOW()
);

-- Insert test transaction (initiated)
INSERT INTO `pp_transaction` (
    `ref`, `brand_id`, `gateway_id`, `amount`, `currency`,
    `customer_info`, `metadata`, `status`, `created_date`
) VALUES (
    'PP-TEST-001',
    'test_brand_001',
    'test_gateway_001',
    '500.00',
    'BDT',
    '{"name": "Test Customer", "email": "test@example.com", "mobile": "01712345678"}',
    '{"invoice_id": "INV-001", "order_id": "ORD-001"}',
    'initiated',
    NOW()
);

-- Verify
SELECT 'Test data inserted successfully' AS status;
SELECT * FROM `pp_addon_settings` WHERE `addon_id` = 'sms_notification';
SELECT `ref`, `amount`, `currency`, `status` FROM `pp_transaction` WHERE `ref` = 'PP-TEST-001';
