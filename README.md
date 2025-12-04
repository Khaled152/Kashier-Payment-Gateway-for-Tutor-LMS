# Kashier Payment Gateway for Tutor LMS

Integrate Kashier payment gateway with Tutor LMS native eCommerce system. Accept payments via Credit Card, Bank Installments, ValU, Souhoola, Aman, and Mobile Wallet.

## Description

This plugin adds Kashier payment methods to Tutor LMS's native eCommerce system, allowing Egyptian customers to pay for courses using various payment methods.

### Supported Payment Methods

| Method | Description | Subscription Support |
|--------|-------------|---------------------|
| **Card** | Credit/Debit Card payments (Visa, MasterCard, Meeza) | ✅ Yes |
| **Bank Installment** | Pay in installments via banks | ❌ No |
| **ValU** | ValU Buy Now Pay Later | ❌ No |
| **Souhoola** | Souhoola Buy Now Pay Later | ❌ No |
| **Aman** | Aman payment network | ❌ No |
| **Mobile Wallet** | Mobile wallet payments (Vodafone Cash, etc.) | ❌ No |

## Requirements

- WordPress 5.3 or higher
- PHP 7.4 or higher
- Tutor LMS 3.0.0 or higher
- Tutor LMS monetization set to "Native"
- Kashier merchant account ([Sign up here](https://merchant.kashier.io/))

## Installation

1. Upload the `kashier-tutor-gateway` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Tutor LMS → Settings → Monetization** and set "Monetize by" to **Native**
4. Go to **Tutor LMS → Settings → Payment Methods**
5. Enable and configure the **Card** payment method with your Kashier credentials

## Configuration

### Step 1: Get Your Kashier Credentials

1. Log in to your [Kashier Merchant Dashboard](https://merchant.kashier.io/)
2. Navigate to **Integration** → **API Keys**
3. Copy your:
   - Merchant ID
   - Test API Key (for sandbox testing)
   - Live API Key (for production)
   - Test Secret Key
   - Live Secret Key

### Step 2: Configure in Tutor LMS

1. Go to **Tutor LMS → Settings → Payment Methods**
2. Find **Card** (Kashier) in the list
3. Click **Manage** to configure
4. Fill in the settings:

| Setting | Description |
|---------|-------------|
| **Environment** | Select "Test" for sandbox or "Live" for production |
| **Merchant ID** | Your Kashier Merchant ID |
| **Test API Key** | API Key for sandbox testing |
| **Live API Key** | API Key for production |
| **Test Secret Key** | Secret Key for sandbox |
| **Live Secret Key** | Secret Key for production |
| **Webhook URL** | Copy this URL and add it to your Kashier dashboard |

### Step 3: Configure Webhook in Kashier Dashboard

1. Copy the **Webhook URL** from the Card settings
2. Go to your Kashier Merchant Dashboard
3. Navigate to **Integration** → **Webhooks**
4. Add a new webhook with the copied URL
5. Enable notifications for payment events

### Step 4: Enable Other Payment Methods

Other payment methods (Bank Installment, ValU, Souhoola, Aman, Mobile Wallet) share the same credentials as Card. Simply enable them in the payment methods list - no additional configuration needed.

## Testing

### Test Mode

1. Set **Environment** to "Test" in the Card settings
2. Use Kashier's test card numbers:
   - **Success**: `5123450000000008` (Exp: any future date, CVV: 100)
   - **Failure**: `4000000000000002`

### Production Mode

1. Set **Environment** to "Live"
2. Ensure you've entered your Live API Key and Secret Key
3. Test with a real transaction (small amount recommended)

## Payment Flow

1. Customer adds course to cart
2. Customer proceeds to checkout
3. Customer selects a Kashier payment method
4. Customer is redirected to Kashier's secure payment page
5. Customer completes payment
6. Kashier sends webhook notification
7. Plugin updates order status and enrolls student

## Troubleshooting

### Payment methods not appearing

- Ensure the plugin is activated
- Verify Tutor LMS monetization is set to "Native"
- Check that Tutor LMS version is 3.0.0 or higher

### Payments not completing

- Verify webhook URL is correctly configured in Kashier dashboard
- Check that API keys are correct for the selected environment
- Enable WordPress debug mode to check for errors

### Webhook not working

- Ensure your site is publicly accessible (not localhost)
- Check that the webhook URL is correct in Kashier dashboard
- Verify SSL certificate is valid (required for webhooks)

## Hooks & Filters

### Filters

```php
// Modify payment gateway data before registration
add_filter( 'tutor_payment_gateways', 'your_function' );

// Modify payment method labels
add_filter( 'tutor_payment_method_labels', 'your_function' );
```

## Frequently Asked Questions

### Can I use this plugin without WooCommerce?

Yes! This plugin integrates directly with Tutor LMS's native eCommerce system. WooCommerce is not required.

### Do I need the Kashier WooCommerce plugin?

No, this is a standalone plugin. However, if you have the Kashier WooCommerce plugin installed, this plugin will use its payment icons.

### Which currencies are supported?

Kashier primarily supports Egyptian Pound (EGP). Check with Kashier for other supported currencies.

### Is this plugin secure?

Yes. All sensitive payment data is handled by Kashier's secure payment page. Your site never sees or stores card details. All communications use HMAC-SHA256 signatures for verification.

## Changelog

### 1.0.0
- Initial release
- Added support for Card, Bank Installment, ValU, Souhoola, Aman, and Mobile Wallet
- Integration with Tutor LMS native eCommerce
- Webhook support for payment status updates
- Test and Live environment support

## Support

For plugin issues, please create an issue on GitHub.

For Kashier account issues, contact [Kashier Support](https://kashier.io/contact).

## License

This plugin is licensed under the GPLv2 or later.

---

Made with ❤️ for the Egyptian e-learning community

