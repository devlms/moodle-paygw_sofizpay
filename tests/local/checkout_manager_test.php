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
 * Payments from start to delivery, with SofizPay's answers mocked.
 *
 * @package    paygw_sofizpay
 * @category   test
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \paygw_sofizpay\local\checkout_manager
 * @covers     \paygw_sofizpay\local\webhook
 * @covers     \paygw_sofizpay\api\client
 * @covers     \paygw_sofizpay\gateway
 * @covers     \paygw_sofizpay\event\payment_received
 * @covers     \paygw_sofizpay\event\payment_failed
 */
final class checkout_manager_test extends \advanced_testcase {
    /** @var string A merchant account (Stellar public key shape). */
    private const ACCOUNT = 'GBTESTACCOUNTAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    /** @var string The order number SofizPay gives back. */
    private const ORDER = '4719330589';
    /** @var \stdClass */
    private \stdClass $course;
    /** @var \stdClass */
    private \stdClass $student;
    /** @var int enrol_fee instance id */
    private int $instanceid;
    /** @var \OpenSSLAsymmetricKey|null Key that signs test webhooks. */
    private $signer = null;

    protected function setUp(): void {
        global $CFG, $DB;
        parent::setUp();
        $this->resetAfterTest();
        require_once($CFG->libdir . '/filelib.php');
        set_config('enrol_plugins_enabled', 'manual,fee');
        \core\plugininfo\paygw::enable_plugin('sofizpay', 1);
        $gen = $this->getDataGenerator();
        $account = $gen->get_plugin_generator('core_payment')->create_payment_account();
        \core_payment\helper::save_payment_gateway((object) ['accountid' => $account->get('id'), 'gateway' => 'sofizpay',
            'enabled' => 1, 'config' => json_encode(['account' => self::ACCOUNT, 'environment' => 'sandbox'])]);
        $this->course = $gen->create_course();
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $this->instanceid = (int) enrol_get_plugin('fee')->add_instance($this->course, [
            'cost' => 2500.40, 'currency' => 'DZD', 'customint1' => $account->get('id'), 'roleid' => $studentrole,
        ]);
        $this->student = $gen->create_user(['phone1' => '0555000000']);
        $this->setUser($this->student);
        checkout_manager::$curl = null;
        client::$webhookkey = null;
    }

    protected function tearDown(): void {
        client::$webhookkey = null;
        parent::tearDown();
    }

    /**
     * A curl that answers with these bodies in order.
     *
     * @param array ...$answers Decoded JSON answers.
     */
    private function answers(array ...$answers): void {
        // Moodle's mock is a stack (array_pop): push the last answer first so they come back in order.
        foreach (array_reverse($answers) as $answer) {
            \curl::mock_response(json_encode($answer));
        }
    }

    /**
     * SofizPay's answer to an order check.
     *
     * @param int|null $status SATIM orderStatus
     * @param string $amount
     * @param string $account
     * @return array
     */
    private static function order(?int $status, string $amount = '2500.40', string $account = self::ACCOUNT): array {
        return ['order_number' => self::ORDER, 'orderStatus' => $status, 'destination_account' => $account,
            'Amount' => $amount];
    }

    /**
     * Start a payment for the student and return its row.
     *
     * @param string $order
     * @return \stdClass
     */
    private function started(string $order = self::ORDER): \stdClass {
        global $DB;
        $url = 'https://sofizpay.com/sandbox/payment/?mdOrder=' . $order;
        $this->answers(['success' => true, 'cib_transaction_id' => $order, 'payment_url' => $url,
            'amount' => '2500.40', 'status' => 'pending_user_transfer_start']);
        $this->assertSame($url, checkout_manager::start(
            'enrol_fee',
            'fee',
            $this->instanceid,
            (int) $this->student->id,
            'Physics'
        ));
        return $DB->get_record('paygw_sofizpay', ['checkoutid' => $order], '*', MUST_EXIST);
    }

