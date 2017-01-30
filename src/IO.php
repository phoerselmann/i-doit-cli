<?php

/**
 * Copyright (C) 2017 Benjamin Heisig
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

namespace bheisig\idoitcli;

class IO {

    public static function out($message, $args = null) {
        $argList = func_get_args();

        if (count($argList) >= 2) {
            $message = call_user_func_array('sprintf', $argList);
        }

        fwrite(STDOUT, $message . PHP_EOL);
    }

    public static function err($message, $args = null) {
        $argList = func_get_args();

        if (count($argList) >= 2) {
            $message = call_user_func_array('sprintf', $argList);
        }

        fwrite(STDERR, $message . PHP_EOL);
    }

    public static function in($message, $args = null) {
        $argList = func_get_args();

        if (count($argList) >= 2) {
            $message = call_user_func_array('sprintf', $argList);
        }

        fwrite(STDOUT, $message . ' ');

        return trim(fgets(STDIN));
    }

}
