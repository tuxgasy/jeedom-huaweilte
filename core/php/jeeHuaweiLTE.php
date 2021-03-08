<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'huaweilte')) {
    echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
    die();
}

if (init('test') != '') {
    echo 'OK';
    die();
}

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
    die();
}

$eqLogics = eqLogic::byType('huaweilte');
if (count($eqLogics) < 1) {
    die();
}

if (isset($result['messages'])) {
    foreach ($result['messages'] as $key => $datas) {
        $message = trim(secureXSS($datas['message']));
        $sender = trim(secureXSS($datas['sender']));

        if (empty($message) or empty($sender)) {
            continue;
        }

        $smsOk = false;
        foreach ($eqLogics as $eqLogic) {
            if (strpos($eqLogic->getConfiguration('authorizedSenders'), $sender) === false) {
                continue;
            }

            foreach ($eqLogic->getCmd() as $eqLogicCmd) {
                $smsOk = true;
                log::add('huaweilte', 'info', __('Message de ', __FILE__) . $sender . ' : ' . $message);

                $cmd = $eqLogicCmd->getEqlogic()->getCmd('info', 'smsLastMessage');
                $cmd->event($message);

                $cmd = $eqLogicCmd->getEqlogic()->getCmd('info', 'smsLastSender');
                $cmd->event($sender);
                break;
            }
        }

        if (!$smsOk) {
            log::add('huaweilte', 'info', __('Message d\'un numéro non autorisé ', __FILE__) . secureXSS($sender) . ' : ' . secureXSS($message));
        }
    }
}
