<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for qtype_sc.
 *
 * @package    qtype_sc
 * @copyright  2018 Martin Hanusch <martin.hanusch@let.ethz.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_sc\privacy;
     
defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for qtype_sc implementing null_provider.
 * 
 */

class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason() : string {
    return 'privacy:metadata';
    }
}