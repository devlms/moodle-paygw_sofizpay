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

namespace paygw_sofizpay\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy: checkouts, paid or not. Paid ones are exported and deleted with their payment through core
 * payments; checkouts that never became a payment live in the payer's user context.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    paygw_provider {
    /**
     * Describe the data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('paygw_sofizpay', [
            'userid' => 'privacy:metadata:paygw_sofizpay:userid',
            'checkoutid' => 'privacy:metadata:paygw_sofizpay:checkoutid',
            'amount' => 'privacy:metadata:paygw_sofizpay:amount',
            'method' => 'privacy:metadata:paygw_sofizpay:method',
            'status' => 'privacy:metadata:paygw_sofizpay:status',
            'timecreated' => 'privacy:metadata:paygw_sofizpay:timecreated',
        ], 'privacy:metadata:paygw_sofizpay');
        $collection->add_external_location_link('sofizpay', [
            'amount' => 'privacy:metadata:sofizpay:amount',
            'fullname' => 'privacy:metadata:sofizpay:fullname',
            'email' => 'privacy:metadata:sofizpay:email',
            'phone' => 'privacy:metadata:sofizpay:phone',
            'memo' => 'privacy:metadata:sofizpay:memo',
        ], 'privacy:metadata:sofizpay');
        return $collection;
    }

    /**
     * The payer's user context when they have checkouts that never became a payment.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            'SELECT ctx.id FROM {context} ctx
                              WHERE ctx.contextlevel = :level AND ctx.instanceid = :userid
                                AND EXISTS (SELECT 1 FROM {paygw_sofizpay} c WHERE c.userid = :uid AND c.paymentid IS NULL)',
            ['level' => CONTEXT_USER, 'userid' => $userid, 'uid' => $userid]
        );
        return $list;
    }

    /**
     * Users with unpaid checkouts, in their own context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof \context_user) {
            $userlist->add_from_sql(
                'userid',
                'SELECT userid FROM {paygw_sofizpay} WHERE userid = :u AND paymentid IS NULL',
                ['u' => $context->instanceid]
            );
        }
    }

    /**
     * Export unpaid checkouts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || (int) $context->instanceid !== $userid) {
                continue;
            }
            $rows = $DB->get_records_select('paygw_sofizpay', 'userid = ? AND paymentid IS NULL', [$userid], 'id');
            writer::with_context($context)->export_data([get_string('pluginname', 'paygw_sofizpay')], (object) [
                'checkouts' => array_values(array_map(fn($r) => [
                    'checkoutid' => (string) $r->checkoutid,
                    'amount' => (int) $r->amount . ' ' . $r->currency,
                    'method' => $r->method,
                    'status' => $r->status,
                    'timecreated' => transform::datetime($r->timecreated),
                ], $rows)),
            ]);
        }
    }

    /**
     * Delete all unpaid checkouts of the user whose context this is.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_user) {
            $DB->delete_records_select('paygw_sofizpay', 'userid = ? AND paymentid IS NULL', [$context->instanceid]);
        }
    }

    /**
     * Delete one user's unpaid checkouts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            self::delete_data_for_all_users_in_context($context);
        }
    }

    /**
     * Delete several users' unpaid checkouts.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        self::delete_data_for_all_users_in_context($userlist->get_context());
    }

    /**
     * Export the SofizPay side of a payment.
     *
     * @param \context $context
     * @param array $subcontext
     * @param \stdClass $payment
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;
        $row = $DB->get_record('paygw_sofizpay', ['paymentid' => $payment->id]);
        if (!$row) {
            return;
        }
        $subcontext[] = get_string('gatewayname', 'paygw_sofizpay');
        writer::with_context($context)->export_data($subcontext, (object) [
            'checkoutid' => (string) $row->checkoutid,
            'method' => $row->method,
            'amount' => (int) $row->amount . ' ' . $row->currency,
        ]);
    }

    /**
     * Delete the SofizPay side of payments.
     *
     * @param string $paymentsql
     * @param array $paymentparams
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;
        $DB->delete_records_select('paygw_sofizpay', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
