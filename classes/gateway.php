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

namespace paygw_sofizpay;

/**
 * SofizPay: CIB and EDAHABIA cards in Algerian dinars, on the SATIM network.
 *
 * @package    paygw_sofizpay
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Algerian dinars only.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['DZD'];
    }

    /**
     * Settings for a payment account.
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_sofizpay'), [
            'sandbox' => get_string('environment:sandbox', 'paygw_sofizpay'),
            'live' => get_string('environment:live', 'paygw_sofizpay'),
        ]);
        $mform->addHelpButton('environment', 'environment', 'paygw_sofizpay');
        $mform->addElement('text', 'account', get_string('account', 'paygw_sofizpay'), ['size' => 60]);
        $mform->setType('account', PARAM_ALPHANUM);
        $mform->addHelpButton('account', 'account', 'paygw_sofizpay');
    }

    /**
     * The merchant account must look like a Stellar public key.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if (!empty($data->enabled) && !api\client::valid_account((string) ($data->account ?? ''))) {
            $errors['account'] = get_string('error:account', 'paygw_sofizpay');
        }
    }
}
