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
 * Upgrade logic.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_meet_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if($oldversion < 2020052601) {

        // Define table meet_recordings to be created
        $table = new xmldb_table('meet_recordings');

        // Add fields
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('meetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('gfileid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gfilename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gfileduration', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('gfilethumbnail', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('gfiletimecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('gfiletimemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Add keys
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('meet', XMLDB_KEY_FOREIGN, array('meetid'), 'meet', array('id'));

        // Create table
        if( ! $dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table meet to be updated
        $table = new xmldb_table('meet');
        $field = new xmldb_field('recordingslastcheck', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeend');

        // Add field
        if( ! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Meet savepoint reached.
        upgrade_mod_savepoint(true, 2020052601, 'meet');
    }

    if($oldversion < 2020061900) {

        // Define table meet to be updated
        $table = new xmldb_table('meet');
        $field = new xmldb_field('gmeetid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'geventuri');

        // Add field
        if( ! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set default value for current meetings
        $instances = $DB->get_records('meet');
        foreach ($instances as $instance) {
            $instance->gmeetid = substr($instance->gmeeturi, strrpos($instance->gmeeturi, '/') + 1);
            $DB->update_record('meet', $instance);
        }

        // Set field as not null
        $dbman->change_field_notnull($table, $field);

        // Meet savepoint reached.
        upgrade_mod_savepoint(true, 2020061900, 'meet');
    }

    if($oldversion < 2020062900) {

        // Define table meet recordings to be updated
        $table = new xmldb_table('meet_recordings');
        $fields = array(
            new xmldb_field('chatlogid', XMLDB_TYPE_INTEGER, '10', true, null, null, null, 'meetid'),
            new xmldb_field('gchatlogid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'meetid'),
            new xmldb_field('gchatlogname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'meetid'),
        );

        // Add fields
        foreach ($fields as $field) {
            if( ! $dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Meet savepoint reached.
        upgrade_mod_savepoint(true, 2020062900, 'meet');
    }

    if($oldversion < 2020073101) {

        // Define table meet to be updated
        $table = new xmldb_table('meet');
        $field = new xmldb_field('addcoursename', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'notify');

        // Add field
        if( ! $dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Meet savepoint reached.
        upgrade_mod_savepoint(true, 2020073101, 'meet');
    }

    return true;
}
