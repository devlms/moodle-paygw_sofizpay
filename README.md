# SofizPay for Moodle (CIB and EDAHABIA)

[![CI](https://github.com/devlms/moodle-paygw_sofizpay/actions/workflows/ci.yml/badge.svg)](https://github.com/devlms/moodle-paygw_sofizpay/actions/workflows/ci.yml)
[![Maturity](https://img.shields.io/badge/maturity-alpha-orange)](version.php)

A Moodle payment gateway for [SofizPay](https://sofizpay.com/business/pay), for sites in Algeria. Learners pay course fees in dinars with a CIB bank card or an EDAHABIA card from Algérie Poste, on the SATIM payment page. Works with anything in Moodle that takes payments, such as enrolment on payment.

| Feature | Status |
| --- | --- |
| One-time payments in DZD, centimes included | Done |
| Sandbox and live environments | Done |
| Every payment read back from SofizPay before access is given: status, amount and receiving account | Done |
| Webhooks checked with SofizPay's RSA signature | Done |
| Each payment delivered once, whether the webhook or the return page arrives first | Done |
| Works on its own, and as a provider of the [DevLMS payment hub](https://github.com/devlms/moodle-paygw_payhub) | Done |
| Arabic, French and English | Done |
| Privacy API: payments exported and deleted | Done |
| Refunds | From the SofizPay dashboard |

## Set up

1. Install the plugin in `payment/gateway/sofizpay` and finish the upgrade.
2. Site administration > Plugins > Payment gateways: enable SofizPay.
3. Site administration > Payments > Payment accounts: open an account, then SofizPay. Choose Sandbox, paste your SofizPay merchant account (the public key starting with G, from your SofizPay dashboard) and enable it.
4. Price a course in DZD and choose that payment account.

No webhook to register: each payment tells SofizPay where to send its result. Test in the sandbox with SofizPay's published test card, then switch the account to Live.

## How it works

The learner chooses SofizPay in Moodle's payment window and goes to the SATIM page, where they pick CIB or EDAHABIA and pay. When SofizPay's webhook arrives, or the learner comes back, the plugin asks SofizPay for the order. Only an order SATIM reports as paid, to your merchant account, for at least the price, enrols the learner.

## Requirements

Moodle 4.5 to 5.3. A SofizPay merchant account.

## Licence

GPL v3 or later. Built by [DevLMS](https://devlms.com), who develop Moodle plugins. [Get a quote](https://devlms.com/#quote).
