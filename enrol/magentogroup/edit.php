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
 * Adds new instance of enrol_magentogroup to specified course or edits current instance.
 *
 * @package    enrol
 * @subpackage magentogroup
 * @author     Edwin Phillips <edwin.phillips@catalyst-eu.net>
 * @copyright  Catalyst IT Ltd 2016 <http://catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('edit_form.php');

$instanceid = optional_param('id', 0, PARAM_INT);
$courseid   = required_param('courseid', PARAM_INT);
$course     = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context    = context_course::instance($course->id, MUST_EXIST);

require_login($course);
require_capability('enrol/magentogroup:config', $context);

$PAGE->set_url('/enrol/magentogroup/edit.php', array('courseid' => $course->id, 'id' => $instanceid));
$PAGE->set_pagelayout('admin');

$return = new moodle_url('/enrol/instances.php', array('id' => $course->id));
if (!enrol_is_enabled('magentogroup')) {
    redirect($return);
}

$enrol = enrol_get_plugin('magentogroup');

if ($instanceid) {
    $instance = $DB->get_record('enrol', array('courseid' => $course->id, 'enrol' => 'magentogroup', 'id' => $instanceid), '*', MUST_EXIST);
} else {
    require_capability('moodle/course:enrolconfig', $context);
    navigation_node::override_active_url(new moodle_url('/enrol/instances.php', array('id' => $course->id)));
    $instance = (object)$enrol->get_instance_defaults();
    $instance->id       = null;
    $instance->courseid = $course->id;
    $instance->status   = ENROL_INSTANCE_ENABLED;
}

$mform = new enrol_magentogroup_edit_form(null, array($instance, $enrol, $context));

if ($mform->is_cancelled()) {
    redirect($return);
} else if ($data = $mform->get_data()) {
    if ($instance->id) {
        $reset = ($instance->status != $data->status);
        $instance->status       = $data->status;
        $instance->name         = get_string('pluginname', 'enrol_magentogroup');
        $instance->customtext1  = $data->customtext1;
        $instance->customtext2  = $data->customtext2;
        $instance->roleid       = $DB->get_field('role', 'id', array('shortname' => ENROL_MAGENTOGROUP_STUDENT_SHORTNAME));
        $instance->timemodified = time();
        $DB->update_record('enrol', $instance);
        if ($reset) {
            $context->mark_dirty();
        }
    } else {
        $fields = array(
            'status'      => $data->status,
            'name'        => get_string('pluginname', 'enrol_magentogroup'),
            'customtext1' => $data->customtext1
        );
        $enrol->add_instance($course, $fields);
    }

    redirect($return);
}

$PAGE->set_heading($course->fullname);
$PAGE->set_title(get_string('pluginname', 'enrol_magentogroup'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'enrol_magentogroup'));
$mform->display();
echo $OUTPUT->footer();
