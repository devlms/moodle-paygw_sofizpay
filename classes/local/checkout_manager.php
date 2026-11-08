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

namespace paygw_sofizpay\local;

use core_payment\helper;
use paygw_sofizpay\api\api_exception;
use paygw_sofizpay\api\client;
use stdClass;

/**
 * The life of a checkout: created as pending, then paid, failed or canceled, and never back.
 *
 * Every change of status happens here, under a lock per checkout, and only after reading the checkout from
 * SofizPay: a webhook or a redirect only says "look again", never "it is paid". A paid checkout is delivered
 * once, whichever of the webhook and the return page arrives first.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkout_manager {
    /** @var string Waiting for the payer. */
    public const PENDING = 'pending';
    /** @var string Paid and delivered. */
    public const PAID = 'paid';
    /** @var string The payment failed. */
    public const FAILED = 'failed';
    /** @var string The payer canceled, or the checkout expired. */
    public const CANCELED = 'canceled';

    /** @var string This gateway, when it delivers the order itself. */
    public const SELF = 'paygw_sofizpay';

    /** @var \curl|null For tests. */
    public static ?\curl $curl = null;

    /**
     * Start paying for an item: create the checkout and return the URL to send the payer to.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param int $userid
     * @param string $description
     * @param string $owner Component that delivers the order when paid (a hub), or this gateway.
     * @param string|null $reference The owner's own reference.
     * @param float|null $amount Dinars to charge, for an owner that priced the item itself.
     * @return string SofizPay's payment page URL.
     */
    public static function start(
        string $component,
        string $paymentarea,
        int $itemid,
        int $userid,
        string $description,
        string $owner = self::SELF,
        ?string $reference = null,
        ?float $amount = null
    ): string {
        global $DB;
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'sofizpay');
        if ($amount === null) {
            if ($payable->get_currency() !== 'DZD') {
                throw new \moodle_exception('error:currency', 'paygw_sofizpay');
            }
            $amount = helper::get_rounded_cost($payable->get_amount(), 'DZD', helper::get_gateway_surcharge('sofizpay'));
        }
        $now = time();
        $row = (object) [
            'accountid' => $payable->get_account_id(),
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'userid' => $userid,
            'amount' => round($amount, 2),
            'currency' => 'DZD',
            'method' => 'card',
            'status' => self::PENDING,
            'owner' => $owner,
            'reference' => $reference,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $row->id = $DB->insert_record('paygw_sofizpay', $row);
        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $payment = self::client($config)->create_payment(
            (float) $row->amount,
            fullname($user),
            (string) $user->email,
            (string) ($user->phone2 ?: $user->phone1),
            (new \moodle_url('/payment/gateway/sofizpay/return.php', ['id' => $row->id]))->out(false),
            (new \moodle_url('/payment/gateway/sofizpay/webhook.php'))->out(false),
            'moodle-' . $row->id,
            substr(current_language(), 0, 2)
        );
        $DB->update_record('paygw_sofizpay', (object) ['id' => $row->id, 'checkoutid' => $payment['order'],
            'timemodified' => time()]);
        return $payment['url'];
    }

    /**
     * Read an order from SofizPay and move it on: deliver it if paid, close it if it failed.
     *
     * @param int $id Our checkout row.
     * @return stdClass The row after the update.
     */
    public static function sync(int $id): stdClass {
        global $DB;
        $lock = \core\lock\lock_config::get_lock_factory('paygw_sofizpay')->get_lock('checkout_' . $id, 15);
        if (!$lock) {
            throw new \moodle_exception('error:busy', 'paygw_sofizpay');
        }
        try {
            $row = $DB->get_record('paygw_sofizpay', ['id' => $id], '*', MUST_EXIST);
            if ($row->status !== self::PENDING || empty($row->checkoutid)) {
                return $row;
            }
            $config = self::account_config((int) $row->accountid);
            $remote = self::client($config)->check($row->checkoutid);
            $status = $remote['orderStatus'] ?? null;
            if ($status !== null && (int) $status === client::PAID) {
                // Paid to this site's account, for at least the amount asked.
                $ours = (string) ($remote['destination_account'] ?? '') === (string) ($config['account'] ?? '');
                if (!$ours || (float) ($remote['Amount'] ?? -1) + 0.001 < (float) $row->amount) {
                    self::move($row, self::FAILED);
                    debugging("SofizPay order {$row->checkoutid}: account or amount does not match", DEBUG_NORMAL);
                    return $row;
                }
                // Saving the payment, delivering the order and marking the checkout paid succeed or fail together,
                // so a retry never records the payment twice.
                $transaction = $DB->start_delegated_transaction();
                try {
                    $row->paymentid = self::deliver($row);
                    self::move($row, self::PAID);
                    $transaction->allow_commit();
                } catch (\Throwable $e) {
                    $row->status = self::PENDING;
                    $transaction->rollback($e);
                }
            } else if ($status !== null && in_array((int) $status, client::FAILED, true)) {
                self::move($row, self::FAILED);
            }
            return $row;
        } finally {
            $lock->release();
        }
    }

    /**
     * Hand the paid order over: save the payment and deliver it here, or let the owner (a hub) do it.
     *
     * @param stdClass $row
     * @return int core payments id
     */
    private static function deliver(stdClass $row): int {
        if ($row->owner !== self::SELF) {
            $paymentid = component_class_callback(
                '\\' . $row->owner . '\\hub',
                'complete',
                [(string) $row->reference, 'sofizpay', (float) $row->amount, 'DZD'],
                null
            );
            if (!$paymentid) {
                throw new \moodle_exception('error:owner', 'paygw_sofizpay', '', $row->owner);
            }
            return (int) $paymentid;
        }
        $paymentid = helper::save_payment(
            (int) $row->accountid,
            $row->component,
            $row->paymentarea,
            (int) $row->itemid,
            (int) $row->userid,
            (float) $row->amount,
            'DZD',
            'sofizpay'
        );
        helper::deliver_order($row->component, $row->paymentarea, (int) $row->itemid, $paymentid, (int) $row->userid);
        return $paymentid;
    }

    /**
     * Record a new status. Only pending checkouts change.
     *
     * @param stdClass $row
     * @param string $status
     */
    private static function move(stdClass $row, string $status): void {
        global $DB;
        $row->status = $status;
        $row->timemodified = time();
        $DB->update_record('paygw_sofizpay', (object) ['id' => $row->id, 'status' => $status,
            'paymentid' => $row->paymentid ?? null, 'timemodified' => $row->timemodified]);
        $event = $status === self::PAID ? \paygw_sofizpay\event\payment_received::class
            : \paygw_sofizpay\event\payment_failed::class;
        $event::create([
            'context' => \context_system::instance(),
            'objectid' => $row->id,
            'relateduserid' => $row->userid,
            'other' => ['component' => $row->component, 'paymentarea' => $row->paymentarea, 'itemid' => (int) $row->itemid,
                'amount' => (float) $row->amount, 'currency' => $row->currency, 'gateway' => 'sofizpay'],
        ])->trigger();
    }

    /**
     * The checkout row for a SofizPay order number (cib_transaction_id).
     *
     * @param string $checkoutid
     * @return stdClass|null
     */
    public static function find(string $checkoutid): ?stdClass {
        global $DB;
        return $DB->get_record('paygw_sofizpay', ['checkoutid' => $checkoutid]) ?: null;
    }

    /**
     * The SofizPay settings of a payment account.
     *
     * @param int $accountid
     * @return array
     */
    public static function account_config(int $accountid): array {
        // All gateways, not only enabled ones: a checkout started before the gateway was switched off must still settle.
        $gateways = (new \core_payment\account($accountid))->get_gateways(false);
        return isset($gateways['sofizpay']) ? $gateways['sofizpay']->get_configuration() : [];
    }

    /**
     * An API client for some settings.
     *
     * @param array $config
     * @return client
     */
    private static function client(array $config): client {
        return new client((string) ($config['account'] ?? ''), (string) ($config['environment'] ?? 'sandbox'), self::$curl);
    }
}
