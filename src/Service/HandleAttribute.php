<?php

/**
 * Copyright (C) 2016-19 Benjamin Heisig
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

namespace bheisig\idoitcli\Service;

use \Exception;
use \BadMethodCallException;
use \RuntimeException;

/**
 * Attribute handling
 */
class HandleAttribute extends Service {

    const TEXT = 'text';
    const TEXT_AREA = 'text_area';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const DIALOG = 'dialog';
    const DIALOG_PLUS = 'dialog_plus';
    const DIALOG_PLUS_MULTI_SELECTION = 'dialog_plus_multi-selection';
    const YES_NO_DIALOG = 'yes_no_dialog';
    const OBJECT_RELATION = 'object_relation';
    const OBJECT_RELATIONS = 'object_relations';
    const GEO_COORDINATES = 'geo_coordinates';
    const IP_ADDRESS = 'ip_address';
    const LINK = 'link';
    const PASSWORD = 'password';
    const FILE = 'file';
    const UNIT = 'unit';
    const CAPACITY = 'capacity';
    const HORIZONTAL_LINE = 'hr';
    const EMBEDED_HTML = 'html';
    const EMBEDED_JAVASCRIPT = 'js';
    const UNKNOWN = 'unknown';

    const SHORT_TEXT_LENGTH = 255;
    const LONG_TEXT_LENGTH = 65535;

    protected $definition = [];
    protected $type = '';

    /**
     * i-doit API
     *
     * @var IdoitAPI
     */
    protected $idoitAPI;

    /**
     * i-doit API factory
     *
     * @var IdoitAPIFactory
     */
    protected $idoitAPIFactory;

    /**
     * Setup service
     *
     * @param IdoitAPI $idoitAPI i-doit API
     * @param IdoitAPIFactory $idoitAPIFactory i-doit API factory
     *
     * @return self Returns itself
     */
    public function setUp(IdoitAPI $idoitAPI, IdoitAPIFactory $idoitAPIFactory): self {
        $this->idoitAPI = $idoitAPI;
        $this->idoitAPIFactory = $idoitAPIFactory;

        return $this;
    }

    /**
     * Load attribute definition
     *
     * @param array $definition Attribute definition
     *
     * @return self Returns itself
     */
    public function load(array $definition): self {
        $this->definition = $definition;

        $this->identifyType();

        return $this;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function ignore(): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        switch ($this->type) {
            case self::TEXT:
            case self::TEXT_AREA:
            case self::DATE:
            case self::DATETIME:
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::YES_NO_DIALOG:
            case self::OBJECT_RELATION:
            case self::OBJECT_RELATIONS:
            case self::IP_ADDRESS:
            case self::GEO_COORDINATES:
            case self::LINK:
            case self::PASSWORD:
            case self::CAPACITY:
            case self::UNIT:
                return false;
            // @todo Add support for files!
            case self::FILE:
            case self::HORIZONTAL_LINE:
            case self::EMBEDED_HTML:
            case self::EMBEDED_JAVASCRIPT:
            case self::UNKNOWN:
                return true;
            default:
                throw new Exception(sprintf(
                    'Unknown ignore state for attribute "%s"',
                    $this->definition['title']
                ));
        }
    }

