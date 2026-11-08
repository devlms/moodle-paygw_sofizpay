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

use paygw_sofizpay\api\client;

/**
 * Handles one SofizPay webhook. Kept apart from webhook.php so it can be tested without HTTP.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class webhook {
    /**
     * Check and act on a webhook.
     *
     * @param string $body Raw request body.
     * @return int HTTP status to answer with.
     */
    public static function handle(string $body): int {
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            return 400;
        }
        if (!client::valid_signature($payload)) {
            return 403;
        }
        $order = (string) ($payload['cib_transaction_id'] ?? $payload['order_number'] ?? '');
        $row = $order === '' ? null : checkout_manager::find($order);
        if (!$row) {
            // Not an order this site started: acknowledge so SofizPay stops retrying.
            return 200;
        }
        try {
            // The signed message is only a trigger: the status is read back from SofizPay.
            checkout_manager::sync((int) $row->id);
        } catch (\moodle_exception $e) {
            return 503;
        }
        return 200;
    }
}
