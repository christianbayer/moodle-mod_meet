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
 * List of all meet in course
 *
 * @package   mod_meet
 * @copyright 2020 onwards, Univates
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Christian Bayer  (christian.bayer@universo.univates.br)
 */

require_once('../../config.php');

// Get course id
$id = required_param('id', PARAM_INT);

// Get course
$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

// Security check
require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

// Trigger instances list viewed event.
$event = \mod_meet\event\course_module_instance_list_viewed::create(array('context' => context_course::instance($course->id)));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get strings
$strmeet         = get_string('modulename', 'meet');
$strmeets        = get_string('modulenameplural', 'meet');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/meet/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': ' . $strmeets);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strmeets);
echo $OUTPUT->header();
echo $OUTPUT->heading($strmeets);
if (!$meets = get_all_instances_in_course('meet', $course)) {
    notice(get_string('thereareno', 'moodle', $strmeets), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($meets as $meet) {
    $cm = $modinfo->cms[$meet->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($meet->section !== $currentsection) {
            if ($meet->section) {
                $printsection = get_section_name($course, $meet->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $meet->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($meet->timemodified)."</span>";
    }

    $class = $meet->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed

    $table->data[] = array (
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">".format_string($meet->name)."</a>",
        format_module_intro('page', $meet, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