    /**
     * Translate value from i-doit to human-readable format
     *
     * @param mixed $value Value from i-doit API
     *
     * @return string Encoded value, even if it's empty
     *
     * @throws Exception on error
     */
    public function encode($value): string {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        if ($this->hasValueForEncoding($value) === false) {
            return '';
        }

        if ($this->validateBeforeEncoding($value) === false) {
            throw new BadMethodCallException(sprintf(
                'Invalid value for attribute "%s"',
                $this->definition['title']
            ));
        }

        switch ($this->type) {
            case self::TEXT:
            case self::DATE:
            case self::LINK:
            case self::PASSWORD:
            case self::UNIT:
                return $value;
            case self::TEXT_AREA:
                // Rich-text editor with HTML:
                return strip_tags($value);
            case self::DATETIME:
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::OBJECT_RELATION:
            case self::CAPACITY:
                return $value['title'];
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::OBJECT_RELATIONS:
                $values = [];

                foreach ($value as $block) {
                    $values[] = $block['title'];
                }
                return implode(', ', $values);
            case self::YES_NO_DIALOG:
                // @todo C__CATG__IP::use_standard_gateway always returns category entry identifier…
                if (!array_key_exists('title', $value)) {
                    return 'no';
                }

                $check = filter_var(
                    $value['title'],
                    FILTER_VALIDATE_BOOLEAN
                );

                return ($check) ? 'yes' : 'no';
            case self::IP_ADDRESS:
                return $value['ref_title'];
            case self::GEO_COORDINATES:
                $longitude = '';
                $latitude = '';

                if (!is_array($value)) {
                    return '';
                }

                if (array_key_exists('longitude', $value) &&
                    is_string($value['longitude'])) {
                    $longitude = $value['longitude'];
                }

                if (array_key_exists('latitude', $value) &&
                    is_string($value['latitude'])) {
                    $latitude = $value['latitude'];
                }

                if ($longitude !== '' && $latitude !== '') {
                    return $longitude . ', ' . $latitude;
                } elseif ($longitude !== '' && $latitude === '') {
                    return $longitude;
                } elseif ($longitude === '' && $latitude !== '') {
                    return $latitude;
                } else {
                    return '';
                }
                break;
            case self::FILE:
                // @todo Return file title with download link!
                return $value;
            case self::UNKNOWN:
                switch (gettype($value)) {
                    case 'array':
                        if (array_key_exists('title', $value)) {
                            return $value['title'];
                        } elseif (array_key_exists('ref_title', $value)) {
                            return $value['ref_title'];
                        } else {
                            $values = [];

                            foreach ($value as $subObject) {
                                if (is_array($subObject) &&
                                    array_key_exists('title', $subObject)) {
                                    $values[] = $subObject['title'];
                                }

                                if (is_array($subObject)) {
                                    if (array_key_exists('title', $value)) {
                                        $values[] = $subObject['title'];
                                    } elseif (array_key_exists('ref_title', $value)) {
                                        $values[] = $subObject['ref_title'];
                                    }
                                }
                            }

                            return implode(', ', $values);
                        }
                        break;
                    case 'string':
                        // Rich text editor uses HTML:
                        return strip_tags($value);
                    default:
                        return '';
                }
                break;
            default:
                throw new RuntimeException(sprintf(
                    'Unable to encode value for attribute "%s"',
                    $this->definition['title']
                ));
        }
    }

    /**
     * Translate value from CLI to format parsable by i-doit
     *
     * @param string $value
     *
     * @return mixed
     *
     * @throws Exception on error
     */
    public function decode(string $value) {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        $decodedValue = null;

        if ($this->hasValueForDecoding($value) === false) {
            return $decodedValue;
        }

        if ($this->validateBeforeDecoding($value) === false) {
            throw new BadMethodCallException(sprintf(
                'Invalid value for attribute "%s"',
                $this->definition['title']
            ));
        }

        switch ($this->type) {
            case self::TEXT:
            case self::TEXT_AREA:
            case self::IP_ADDRESS:
            case self::LINK:
            case self::PASSWORD:
                $decodedValue = $value;
                break;
            case self::UNIT:
                $decodedValue = (float) $value;
                break;
            case self::CAPACITY:
                switch (strtolower($value)) {
                    case 'b':
                    case 'byte':
                        $decodedValue = 1000;
                        break;
                    case 'kb':
                    case 'kilobyte':
                    case 'kbyte':
                        $decodedValue = 1;
                        break;
                    case 'mb':
                    case 'megabyte':
                    case 'mbyte':
                        $decodedValue = 2;
                        break;
                    case 'gb':
                    case 'gigabyte':
                    case 'gbyte':
                        $decodedValue = 3;
                        break;
                    case 'tb':
                    case 'terabyte':
                    case 'tbyte':
                        $decodedValue = 4;
                        break;
                }
                break;
            case self::DATE:
            case self::DATETIME:
                $unixTimestamp = strtotime($value);
                if (is_int($unixTimestamp)) {
                    $decodedValue = date('Y-m-d', $unixTimestamp);
                }
                break;
            case self::DIALOG:
            case self::DIALOG_PLUS:
                if (is_numeric($value) && (int) $value > 0) {
                    $decodedValue = (int) $value;
                } else {
                    $decodedValue = $value;
                }
                break;
            case self::DIALOG_PLUS_MULTI_SELECTION:
                $values = array_map('trim', explode(',', $value));

                foreach ($values as $rawValue) {
                    if (is_numeric($rawValue) && (int) $rawValue > 0) {
                        $decodedValue[] = (int) $rawValue;
                    } else {
                        $decodedValue[] = $rawValue;
                    }
                }
                break;
            case self::YES_NO_DIALOG:
                $check = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                $decodedValue = ($check) ? 1 : 0;
                break;
            case self::OBJECT_RELATION:
                $decodedValue = $this->identifyObject($value);
                break;
            case self::OBJECT_RELATIONS:
                $values = array_map('trim', explode(',', $value));

                foreach ($values as $value) {
                    $decodedValue[] = $this->identifyObject($value);
                }

                break;
            case self::GEO_COORDINATES:
                // Ignore!
                break;
            case self::UNKNOWN:
            default:
                throw new RuntimeException(sprintf(
                    'Unable to encode value for attribute "%s"',
                    $this->definition['title']
                ));
        }

        if ($this->validateAfterDecoding($decodedValue) === false) {
            throw new BadMethodCallException(sprintf(
                'Invalid value for attribute "%s"',
                $this->definition['title']
            ));
        }

        return $decodedValue;
    }

