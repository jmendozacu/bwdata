update customer_entity set email = concat('shrikant+',`entity_id`,'@relfor.com');

update `marketplace_userdata` set contact_email = concat('shrikant+',`seller_id`,'@relfor.com');

update `marketplace_userdata` set store_owner_email = concat('shrikant+',`seller_id`,'@relfor.com'), store_manager_email = concat('shrikant+',`seller_id`,'@relfor.com');

update `marketplace_userdata` set store_manager_mobile_no = '1122112211', store_owner_mobile_no = '1122112211';

insert core_config_data (config_id, scope, scope_id, path, value) values (null, 'default', 0, 'dev/static/sign', 0); 

update `core_config_data` set value = 'http://local.bakeway.com' where `path` = 'web/unsecure/base_url';

update `core_config_data` set value = 'http://local.bakeway.com' where `path` = 'web/secure/base_url';

update `core_config_data` set value = '0' where `path` = 'smtp/general/enabled';

update `core_config_data` set value = '*' where `path` = 'web/corsRequests/origin_url';

update `core_config_data` set value = 'http://local.bakeway.com/resetpassword/track/' where `path` = 'react_site_settings/react_settings_general/react_url';

update `core_config_data` set value = 'http://local.bakeway.com/track/' where `path` = 'react_site_settings/react_settings_general/guest_track_url';

UPDATE `customer_entity` SET password_hash = '0485e6ffa6a0ce60cef3bfbe48ed25d000d71bd9864db9eae0406aa236865513:zYZ2xBmh5FvKcXh6uM87QDweOEALMN6M:1' WHERE 1;

update `core_config_data` set value = 'Relfor79692506955759' where `path` = 'payment/paytm/MID';

update `core_config_data` set value = 'in4gRghL10QyDPyZ' where `path` = 'payment/paytm/merchant_key';

update `core_config_data` set value = '1' where `path` = 'payment/paytm/debug';

update `core_config_data` set value = 'Retail' where `path` = 'payment/paytm/Industry_id';

update `core_config_data` set value = 'WEB' where `path` = 'payment/paytm/Channel_Id';

update `core_config_data` set value = 'WEB_STAGING' where `path` = 'payment/paytm/Website';

update `core_config_data` set value = 'rzp_test_5i7oIvX5lRE3KS' where `path` = 'payment/razorpay/key_id';

update `core_config_data` set value = 'e8Pa3Zbz9sHeqeSmxwlydK7o' where `path` = 'payment/razorpay/key_secret';

update `core_config_data` set value = NULL where `path` like "sales_email/order/copy_to";

update `core_config_data` set value = '30000' where `path` like "grab/grab_api_setting/client_id";

update `sales_order_address`  set email = "1234567@gmail.com",phone = "1234567891" ORDER BY `entity_id` DESC;

update `sales_order` set customer_email = "1234567@gmail.com";

update `sales_order` set status = "complete";

TRUNCATE TABLE `vendor_device_data`;
