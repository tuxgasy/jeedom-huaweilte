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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class huaweilte extends eqLogic {
    public static function dependancy_info() {
        $return = array();
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__).'/dependance';
        if (exec(system::getCmdSudo() . ' python3 -c "import huawei_lte_api"; echo $') == 0) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return array('script' => __DIR__ . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $return['launchable'] = 'ok';

        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }

        $deviceUrl = config::byKey('deviceurl', __CLASS__);
        if (empty($deviceUrl)) {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __("L'URL du device n'est pas configuré", __FILE__);
        }

        return $return;
    }

    public static function deamon_start() {
        self::deamon_stop();

        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $deamon_path = realpath(__DIR__ . '/../../resources/huaweilted');
        $cmd = '/usr/bin/python3 ' . $deamon_path . '/huaweilted.py';
        $cmd .= ' --deviceurl ' . config::byKey('deviceurl', __CLASS__);
        $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__);
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon ' . __CLASS__ . ' : ' . $cmd);
        $result = exec($cmd . ' >> ' . log::getPathToLog(__CLASS__) . ' 2>&1 &');

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }

            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__, 'error', 'Impossible de lancer le démon ' . __CLASS__ .', vérifiez les paramètres', 'unableStartDeamon');
            return false;
        }

        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }

    public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }

        system::kill('huaweilted.py');
        system::fuserk(55100);

        sleep(1);
    }
}

class huaweilteCmd extends cmd {
    public function execute($_options = array()) {
        //
    }
}
