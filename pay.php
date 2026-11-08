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
 * Show the price, then go to the SATIM card page through SofizPay.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_sofizpay\local\checkout_manager;

require_once(__DIR__ . '/../../../config.php');

require_login();
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = optional_param('description', '', PARAM_TEXT);

$payable = helper::get_payable($component, $paymentarea, $itemid);
$cost = helper::get_cost_as_string($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('sofizpay'));

$params = ['component' => $component, 'paymentarea' => $paymentarea, 'itemid' => $itemid, 'description' => $description];
$PAGE->set_url(new moodle_url('/payment/gateway/sofizpay/pay.php', $params));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('paywith', 'paygw_sofizpay'));
$PAGE->set_heading(get_string('paywith', 'paygw_sofizpay'));

if (optional_param('go', 0, PARAM_BOOL) && confirm_sesskey()) {
    try {
        $phone = optional_param('phone', '', PARAM_RAW_TRIMMED);
        $url = checkout_manager::start(
            $component,
            $paymentarea,
            $itemid,
            (int) $USER->id,
            $description,
            checkout_manager::SELF,
            null,
            null,
            $phone
        );
    } catch (\moodle_exception $e) {
        redirect($PAGE->url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
    redirect($url);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('paygw_sofizpay/pay', [
    'description' => $description,
    'cost' => $cost,
    'action' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'phone' => checkout_manager::phone((string) ($USER->phone2 ?: $USER->phone1)),
    'hidden' => array_map(fn($k, $v) => ['name' => $k, 'value' => $v], array_keys($params), $params),
]);
echo $OUTPUT->footer();
