# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import argparse
import logging
import os
import signal
import sys
import time

from huawei_lte_api.AuthorizedConnection import AuthorizedConnection
from huawei_lte_api.Client import Client

try:
    from jeedom.jeedom import *
except ImportError:
	print('Error: importing module jeedom.jeedom')
	sys.exit(1)

def listen():
    try:
        while 1:
            connection = AuthorizedConnection(_device_url)
            client = Client(connection)

            time.sleep(0.5)
    except KeyboardInterrupt:
        shutdown()

# ----------------------------------------------------------------------------

def handler(signum=None, frame=None):
    logging.debug("Signal %i caught, exiting..." % int(signum))
    shutdown()

def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))

	try:
		os.remove(_pidfile)
	except:
		pass

	try:
		jeedom_socket.close()
	except:
		pass

	try:
		jeedom_serial.close()
	except:
		pass

	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------

_log_level = 'error'
_socket_port = 55100
_socket_host = 'localhost'
_device_url = 'http://192.168.8.1/'
_pidfile = '/tmp/huaweilted.pid'

parser = argparse.ArgumentParser(description='Huawei LTE Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--deviceurl", help="Device URL", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
args = parser.parse_args()

if args.loglevel:
    _log_level = args.loglevel
if args.socketport:
    _socket_port = args.socketport
if args.deviceurl:
    _device_url = args.deviceurl
if args.pid:
    _pidfile = args.pid

_socket_port = int(_socket_port)
jeedom_utils.set_log_level(_log_level)

logging.info('Start demond')
logging.info('Log level : ' + str(_log_level))
logging.info('Socket port : ' + str(_socket_port))
logging.info('Socket host : ' + str(_socket_host))
logging.info('PID file : ' + str(_pidfile))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    jeedom_utils.write_pid(str(_pidfile))
    jeedom_socket = jeedom_socket(port=_socket_port, address=_socket_host)
    listen()
except Exception as e:
    logging.error('Fatal error : ' + str(e))
    shutdown()
