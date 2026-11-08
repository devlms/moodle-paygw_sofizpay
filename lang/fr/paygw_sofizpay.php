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

$string['account'] = 'Compte marchand';
$string['account_help'] = 'Votre compte marchand SofizPay : la clé publique qui commence par G, affichée dans votre tableau de bord SofizPay. Chaque paiement est vérifié contre ce compte ; un paiement vers un autre compte est refusé.';
$string['back'] = 'Retour';
$string['canceled'] = 'Le paiement a été annulé. Rien n\'a été débité.';
$string['checkagain'] = 'Vérifier à nouveau';
$string['continue'] = 'Continuer vers le paiement';
$string['environment'] = 'Environnement';
$string['environment:live'] = 'Production';
$string['environment:sandbox'] = 'Sandbox (test)';
$string['environment_help'] = 'Le mode sandbox utilise l\'environnement de test de SofizPay, sans mouvement d\'argent. Passez en production une fois votre compte marchand validé.';
$string['error:account'] = 'Saisissez votre compte marchand SofizPay : 56 caractères commençant par G.';
$string['error:api'] = 'SofizPay : {$a}';
$string['error:busy'] = 'Ce paiement est en cours de vérification. Réessayez dans un instant.';
$string['error:currency'] = 'SofizPay accepte uniquement le dinar algérien (DZD).';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'Le paiement est passé, mais {$a} n\'a pas pu l\'enregistrer. Contactez l\'administrateur du site.';
$string['error:phone'] = 'SofizPay demande un numéro de téléphone pour les paiements par carte. Saisissez-en un, ou ajoutez-le à votre profil, puis réessayez.';
$string['event:paymentfailed'] = 'Paiement SofizPay échoué ou annulé';
$string['event:paymentreceived'] = 'Paiement SofizPay reçu';
$string['failed'] = 'Le paiement n\'a pas abouti. Rien n\'a été débité ; vous pouvez réessayer.';
$string['gatewaydescription'] = 'Cartes CIB et EDAHABIA en dinars algériens, via SofizPay.';
$string['gatewayname'] = 'Carte CIB ou EDAHABIA (SofizPay)';
$string['method:card'] = 'Carte CIB ou EDAHABIA';
$string['paid'] = 'Paiement reçu. Merci.';
$string['paywith'] = 'Payer par carte';
$string['phone'] = 'Numéro de téléphone';
$string['phone_help'] = 'SofizPay le demande pour chaque paiement par carte.';
$string['pluginname'] = 'SofizPay';
$string['pluginname_desc'] = 'Cartes CIB et EDAHABIA en dinars algériens, via SofizPay.';
$string['privacy:metadata:paygw_sofizpay'] = 'Les paiements SofizPay lancés sur ce site.';
$string['privacy:metadata:paygw_sofizpay:amount'] = 'Le montant débité, en dinars.';
$string['privacy:metadata:paygw_sofizpay:checkoutid'] = 'Le numéro de commande chez SofizPay.';
$string['privacy:metadata:paygw_sofizpay:method'] = 'Le moyen de paiement : par carte.';
$string['privacy:metadata:paygw_sofizpay:status'] = 'Si le paiement est en attente, payé, échoué ou annulé.';
$string['privacy:metadata:paygw_sofizpay:timecreated'] = 'Quand le paiement a été lancé.';
$string['privacy:metadata:paygw_sofizpay:userid'] = 'La personne qui paie.';
$string['privacy:metadata:sofizpay'] = 'Pour encaisser un paiement, le montant, le nom, l\'adresse e-mail et le téléphone du payeur ainsi qu\'une référence sont envoyés à SofizPay. Le payeur saisit ses données de carte sur la page de paiement SATIM, pas dans Moodle.';
$string['privacy:metadata:sofizpay:amount'] = 'Le montant à payer.';
$string['privacy:metadata:sofizpay:email'] = 'L\'adresse e-mail du payeur.';
$string['privacy:metadata:sofizpay:fullname'] = 'Le nom complet du payeur.';
$string['privacy:metadata:sofizpay:memo'] = 'Une référence à ce paiement dans Moodle.';
$string['privacy:metadata:sofizpay:phone'] = 'Le numéro de téléphone du payeur.';
$string['redirectnote'] = 'Vous choisissez CIB ou EDAHABIA et saisissez vos données de carte sur la page sécurisée SATIM, puis vous revenez ici.';
$string['waiting'] = 'SofizPay n\'a pas encore confirmé ce paiement. Cela prend en général quelques secondes.';
