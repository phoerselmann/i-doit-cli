<?php

/**
 * Copyright (C) 2016-18 Benjamin Heisig
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Benjamin Heisig <https://benjamin.heisig.name/>
 * @copyright Copyright (C) 2016-17 Benjamin Heisig
 * @license http://www.gnu.org/licenses/agpl-3.0 GNU Affero General Public License (AGPL)
 * @link https://github.com/bheisig/i-doit-cli
 */

declare(strict_types=1);

namespace bheisig\idoitcli\Command;

use bheisig\cli\Command\Command as BaseCommand;
use bheisig\cli\Config;
use bheisig\cli\JSONFile;
use bheisig\idoitcli\API\Idoit;
use bheisig\idoitcli\Service\Cache;
use bheisig\idoitcli\Service\UserInteraction;
use bheisig\idoitcli\Service\Validate;

/**
 * Base class for commands
 */
abstract class Command extends BaseCommand {

    /**
     * i-doit API calls
     *
     * @var \bheisig\idoitcli\API\Idoit
     */
    protected $idoit;

    /**
     * @var \bheisig\idoitcli\Service\Cache
     */
    protected $cache;

    /**
     * @var \bheisig\idoitcli\Service\UserInteraction
     */
    protected $userInteraction;

    /**
     * @var \bheisig\idoitcli\Service\Validate
     */
    protected $validate;

    /**
     * Factory for i-doit API calls
     *
     * @return \bheisig\idoitcli\API\Idoit
     *
     * @throws \Exception on error
     */
    protected function useIdoitAPI(): Idoit {
        if (!isset($this->idoit)) {
            $this->idoit = new Idoit($this->config, $this->log);
        }

        return $this->idoit;
    }

    /**
     * Print debug information
     *
     * @param array $arr
     *
     * @param int $nested
     */
    protected function printDebug(array $arr, int $nested = 0) {
        if (count($arr) === 0) {
            $this->log->debug(
                '%s–',
                str_repeat(' ', $nested * 4)
            );

            return;
        }

        foreach ($arr as $key => $value) {
            switch (gettype($value)) {
                case 'array':
                    $this->log->debug(
                        '%s%s:',
                        str_repeat(' ', $nested * 4),
                        $key
                    );

                    $this->printDebug($value, ($nested + 1));
                    break;
                default:
                    $this->log->debug(
                        '%s%s: %s',
                        str_repeat(' ', $nested * 4),
                        $key,
                        $value
                    );
                    break;
            }
        }
    }

    /**
     * Process some routines before executing command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function setup(): self {
        parent::setup();

        $this->validateConfig();

        $this->cache = new Cache($this->config, $this->log);
        $this->userInteraction = new UserInteraction($this->config, $this->log);
        $this->validate = new Validate($this->config, $this->log);

        return $this;
    }

    /**
     * Validate configuration settings
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    protected function validateConfig(): self {
        $file = $this->config['appDir'] . '/config/schema.json';
        $rules = JSONFile::read($file);
        $config = new Config();
        $errors = $config->validate($this->config, $rules);

        if (count($errors) > 0) {
            $this->log->warning('One or more errors found in configuration settings:');

            foreach ($errors as $error) {
                $this->log->warning($error);
            }

            throw new \Exception('Cannot proceed unless you fix your configuration');
        }

        return $this;
    }

}
