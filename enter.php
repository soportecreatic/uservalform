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

require('../../config.php');
require_once($CFG->dirroot . '/local/uservalform/enter_form.php');

$courseid = required_param('courseid', PARAM_INT);
$validationerror = optional_param('error', '', PARAM_INT);

$return = new moodle_url('/');
$courselink = new moodle_url('/course/view.php', array('id' => $courseid));

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
require_login($course);

$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_url('/local/uservalform/enter.php', array('courseid'=>$courseid));

$uservalformdata = $DB->get_record('local_uservalform', array('courseid' => $courseid));
$userinfofield = $DB->get_record('user_info_field', array('id' => $uservalformdata->userinfofieldid));
$userinfodata = $DB->get_record('user_info_data', array('fieldid' => $userinfofield->id, 'userid' => $USER->id));
$lastaccess = $DB->get_record('local_uservalform_access', array('courseid' => $courseid, 'userid' => $USER->id));

$validationform = new local_uservalform_validation_form(null, array($userinfofield, $uservalformdata));

if ($validationform->is_cancelled()) {
    redirect($return);
} else if ($data = $validationform->get_data()) {

    // Check if data entered matchs with the one entered in the database when the form is submitted.
    if($data->validationinfo == $userinfodata->data) {

        if($lastaccess) {
            if($uservalformdata->showonlyonce == false) {
                $lastaccess->sesskey = $USER->sesskey;
                $lastaccess->checked = false;
            } else {
                $lastaccess->checked = true;
                $lastaccess->sesskey = null;
            }

            $DB->update_record('local_uservalform_access', $lastaccess);
        } else {
            // If the database record doesn't exist we create and object with the data to insert into the database.
            $lastaccess = new stdClass();
            $lastaccess->userid = $USER->id;
            $lastaccess->courseid = $courseid;

            /* If the option 'Show form only once' is disabled, the field 'sesskey' is used to check
            if the user has entered validation data during his/her session. */
            if($uservalformdata->showonlyonce == false) {
                $lastaccess->sesskey = $USER->sesskey;
                $lastaccess->checked = false;
            } else {
                /* If the option 'Show form only once' is enabled, the field 'checked' is used to check
                if the user entered previously the specified validation data. */
                $lastaccess->checked = true;
                $lastaccess->sesskey = null;
            }

            $DB->insert_record('local_uservalform_access', $lastaccess);
        }

        redirect($courselink);

    } else {
        if($data->validationinfo == '') {
            $errormessage = get_string('dataerrorblank', 'local_uservalform');
        } else {
            $errormessage = get_string('dataerror', 'local_uservalform');
        }
        redirect($PAGE->url, $errormessage, null, \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();

echo html_writer::tag('p', get_string('validationformtext', 'local_uservalform'));
$validationform->display();
echo html_writer::link('https://creatic.co', get_string('developedby', 'local_uservalform'),
        array('style' => 'float: right'));

echo $OUTPUT->footer();
