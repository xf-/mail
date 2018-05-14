<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Cache\Cache;
use OCA\Mail\Service\Logging\StreamLogger;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Security\ICrypto;

class IMAPClientFactory {

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var StreamLogger */
	private $streamLogger;

	public function __construct(ICrypto $crypto, IConfig $config,
		ICacheFactory $cacheFactory, StreamLogger $streamLogger) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->cacheFactory = $cacheFactory;
		$this->streamLogger = $streamLogger;
	}

	/**
	 * @param Account $account
	 * @return Horde_Imap_Client_Socket
	 */
	public function getClient(Account $account) {
		$host = $account->getMailAccount()->getInboundHost();
		$user = $account->getMailAccount()->getInboundUser();
		$password = $account->getMailAccount()->getInboundPassword();
		$password = $this->crypto->decrypt($password);
		$port = $account->getMailAccount()->getInboundPort();
		$sslMode = $account->getMailAccount()->getInboundSslMode();
		if ($sslMode === 'none') {
			$sslMode = false;
		}

		$params = [
			'username' => $user,
			'password' => $password,
			'hostspec' => $host,
			'port' => $port,
			'secure' => $sslMode,
			'timeout' => (int) $this->config->getSystemValue('app.mail.imap.timeout', 20),
		];
		if ($this->config->getSystemValue('debug', false)) {
			$params['debug'] = $this->streamLogger->getStream('imap');
		}
		if ($this->cacheFactory->isAvailable()) {
			$params['cache'] = [
				'backend' => new Cache([
					'cacheob' => $this->cacheFactory->createDistributed(md5($account->getId())),
			])];
		}
		return new Horde_Imap_Client_Socket($params);
	}

}
