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
 * Definition of log events.
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

defined('MOODLE_INTERNAL') || die();

global $DB;

$logs = array(
    array('module' => 'meet', 'action' => 'add', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'update', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'view', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'delete', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'join', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'report', 'mtable' => 'meet', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'delete recording', 'mtable' => 'meet_recordings', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'automatically fetch recording', 'mtable' => 'meet_recordings', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'manually fetch recording', 'mtable' => 'meet_recordings', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'view recording', 'mtable' => 'meet_recordings', 'field' => 'name'),
    array('module' => 'meet', 'action' => 'update recording', 'mtable' => 'meet_recordings', 'field' => 'name'),
);