    /**
     * A webhook body signed like SofizPay signs it, with a test key.
     *
     * @param array $payload
     * @param bool $forged Sign with another key.
     * @return string
     */
    private function signed(array $payload, bool $forged = false): string {
        if (!$this->signer) {
            $this->signer = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            client::$webhookkey = openssl_pkey_get_details($this->signer)['key'];
        }
        $key = $forged ? openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA])
            : $this->signer;
        $payload['message'] = 'Payment ' . ($payload['cib_transaction_id'] ?? '') . ' done';
        openssl_sign($payload['message'], $raw, $key, OPENSSL_ALGO_SHA256);
        $payload['signature_url_safe'] = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
        return json_encode($payload);
    }

    public function test_start_records_a_pending_payment_in_dinars_and_centimes(): void {
        $row = $this->started();
        $this->assertSame(checkout_manager::PENDING, $row->status);
        $this->assertEquals(2500.40, (float) $row->amount);
        $this->assertSame(checkout_manager::SELF, $row->owner);
    }

    public function test_a_paid_order_enrols_the_student_once(): void {
        global $DB;
        $row = $this->started();
        $this->answers(self::order(client::PAID));
        $sink = $this->redirectEvents();
        $after = checkout_manager::sync((int) $row->id);
        $received = array_values(array_filter($sink->get_events(), fn($e) => $e instanceof \paygw_sofizpay\event\payment_received));
        $this->assertCount(1, $received);
        $this->assertEquals(2500.40, $received[0]->other['amount']);
        $this->assertNotEmpty($received[0]->get_description());
        $sink->close();
        $this->assertSame(checkout_manager::PAID, $after->status);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course->id), $this->student));
        $payment = $DB->get_record('payments', ['id' => $after->paymentid], '*', MUST_EXIST);
        $this->assertSame('sofizpay', $payment->gateway);
        $this->assertSame('DZD', $payment->currency);
        // A second webhook or return visit changes nothing and asks SofizPay nothing.
        checkout_manager::sync((int) $row->id);
        $this->assertSame(1, $DB->count_records('payments'));
    }

    public function test_a_smaller_amount_or_another_account_is_not_delivered(): void {
        $row = $this->started();
        $this->answers(self::order(client::PAID, '10.00'));
        $this->assertSame(checkout_manager::FAILED, checkout_manager::sync((int) $row->id)->status);
        $this->assertDebuggingCalled();
        $other = $this->started('4719330590');
        $this->answers(self::order(client::PAID, '2500.40', 'GBOTHERACCOUNTBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB'));
        $this->assertSame(checkout_manager::FAILED, checkout_manager::sync((int) $other->id)->status);
        $this->assertDebuggingCalled();
        $this->assertFalse(is_enrolled(\context_course::instance($this->course->id), $this->student));
    }

    public function test_unpaid_and_declined_orders(): void {
        $row = $this->started();
        $this->answers(self::order(null));
        $this->assertSame(checkout_manager::PENDING, checkout_manager::sync((int) $row->id)->status);
        $this->answers(self::order(6));
        $this->assertSame(checkout_manager::FAILED, checkout_manager::sync((int) $row->id)->status);
    }

    public function test_a_phone_is_required_and_can_be_typed_on_the_pay_page(): void {
        global $DB;
        // SofizPay refuses payments without a phone: stop before calling it, with a message the learner can act on.
        $DB->set_field('user', 'phone1', '', ['id' => $this->student->id]);
        try {
            checkout_manager::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'Physics');
            $this->fail('Started a payment without a phone');
        } catch (\moodle_exception $e) {
            $this->assertSame('error:phone', $e->errorcode);
        }
        $this->assertSame(0, $DB->count_records('paygw_sofizpay'));
        $this->answers(['success' => true, 'cib_transaction_id' => '777', 'payment_url' => 'https://sofizpay.com/p/777']);
        $url = checkout_manager::start(
            'enrol_fee',
            'fee',
            $this->instanceid,
            (int) $this->student->id,
            'Physics',
            checkout_manager::SELF,
            null,
            null,
            '+213 555 12 34 56'
        );
        $this->assertSame('https://sofizpay.com/p/777', $url);
    }

    public function test_only_dinars_are_taken(): void {
        global $DB;
        $DB->set_field('enrol', 'currency', 'EUR', ['id' => $this->instanceid]);
        $this->expectException(\moodle_exception::class);
        checkout_manager::start('enrol_fee', 'fee', $this->instanceid, (int) $this->student->id, 'Physics');
    }

    public function test_webhook_checks_the_rsa_signature_then_reads_sofizpay(): void {
        $this->started();
        $this->assertSame(400, webhook::handle('not json'));
        $this->assertSame(403, webhook::handle($this->signed(['cib_transaction_id' => self::ORDER], true)));
        $this->assertSame(403, webhook::handle(json_encode(['cib_transaction_id' => self::ORDER])));
        $unknown = $this->signed(['cib_transaction_id' => '999']);
        $this->assertSame(200, webhook::handle($unknown), 'Unknown orders are acknowledged.');
        $status = checkout_manager::find(self::ORDER)->status;
        $this->assertSame(checkout_manager::PENDING, $status, 'A forged webhook changed nothing.');
        $this->answers(self::order(client::PAID));
        $this->assertSame(200, webhook::handle($this->signed(['cib_transaction_id' => self::ORDER])));
        $this->assertSame(checkout_manager::PAID, checkout_manager::find(self::ORDER)->status);
    }

    public function test_client_accounts_and_errors(): void {
        $this->assertTrue(client::valid_account(self::ACCOUNT));
        $this->assertFalse(client::valid_account('G123'));
        $this->assertFalse(client::valid_account('S' . substr(self::ACCOUNT, 1)), 'A secret seed is not an account.');
        $client = new client(self::ACCOUNT, 'sandbox', new \curl());
        try {
            $client->check('1; drop');
            $this->fail('Accepted a malformed order number');
        } catch (\paygw_sofizpay\api\api_exception $e) {
            $this->assertStringContainsString('bad order number', $e->getMessage());
        }
        $this->answers(['success' => false, 'error' => 'Amount too small']);
        try {
            $client->create_payment(1, 'A', 'a@example.com', '', 'https://x', 'https://y', 'm', 'fr');
            $this->fail('Accepted a failed answer');
        } catch (\paygw_sofizpay\api\api_exception $e) {
            $this->assertStringContainsString('Amount too small', $e->getMessage());
        }
        $this->assertSame(['DZD'], \paygw_sofizpay\gateway::get_supported_currencies());
    }

    public function test_through_the_hub_the_hub_saves_and_delivers(): void {
        global $DB;
        if (!class_exists(\paygw_payhub\hub::class)) {
            $this->markTestSkipped('The payment hub is not installed.');
        }
        \core\plugininfo\paygw::enable_plugin('payhub', 1);
        $accountid = (int) $DB->get_field('payment_gateways', 'accountid', ['gateway' => 'sofizpay']);
        \core_payment\helper::save_payment_gateway((object) ['accountid' => $accountid, 'gateway' => 'payhub',
            'enabled' => 1, 'config' => json_encode(['use_sofizpay' => 1])]);
        $this->answers(['success' => true, 'cib_transaction_id' => '555', 'payment_url' => 'https://sofizpay.com/p/555']);
        $url = \paygw_payhub\hub::start(
            'enrol_fee',
            'fee',
            $this->instanceid,
            (int) $this->student->id,
            'sofizpay',
            'card',
            'Physics'
        );
        $this->assertSame('https://sofizpay.com/p/555', $url);
        $row = checkout_manager::find('555');
        $this->assertSame('paygw_payhub', $row->owner);
        $this->answers(self::order(client::PAID));
        $after = checkout_manager::sync((int) $row->id);
        $this->assertSame(checkout_manager::PAID, $after->status);
        $this->assertTrue(is_enrolled(\context_course::instance($this->course->id), $this->student));
        $this->assertSame('paid', $DB->get_field('paygw_payhub', 'status', ['reference' => $row->reference]));
        $this->assertSame('sofizpay', $DB->get_field('payments', 'gateway', ['id' => $after->paymentid]));
    }
}
