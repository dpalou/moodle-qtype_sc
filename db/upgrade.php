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
 * qtype_sc upgrade code.
 *
 * @package     qtype_sc
 * @author      Amr Hourani (amr.hourani@id.ethz.ch)
 * @author      Martin Hanusch (martin.hanusch@let.ethz.ch)
 * @author      Jürgen Zimmer (juergen.zimmer@edaktik.at)
 * @author      Andreas Hruska (andreas.hruska@edaktik.at)
 * @copyright   2018 ETHZ {@link http://ethz.ch/}
 * @copyright   2017 eDaktik GmbH {@link http://www.edaktik.at}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade code for the sc question type.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_sc_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018030802) {
        $table = new xmldb_table('qtype_sc_options');
        $field = new xmldb_field('shuffleoptions', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'scoringmethod');
        $dbman->rename_field($table, $field, 'shuffleanswers');
        upgrade_plugin_savepoint(true, 2018030802, 'qtype', 'sc');
    }

    if ($oldversion < 2018032003) {
        require_once($CFG->dirroot . '/question/type/sc/db/upgradelib.php');

        qtype_sc_convert_question_attempts(2018032003);
        upgrade_plugin_savepoint(true, 2018032003, 'qtype', 'sc');
    }

    if ($oldversion < 2020051200) {
        require_once($CFG->dirroot . '/question/type/sc/db/upgradelib.php');

        qtype_sc_convert_question_attempts(2020051200);
        upgrade_plugin_savepoint(true, 2020051200, 'qtype', 'sc');
    }

    return true;
}
