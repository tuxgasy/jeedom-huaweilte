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
    /*     * ***********************Methode static*************************** */

    public static function dependancy_info() {
        $return = array();
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__).'/dependance';
        if (exec(system::getCmdSudo() . ' python3 -c "import huawei_lte_api"; echo $?') == 0) {
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
        $cmd .= ' --cycle ' . config::byKey('cycle', __CLASS__);
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__);
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/' . __CLASS__ . '/core/php/jeeHuaweiLTE.php';
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

    /*     * *********************Méthodes d'instance************************* */

    public function postSave() {
        $cmd = $this->getCmd(null, 'signal');
        if (!is_object($cmd)) {
            $cmd = new huaweilteCmd();
            $cmd->setLogicalId('signal');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Signal', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('numeric');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setConfiguration('minValue', 0);
        $cmd->setConfiguration('maxValue', 5);
        $cmd->save();

        $cmd = $this->getCmd(null, 'operatorName');
        if (!is_object($cmd)) {
            $cmd = new huaweilteCmd();
            $cmd->setLogicalId('operatorName');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Operateur', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = $this->getCmd(null, 'smsLastSender');
        if (!is_object($cmd)) {
            $cmd = new huaweilteCmd();
            $cmd->setLogicalId('smsLastSender');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Dernier expéditeur de SMS', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();

        $cmd = $this->getCmd(null, 'smsLastMessage');
        if (!is_object($cmd)) {
            $cmd = new huaweilteCmd();
            $cmd->setLogicalId('smsLastMessage');
            $cmd->setIsVisible(1);
            $cmd->setName(__('Dernier SMS', __FILE__));
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
    }
}

class huaweilteCmd extends cmd {
    public function dontRemoveCmd() {
        if ($this->getSubType() == 'message') {
            return false;
        }
        return true;
    }

    public function preSave() {
        if ($this->getSubType() == 'message') {
            $this->setDisplay('title_disable', 1);
        }
    }

    public function execute($_options = array()) {
        if (isset($_options['numbers'])) {
            $numbers = $_options['numbers'];
        } else {
            $numbers = explode(';', $this->getConfiguration('phonenumber'));
        }

        $message = trim($_options['message']);

        if (config::byKey('deviceurl', 'huaweilte', null) != null) {
            $data = json_encode(array(
                'apikey' => jeedom::getApiKey('huaweilte'),
                'numbers' => $numbers,
                'message' => $message,
            ));

            $socket = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'huaweilte'));
            socket_write($socket, $data, strlen($data));
            socket_close($socket);
        }
    }
}