    public function hasValueForDecoding($value): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        if ($value === null) {
            return false;
        }

        switch ($this->type) {
            case self::TEXT:
            case self::TEXT_AREA:
            case self::DATE:
            case self::DATETIME:
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::YES_NO_DIALOG:
            case self::OBJECT_RELATION:
            case self::OBJECT_RELATIONS:
            case self::IP_ADDRESS:
            case self::GEO_COORDINATES:
            case self::LINK:
            case self::PASSWORD:
            case self::UNIT:
            case self::CAPACITY:
                return is_string($value) && strlen($value) > 0;
            // @todo Add support for files!
            case self::FILE:
            case self::HORIZONTAL_LINE:
            case self::EMBEDED_HTML:
            case self::EMBEDED_JAVASCRIPT:
            case self::UNKNOWN:
            default:
                return false;
        }
    }

    public function hasValueForEncoding($value): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        if ($value === null) {
            return false;
        }

        switch ($this->type) {
            case self::TEXT:
            case self::TEXT_AREA:
            case self::DATE:
            case self::LINK:
            case self::PASSWORD:
            case self::UNIT:
            case self::CAPACITY:
                return is_string($value) && strlen($value) > 0;
            case self::DATETIME:
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::YES_NO_DIALOG:
            case self::OBJECT_RELATION:
                return is_array($value) &&
                    array_key_exists('title', $value);
            case self::DIALOG_PLUS_MULTI_SELECTION:
                if (!is_array($value)) {
                    return false;
                }

                foreach ($value as $block) {
                    if (!is_array($block) ||
                        !array_key_exists('title', $block) ||
                        is_string($block['title']) ||
                        strlen($block['title']) === 0) {
                        return false;
                    }
                }

                return true;
            case self::OBJECT_RELATIONS:
                if (!is_array($value)) {
                    return false;
                }

                foreach ($value as $block) {
                    if (!array_key_exists('title', $block) ||
                        is_string($block['title']) ||
                        strlen($block['title']) === 0) {
                        return false;
                    }
                }

                return true;
            case self::IP_ADDRESS:
                return is_array($value) &&
                    array_key_exists('ref_title', $value);
            case self::GEO_COORDINATES:
                return is_array($value) &&
                    array_key_exists('longitude', $value) &&
                    is_string($value['longitude']) &&
                    array_key_exists('latitude', $value) &&
                    is_string($value['latitude']);
            // @todo Add support for files!
            case self::FILE:
            case self::HORIZONTAL_LINE:
            case self::EMBEDED_HTML:
            case self::EMBEDED_JAVASCRIPT:
            case self::UNKNOWN:
            default:
                return true;
        }
    }

    public function validateBeforeEncoding($value): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        switch ($this->type) {
            case self::TEXT:
            case self::TEXT_AREA:
            case self::DATE:
            case self::LINK:
            case self::PASSWORD:
                return is_string($value);
            case self::DATETIME:
                return is_array($value) &&
                    array_key_exists('title', $value) &&
                    is_string($value['title']) &&
                    strtotime($value['title']) !== false;
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::OBJECT_RELATION:
            case self::CAPACITY:
                return is_array($value) &&
                    array_key_exists('title', $value) &&
                    is_string($value['title']);
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::OBJECT_RELATIONS:
                if (!is_array($value)) {
                    return false;
                }

                foreach ($value as $block) {
                    if (!array_key_exists('title', $block) ||
                        is_string($block['title']) ||
                        strlen($block['title']) === 0) {
                        return false;
                    }
                }

                return true;
            case self::YES_NO_DIALOG:
                return is_array($value) &&
                    array_key_exists('title', $value) &&
                    filter_var(
                        $value['title'],
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ) !== null;
            case self::IP_ADDRESS:
                return is_array($value) &&
                    array_key_exists('ref_title', $value) &&
                    is_string($value['ref_title']);
            case self::GEO_COORDINATES:
                return is_array($value) &&
                    array_key_exists('longitude', $value) &&
                    is_string($value['longitude']) &&
                    array_key_exists('latitude', $value) &&
                    is_string($value['latitude']);
            case self::UNIT:
                return is_numeric($value);
            case self::UNKNOWN:
            default:
                throw new RuntimeException(sprintf(
                    'Unable to validate value for attribute "%s"',
                    $this->definition['title']
                ));
        }
    }

    public function validateAfterEncoding(string $value): bool {
        // @todo Implement me!
        return true;
    }

    public function validateBeforeDecoding(string $value): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        switch ($this->type) {
            case self::TEXT_AREA:
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::OBJECT_RELATIONS:
                return strlen($value) <= self::LONG_TEXT_LENGTH;
            case self::TEXT:
            case self::DIALOG:
            case self::DIALOG_PLUS:
            case self::OBJECT_RELATION:
            case self::IP_ADDRESS:
            case self::LINK:
            case self::PASSWORD:
            case self::UNIT:
            case self::CAPACITY:
                return strlen($value) <= self::SHORT_TEXT_LENGTH;
            case self::DATE:
            case self::DATETIME:
                $unixTimestamp = strtotime($value);
                return is_integer($unixTimestamp) && $unixTimestamp > 0;
            case self::YES_NO_DIALOG:
                $check = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                return is_bool($check);
            case self::UNKNOWN:
            case self::GEO_COORDINATES:
                return true;
            default:
                throw new RuntimeException(sprintf(
                    'Unable to validate value for attribute "%s"',
                    $this->definition['title']
                ));
        }
    }

    public function validateAfterDecoding($value): bool {
        if (!$this->isLoaded()) {
            throw new BadMethodCallException('Missing attribute definition');
        }

        switch ($this->type) {
            case self::TEXT:
            case self::IP_ADDRESS:
            case self::DATE:
            case self::DATETIME:
            case self::LINK:
            case self::PASSWORD:
                return is_string($value) && strlen($value) <= self::SHORT_TEXT_LENGTH;
            case self::TEXT_AREA:
                return is_string($value) && strlen($value) <= self::LONG_TEXT_LENGTH;
            case self::DIALOG:
            case self::DIALOG_PLUS:
                return (is_string($value) && strlen($value) <= self::SHORT_TEXT_LENGTH) ||
                    (is_int($value) && $value > 0);
            case self::DIALOG_PLUS_MULTI_SELECTION:
            case self::OBJECT_RELATIONS:
                return is_array($value);
            case self::OBJECT_RELATION:
            case self::CAPACITY:
                return is_int($value) && $value > 0;
            case self::YES_NO_DIALOG:
                $check = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

                return is_bool($check);
            case self::UNIT:
                return is_float($value);
            case self::UNKNOWN:
            case self::GEO_COORDINATES:
                return true;
            default:
                throw new RuntimeException(sprintf(
                    'Unable to validate value for attribute "%s"',
                    $this->definition['title']
                ));
        }
    }

    /**
     * Identify attribute type
     *
     * @return self Returns itself
     */
    protected function identifyType(): self {
        if (array_key_exists('info', $this->definition) &&
            is_array($this->definition['info']) &&
            array_key_exists('type', $this->definition['info']) &&
            $this->definition['info']['type'] === 'datetime') {
            $this->type = self::DATETIME;
        } elseif (array_key_exists('data', $this->definition) &&
            is_array($this->definition['data']) &&
            array_key_exists('type', $this->definition['data']) &&
            $this->definition['data']['type'] === 'date') {
            $this->type = self::DATE;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'text' &&
            array_key_exists('data', $this->definition) &&
            is_array($this->definition['data']) &&
            array_key_exists('type', $this->definition['data']) &&
            $this->definition['data']['type'] === 'text' &&
            (
                !array_key_exists('format', $this->definition) ||
                $this->definition['format'] === null)
            ) {
            $this->type = self::TEXT;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'link') {
            $this->type = self::LINK;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'textarea') {
            $this->type = self::TEXT_AREA;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'wysiwyg') {
            $this->type = self::TEXT_AREA;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('p_strPopupType', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['p_strPopupType'] === 'dialog_plus' &&
            array_key_exists('multiselection', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['multiselection'] === 1) {
            $this->type = self::DIALOG_PLUS_MULTI_SELECTION;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('popup', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['popup'] === 'file') {
            $this->type = self::FILE;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('popup', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['popup'] === 'dialog_plus') {
            $this->type = self::DIALOG_PLUS;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('p_strPopupType', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['p_strPopupType'] === 'dialog_plus') {
            $this->type = self::DIALOG_PLUS;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'get_yes_or_no') {
            $this->type = self::YES_NO_DIALOG;
        } elseif (array_key_exists('data', $this->definition) &&
            is_array($this->definition['data']) &&
            array_key_exists('source_table', $this->definition['data']) &&
            $this->definition['data']['source_table'] === 'isys_memory_unit') {
            $this->type = self::CAPACITY;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'dialog') {
            $this->type = self::DIALOG;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'object') {
            $this->type = self::OBJECT_RELATION;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'location') {
            $this->type = self::OBJECT_RELATION;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'exportContactAssignment') {
            $this->type = self::OBJECT_RELATION;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('popup', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['popup'] === 'browser_object' &&
            array_key_exists('multiselection', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['multiselection'] === false) {
            $this->type = self::OBJECT_RELATION;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('popup', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['popup'] === 'browser_object' &&
            array_key_exists('multiselection', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['multiselection'] === true) {
            $this->type = self::OBJECT_RELATIONS;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'exportHostname') {
            $this->type = self::TEXT;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            in_array(
                $this->definition['format']['callback'][1],
                ['property_callback_latitude', 'property_callback_longitude']
            )) {
            $this->type = self::TEXT;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'exportIpReference') {
            $this->type = self::IP_ADDRESS;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('callback', $this->definition['format']) &&
            is_array($this->definition['format']['callback']) &&
            array_key_exists(1, $this->definition['format']['callback']) &&
            $this->definition['format']['callback'][1] === 'property_callback_gps') {
            $this->type = self::GEO_COORDINATES;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'hr') {
            $this->type = self::HORIZONTAL_LINE;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'html') {
            $this->type = self::EMBEDED_HTML;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'script') {
            $this->type = self::EMBEDED_JAVASCRIPT;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'password') {
            $this->type = self::PASSWORD;
        } elseif (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('type', $this->definition['ui']) &&
            $this->definition['ui']['type'] === 'dialog') {
            $this->type = self::DIALOG;
        } elseif (array_key_exists('format', $this->definition) &&
            is_array($this->definition['format']) &&
            array_key_exists('unit', $this->definition['format']) &&
            $this->definition['format']['unit'] === 'unit') {
            $this->type = self::UNIT;
        } else {
            $this->type = self::UNKNOWN;
        }

        return $this;
    }

    /**
     * Check whether attribute definition is set properly
     *
     * @return bool
     */
    protected function isLoaded(): bool {
        return count($this->definition) > 0;
    }

    /**
     * Try to identify object
     *
     * @param string $value Numeric identifier or title
     *
     * @return int Object identifier
     * @throws Exception
     */
    protected function identifyObject(string $value): int {
        if (is_numeric($value) && (int) $value > 0) {
            $object = $this->idoitAPIFactory->getCMDBObject()->read((int) $value);

            if (count($object) === 0) {
                throw new RuntimeException(sprintf(
                    'Unable to identify object by identifier "%s"',
                    $value
                ));
            }

            return (int) $object['id'];
        } else {
            $objects = $this->idoitAPI->fetchObjects([
                'title' => $value
            ]);

            switch (count($objects)) {
                case 0:
                    throw new RuntimeException(sprintf(
                        'Unable to identify object by title "%s"',
                        $value
                    ));
                    break;
                case 1:
                    $object = end($objects);
                    return (int) $object['id'];
                default:
                    throw new RuntimeException(sprintf(
                        'Found %s objects. Unable to identify object by title "%s"',
                        count($objects),
                        $value
                    ));
            }
        }
    }

    public function isReadonly(): bool {
        if (array_key_exists('ui', $this->definition) &&
            is_array($this->definition['ui']) &&
            array_key_exists('params', $this->definition['ui']) &&
            is_array($this->definition['ui']['params']) &&
            array_key_exists('p_bReadonly', $this->definition['ui']['params']) &&
            $this->definition['ui']['params']['p_bReadonly'] === true) {
            return true;
        }

        return false;
    }

    /**
     * Get attribute type
     *
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

}
