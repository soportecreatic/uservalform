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
 * @package   local_uservalform
 * @copyright 2018-2019, Creatic SAS <soporte@creatic.co>.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_uservalform_extend_navigation_course($navigation, $course, $coursecontext) {
    global $PAGE, $DB, $USER;

    $settings = $DB->get_record('local_uservalform', array('courseid' => $course->id));
    $lastaccess = $DB->get_record('local_uservalform_access', array('courseid' => $course->id, 'userid' => $USER->id));

    // Each time the user enters a course page, it defines when the data validation form appears.
    $validationurl = new moodle_url('/local/uservalform/enter.php', array('courseid' => $course->id));
    if($settings && $settings->enabled && $PAGE->pagetype != 'local-uservalform-enter'
            && is_enrolled($coursecontext, $USER)
            && !(has_capability('moodle/course:update', $coursecontext))) {

        if($settings->showonlyonce == false) {
            if(!$lastaccess || ($lastaccess && $USER->sesskey != $lastaccess->sesskey)) {
                redirect($validationurl);
            }
        } else {
            if(!$lastaccess || $lastaccess->checked == false) {
                redirect($validationurl);
            }
        }
    }

    // Adds a node in the Course Administration menu.
    if (has_capability('moodle/course:update', $coursecontext)) {
        $url = new moodle_url('/local/uservalform/modalsettings.php', array('courseid' => $course->id));
        $uservalidationnode = navigation_node::create(get_string('navigationmenuname', 'local_uservalform'),
                $url, navigation_node::TYPE_CUSTOM, null, 'uservalform', new pix_icon('i/settings', ''));
        $navigation->add_node($uservalidationnode);
    }
}
