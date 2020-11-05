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
 * Course overview report
 *
 * @package    report
 * @subpackage eledia_assessment
 * @copyright  2020 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_login();

$courseid = required_param('courseid', PARAM_RAW);

$url = new moodle_url('/report/eledia_assessment/course_overview.php', array('courseid' => $courseid));
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Set up page params.
$PAGE->set_url('/report/eledia_assessment/course_overview.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('course_overview', 'report_eledia_assessment'));
$PAGE->set_pagelayout('report');

// Manually build up breadcrumb.
$PAGE->navbar->add($course->shortname, course_get_url($course->id));
$PAGE->navbar->add(get_string('course_overview', 'report_eledia_assessment'), $url);

require_capability('report/eledia_assessment:view_course_overview', $context);

// Add pdf download.
$mform = new assessment_download();
if ($mform->is_submitted()) {
    $content = report_eledia_assessment_get_course_overview_pdf($course, $context);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_overview', 'report_eledia_assessment'));

// Get report header block.
echo report_eledia_assessment_get_course_overview_header($course, $context).'<br><br>';

// Get report table.
$data = report_eledia_assessment_get_course_overview_data($course);
echo report_eledia_assessment_get_course_overview_body($data);

$mform->display();
echo $OUTPUT->footer();



