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

use bheisig\cli\IO;
use bheisig\idoitcli\Service\Cache;

/**
 * Command "types"
 */
class Types extends Command {

    use Cache;

    /**
     * Executes the command
     *
     * @return self Returns itself
     *
     * @throws \Exception on error
     */
    public function execute(): self {
        $types = $this->getObjectTypes();

        $types = array_filter($types, [$this, 'filterObjectTypes']);

        usort($types, [$this, 'sort']);

        $groups = $this->group($types);

        $this->formatGroups($groups);

        return $this;
    }

    /**
     * @param array $type
     *
     * @return bool
     */
    protected function filterObjectTypes(array $type): bool {
        return $type['status'] === '2';
    }

    /**
     * @param array $types
     *
     * @return array
     */
    protected function group(array $types): array {
        $groups = [];

        foreach ($types as $type) {
            if (!array_key_exists($type['type_group_title'], $groups)) {
                $groups[$type['type_group_title']] = [];
            }

            $groups[$type['type_group_title']][] = $type;
        }

        return $groups;
    }

    /**
     * @param array $groups
     */
    protected function formatGroups(array $groups) {
        switch (count($groups)) {
            case 0:
                IO::out('No groups found');
                break;
            case 1:
                IO::out('1 group type found');
                break;
            default:
                IO::out('%s groups found', count($groups));
                break;
        }

        IO::out('');

        foreach ($groups as $group => $types) {
            IO::out('%s:', $group);
            IO::out('');

            $this->formatList($types);
            IO::out('');
        }
    }

    /**
     * @param array $types
     */
    protected function formatList(array $types) {
        switch (count($types)) {
            case 0:
                IO::out('No object types found');
                break;
            case 1:
                IO::out('1 object type found');
                break;
            default:
                IO::out('%s object types found', count($types));
                break;
        }

        IO::out('');

        array_map(function ($type) {
            IO::out($this->format($type));
        }, $types);
    }

    /**
     * @param array $type
     *
     * @return string
     */
    protected function format(array $type): string {
        return sprintf(
            '%s [%s]',
            $type['title'],
            $type['const']
        );
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    protected function sort(array $a, array $b): int {
        return strcmp($a['title'], $b['title']);
    }

    /**
     * Print usage of command
     *
     * @return self Returns itself
     */
    public function printUsage(): self {
        $this->log->info(
            'Usage: %1$s %2$s [OPTIONS]

%3$s',
            $this->config['composer']['extra']['name'],
            $this->getName(),
            $this->getDescription()
        );

        return $this;
    }

}
