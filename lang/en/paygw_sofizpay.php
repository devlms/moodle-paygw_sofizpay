<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for paygw_sofizpay.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['account'] = 'Merchant account';
$string['account_help'] = 'Your SofizPay merchant account: the public key starting with G, shown in your SofizPay dashboard. Payments are checked against it, so a payment to any other account is refused.';
$string['back'] = 'Back';
$string['canceled'] = 'The payment was canceled. Nothing was charged.';
$string['checkagain'] = 'Check again';
$string['continue'] = 'Continue to payment';
$string['environment'] = 'Environment';
$string['environment:live'] = 'Live';
$string['environment:sandbox'] = 'Sandbox (test)';
$string['environment_help'] = 'Sandbox uses SofizPay\'s test environment, where no money moves. Switch to Live once your merchant account is approved.';
$string['error:account'] = 'Enter your SofizPay merchant account: 56 characters starting with G.';
$string['error:api'] = 'SofizPay: {$a}';
$string['error:busy'] = 'This payment is being checked right now. Try again in a moment.';
$string['error:currency'] = 'SofizPay takes Algerian dinars (DZD) only.';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'The payment went through, but {$a} could not record it. Contact the site administrator.';
$string['error:phone'] = 'SofizPay needs a phone number for card payments. Enter one, or add it to your profile, then try again.';
$string['event:paymentfailed'] = 'SofizPay payment failed or canceled';
$string['event:paymentreceived'] = 'SofizPay payment received';
$string['failed'] = 'The payment did not go through. Nothing was charged; you can try again.';
$string['gatewaydescription'] = 'CIB and EDAHABIA cards in Algerian dinars, through SofizPay.';
$string['gatewayname'] = 'CIB or EDAHABIA card (SofizPay)';
$string['method:card'] = 'CIB or EDAHABIA card';
$string['paid'] = 'Payment received. Thank you.';
$string['paywith'] = 'Pay by card';
$string['phone'] = 'Phone number';
$string['phone_help'] = 'SofizPay asks for it with every card payment.';
$string['pluginname'] = 'SofizPay';
$string['pluginname_desc'] = 'CIB and EDAHABIA cards in Algerian dinars, through SofizPay.';
$string['privacy:metadata:paygw_sofizpay'] = 'SofizPay payments started on this site.';
$string['privacy:metadata:paygw_sofizpay:amount'] = 'The amount charged, in dinars.';
$string['privacy:metadata:paygw_sofizpay:checkoutid'] = 'The order number at SofizPay.';
$string['privacy:metadata:paygw_sofizpay:method'] = 'How the payer paid: by card.';
$string['privacy:metadata:paygw_sofizpay:status'] = 'Whether the payment is pending, paid, failed or canceled.';
$string['privacy:metadata:paygw_sofizpay:timecreated'] = 'When the payment was started.';
$string['privacy:metadata:paygw_sofizpay:userid'] = 'The person paying.';
$string['privacy:metadata:sofizpay'] = 'To take a payment, the amount, the payer\'s name, email and phone, and a reference are sent to SofizPay. The payer enters their card details on the SATIM payment page, not in Moodle.';
$string['privacy:metadata:sofizpay:amount'] = 'The amount to pay.';
$string['privacy:metadata:sofizpay:email'] = 'The payer\'s email address.';
$string['privacy:metadata:sofizpay:fullname'] = 'The payer\'s full name.';
$string['privacy:metadata:sofizpay:memo'] = 'A reference to this payment in Moodle.';
$string['privacy:metadata:sofizpay:phone'] = 'The payer\'s phone number.';
$string['redirectnote'] = 'You choose CIB or EDAHABIA and enter your card details on the secure SATIM page, then come back here.';
$string['waiting'] = 'SofizPay has not confirmed this payment yet. This usually takes a few seconds.';
