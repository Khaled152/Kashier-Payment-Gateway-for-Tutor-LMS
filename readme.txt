=== Kashier Payment Gateway for Tutor LMS ===
Contributors: kashier
Tags: tutor lms, payment gateway, kashier, egypt, ecommerce
Requires at least: 5.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept payments via Kashier (Card, Bank Installments, ValU, Souhoola, Aman, Mobile Wallet) in Tutor LMS native eCommerce.

== Description ==

Kashier Payment Gateway for Tutor LMS enables Egyptian merchants to accept payments through Tutor LMS's native eCommerce system using Kashier payment gateway.

= Supported Payment Methods =

* **Credit/Debit Card** - Visa, MasterCard, Meeza
* **Bank Installments** - Pay in installments via Egyptian banks
* **ValU** - Buy Now Pay Later
* **Souhoola** - Buy Now Pay Later
* **Aman** - Aman payment network
* **Mobile Wallet** - Vodafone Cash, Orange Cash, Etisalat Cash, etc.

= Features =

* Easy integration with Tutor LMS native eCommerce
* Multiple payment methods from single configuration
* Test (Sandbox) and Live mode support
* Secure payments via Kashier hosted payment page
* Automatic enrollment after successful payment
* Webhook support for reliable payment confirmation
* Arabic and English language support

= Requirements =

* Tutor LMS 3.0.0 or higher
* Tutor LMS monetization set to "Native"
* Kashier merchant account

== Installation ==

1. Upload `kashier-tutor-gateway` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tutor LMS → Settings → Monetization and set "Monetize by" to "Native"
4. Go to Tutor LMS → Settings → Payment Methods
5. Enable "Card" and configure with your Kashier credentials
6. Copy the Webhook URL and add it to your Kashier merchant dashboard
7. Enable other payment methods as needed

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. This plugin works with Tutor LMS's native eCommerce system without WooCommerce.

= Do I need the Kashier WooCommerce plugin? =

No. This is a standalone plugin for Tutor LMS.

= Which currencies are supported? =

Kashier primarily supports Egyptian Pound (EGP). Contact Kashier for other currencies.

= Is it secure? =

Yes. Payment data is handled by Kashier's secure hosted payment page. Your site never sees card details.

= How do I test payments? =

Set Environment to "Test" and use Kashier test card: 5123450000000008 (Exp: any future, CVV: 100)

== Screenshots ==

1. Payment methods in Tutor LMS settings
2. Card configuration settings
3. Checkout page with Kashier options
4. Kashier hosted payment page

== Changelog ==

= 1.0.0 =
* Initial release
* Card, Bank Installment, ValU, Souhoola, Aman, Mobile Wallet support
* Test and Live environment support
* Webhook integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Kashier Payment Gateway for Tutor LMS.

