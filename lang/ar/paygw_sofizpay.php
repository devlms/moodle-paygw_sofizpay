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

$string['account'] = 'حساب التاجر';
$string['account_help'] = 'حساب التاجر في SofizPay: المفتاح العام الذي يبدأ بالحرف G، والظاهر في لوحة تحكم SofizPay. يُتحقق من كل دفعة مقابل هذا الحساب، وتُرفض أي دفعة إلى حساب آخر.';
$string['back'] = 'رجوع';
$string['canceled'] = 'أُلغيت عملية الدفع. لم يُخصم أي مبلغ.';
$string['checkagain'] = 'تحقق مرة أخرى';
$string['continue'] = 'المتابعة إلى الدفع';
$string['environment'] = 'البيئة';
$string['environment:live'] = 'الإنتاج';
$string['environment:sandbox'] = 'بيئة الاختبار';
$string['environment_help'] = 'بيئة الاختبار تستخدم بيئة SofizPay التجريبية حيث لا تتحرك أي أموال. انتقل إلى الإنتاج بعد اعتماد حساب التاجر.';
$string['error:account'] = 'أدخل حساب التاجر في SofizPay: 56 حرفًا تبدأ بالحرف G.';
$string['error:api'] = 'SofizPay: {$a}';
$string['error:busy'] = 'يجري التحقق من هذه الدفعة الآن. حاول مرة أخرى بعد لحظات.';
$string['error:currency'] = 'يقبل SofizPay الدينار الجزائري (DZD) فقط.';
$string['error:generic'] = '{$a}';
$string['error:owner'] = 'تمت عملية الدفع، لكن {$a} لم يتمكن من تسجيلها. تواصل مع مدير الموقع.';
$string['error:phone'] = 'يطلب SofizPay رقم هاتف للدفع بالبطاقة. أدخل رقمًا أو أضفه إلى ملفك الشخصي، ثم حاول مرة أخرى.';
$string['event:paymentfailed'] = 'فشل الدفع عبر SofizPay أو أُلغي';
$string['event:paymentreceived'] = 'تم استلام دفعة عبر SofizPay';
$string['failed'] = 'لم تتم عملية الدفع. لم يُخصم أي مبلغ، ويمكنك المحاولة مرة أخرى.';
$string['gatewaydescription'] = 'بطاقات CIB والذهبية بالدينار الجزائري عبر SofizPay.';
$string['gatewayname'] = 'بطاقة CIB أو الذهبية (SofizPay)';
$string['method:card'] = 'بطاقة CIB أو الذهبية';
$string['paid'] = 'تم استلام الدفعة. شكرًا لك.';
$string['paywith'] = 'الدفع بالبطاقة';
$string['phone'] = 'رقم الهاتف';
$string['phone_help'] = 'يطلبه SofizPay مع كل دفع بالبطاقة.';
$string['pluginname'] = 'SofizPay';
$string['pluginname_desc'] = 'بطاقات CIB والذهبية بالدينار الجزائري عبر SofizPay.';
$string['privacy:metadata:paygw_sofizpay'] = 'مدفوعات SofizPay التي بدأت على هذا الموقع.';
$string['privacy:metadata:paygw_sofizpay:amount'] = 'المبلغ المخصوم بالدينار.';
$string['privacy:metadata:paygw_sofizpay:checkoutid'] = 'رقم الطلب لدى SofizPay.';
$string['privacy:metadata:paygw_sofizpay:method'] = 'طريقة الدفع: بالبطاقة.';
$string['privacy:metadata:paygw_sofizpay:status'] = 'حالة الدفعة: قيد الانتظار أو مدفوعة أو فاشلة أو ملغاة.';
$string['privacy:metadata:paygw_sofizpay:timecreated'] = 'وقت بدء الدفع.';
$string['privacy:metadata:paygw_sofizpay:userid'] = 'الشخص الذي يدفع.';
$string['privacy:metadata:sofizpay'] = 'لإتمام الدفع يُرسل إلى SofizPay المبلغ واسم الدافع وبريده الإلكتروني وهاتفه ومرجع للعملية. يُدخل الدافع بيانات بطاقته في صفحة الدفع SATIM وليس في Moodle.';
$string['privacy:metadata:sofizpay:amount'] = 'المبلغ المطلوب دفعه.';
$string['privacy:metadata:sofizpay:email'] = 'البريد الإلكتروني للدافع.';
$string['privacy:metadata:sofizpay:fullname'] = 'الاسم الكامل للدافع.';
$string['privacy:metadata:sofizpay:memo'] = 'مرجع لهذه الدفعة في Moodle.';
$string['privacy:metadata:sofizpay:phone'] = 'رقم هاتف الدافع.';
$string['redirectnote'] = 'تختار CIB أو الذهبية وتُدخل بيانات بطاقتك في صفحة SATIM الآمنة، ثم تعود إلى هنا.';
$string['waiting'] = 'لم يؤكد SofizPay هذه الدفعة بعد. يستغرق ذلك عادة بضع ثوانٍ.';
