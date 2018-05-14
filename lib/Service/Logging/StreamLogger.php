<?php

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service\Logging;

use OCA\Mail\Service\Logger;
use OCP\ITempManager;
use OCP\Security\ISecureRandom;

class StreamLogger {

	/** @var Logger */
	private $logger;

	/** @var ITempManager */
	private $tmpManager;

	/** @var string */
	private $prefix;

	/** @var resource[] */
	private $streams;

	public function __construct(Logger $logger, ISecureRandom $random,
		ITempManager $tmpManager) {
		$this->logger = $logger;
		$this->tmpManager = $tmpManager;
		$this->prefix = $random->generate(32, ISecureRandom::CHAR_UPPER);
		$this->streams = [];
	}

	public function __destruct() {
		foreach ($this->streams as $name => $stream) {
			$contents = file_get_contents($stream);
			$this->logger->info("got stream $name with " . strlen($contents) . " entries");
		}
	}

	public function getStream($name) {
		if (!isset($this->streams[$name])) {
			$this->streams[$name] = $this->tmpManager->getTempBaseDir() . "/" . $this->prefix . "_$name.log";
		}

		return $this->streams[$name];
	}

}
