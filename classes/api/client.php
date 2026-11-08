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

namespace paygw_sofizpay\api;

/**
 * SofizPay CIB API: create a payment link, read an order's status, check a webhook's RSA signature.
 *
 * These are the endpoints SofizPay's official PHP SDK uses. The merchant is identified by the public key of its
 * SofizPay (Stellar) account; no secret is sent. Every call goes through Moodle's curl.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /** @var string[] Create and check endpoints, live. */
    public const LIVE = ['create' => 'https://www.sofizpay.com/make-cib-transaction/',
        'check' => 'https://www.sofizpay.com/cib-transaction-check/'];
    /** @var string[] Create and check endpoints, sandbox. */
    public const SANDBOX = ['create' => 'https://sofizpay.com/sandbox/make-cib-transaction/',
        'check' => 'https://sofizpay.com/sandbox/cib-transaction-check/'];
    /** @var int SATIM order status of a paid order. */
    public const PAID = 2;
    /** @var int[] SATIM order statuses that end an order without payment: reversed, refunded, declined. */
    public const FAILED = [3, 4, 6];
    /** @var string SofizPay's public key for webhook signatures (from its official SDK). */
    public const WEBHOOK_KEY = "-----BEGIN PUBLIC KEY-----\n" .
        "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1N+bDPxpqeB9QB0affr/\n" .
        "02aeRXAAnqHuLrgiUlVNdXtF7t+2w8pnEg+m9RRlc+4YEY6UyKTUjVe6k7v2p8Jj\n" .
        "UItk/fMNOEg/zY222EbqsKZ2mF4hzqgyJ3QHPXjZEEqABkbcYVv4ZyV2Wq0x0ykI\n" .
        "+Hy/5YWKeah4RP2uEML1FlXGpuacnMXpW6n36dne3fUN+OzILGefeRpmpnSGO5+i\n" .
        "JmpF2mRdKL3hs9WgaLSg6uQyrQuJA9xqcCpUmpNbIGYXN9QZxjdyRGnxivTE8awx\n" .
        "THV3WRcKrP2krz3ruRGF6yP6PVHEuPc0YDLsYjV5uhfs7JtIksNKhRRAQ16bAsj/\n" .
        "9wIDAQAB\n" .
        "-----END PUBLIC KEY-----";

    /** @var string|null Another public key to check webhooks against, for tests. */
    public static ?string $webhookkey = null;

    /** @var string[] */
    private array $urls;

    /**
     * Constructor.
     *
     * @param string $account The merchant's SofizPay public key (G...).
     * @param string $environment sandbox or live
     * @param \curl|null $curl For tests.
     */
    public function __construct(
        /** @var string The merchant account. */
        private readonly string $account,
        string $environment,
        /** @var \curl|null Curl to use, for tests. */
        private readonly ?\curl $curl = null
    ) {
        if (!self::valid_account($account)) {
            throw new api_exception(get_string('error:account', 'paygw_sofizpay'));
        }
        $this->urls = $environment === 'live' ? self::LIVE : self::SANDBOX;
    }

    /**
     * Whether a string looks like a SofizPay (Stellar) public key.
     *
     * @param string $account
     * @return bool
     */
    public static function valid_account(string $account): bool {
        return (bool) preg_match('/^G[A-Z2-7]{55}$/', $account);
    }

    /**
     * Create a CIB/EDAHABIA payment link.
     *
     * @param float $amount Dinars.
     * @param string $fullname Payer's name.
     * @param string $email Payer's email.
     * @param string $phone Payer's phone, or empty.
     * @param string $returnurl Where SofizPay sends the payer afterwards.
     * @param string $webhookurl Where SofizPay posts the result.
     * @param string $memo Our reference.
     * @param string $language ar, fr or en
     * @return array With order (cib_transaction_id) and url (payment_url).
     */
    public function create_payment(
        float $amount,
        string $fullname,
        string $email,
        string $phone,
        string $returnurl,
        string $webhookurl,
        string $memo,
        string $language
    ): array {
        $data = $this->request($this->urls['create'], [
            'account' => $this->account,
            'amount' => number_format($amount, 2, '.', ''),
            'full_name' => shorten_text($fullname, 100),
            'phone' => $phone,
            'email' => $email,
            'return_url' => $returnurl,
            'webhook_url' => $webhookurl,
            'memo' => $memo,
            'language' => in_array($language, ['ar', 'fr', 'en'], true) ? $language : 'fr',
            'redirect' => 'no',
        ]);
        $url = (string) ($data['payment_url'] ?? $data['cib_response']['formUrl'] ?? '');
        $order = (string) ($data['cib_transaction_id'] ?? '');
        if (empty($data['success']) || $url === '' || $order === '') {
            throw new api_exception(get_string('error:api', 'paygw_sofizpay', (string) ($data['error'] ?? 'no payment url')));
        }
        return ['order' => $order, 'url' => $url];
    }

    /**
     * Read an order's status: the only source of truth for it.
     *
     * @param string $order cib_transaction_id
     * @return array orderStatus, Amount, destination_account and SATIM details
     */
    public function check(string $order): array {
        if (!preg_match('/^[0-9]{1,30}$/', $order)) {
            throw new api_exception(get_string('error:api', 'paygw_sofizpay', 'bad order number'));
        }
        return $this->request($this->urls['check'], ['order_number' => $order]);
    }

    /**
     * Whether a webhook payload carries SofizPay's RSA-SHA256 signature over its message.
     *
     * @param array $payload Decoded webhook body.
     * @return bool
     */
    public static function valid_signature(array $payload): bool {
        $message = (string) ($payload['message'] ?? '');
        $signature = (string) ($payload['signature_url_safe'] ?? $payload['signature'] ?? '');
        if ($message === '' || $signature === '') {
            return false;
        }
        $base64 = str_pad(strtr($signature, '-_', '+/'), (int) ceil(strlen($signature) / 4) * 4, '=');
        $raw = base64_decode($base64, true);
        return $raw !== false && openssl_verify($message, $raw, self::$webhookkey ?? self::WEBHOOK_KEY, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * One GET call with query parameters, as SofizPay's API expects.
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    private function request(string $url, array $params): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        $curl = $this->curl ?? new \curl();
        $curl->setHeader(['Accept: application/json', 'User-Agent: Moodle-paygw_sofizpay']);
        $raw = $curl->get($url, $params, ['CURLOPT_TIMEOUT' => 25, 'CURLOPT_CONNECTTIMEOUT' => 8]);
        if ($curl->get_errno()) {
            throw new api_exception(get_string('error:api', 'paygw_sofizpay', $curl->error));
        }
        $status = (int) ($curl->get_info()['http_code'] ?? 0);
        $data = json_decode((string) $raw, true);
        if ($status >= 400 || !is_array($data)) {
            $why = is_array($data) && !empty($data['error']) ? (string) $data['error'] : "HTTP $status";
            throw new api_exception(get_string('error:api', 'paygw_sofizpay', shorten_text($why, 200)));
        }
        return $data;
    }
}
