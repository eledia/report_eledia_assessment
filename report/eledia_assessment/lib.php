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
 * This file contains public API of eledia_assessment report
 *
 * @package    report
 * @subpackage eledia_assessment
 * @copyright  2020 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the course navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 * @throws coding_exception
 * @throws moodle_exception
 */
function report_eledia_assessment_extend_navigation_course($navigation, $course, $context) {
    if ($course->id == 1) {
        return;
    }
    if (has_capability('report/eledia_assessment:view_course_overview', $context)) {
        $url = new moodle_url('/report/eledia_assessment/course_overview.php', array('courseid' => $course->id));
        $navigation->add(get_string('course_overview', 'report_eledia_assessment'),
            $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Get the header block as html of course_overview.
 *
 * @param stdClass $course The course to object for the report
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function report_eledia_assessment_get_course_overview_header($course, $delimiter = '<br>') {
    $header = '';
    return $header;
}

/**
 * Get the body block as html of course_overview.
 *
 * @param $data the data records of the report
 * @return string
 * @throws coding_exception
 */
function report_eledia_assessment_get_course_overview_body($data) {
    global $CFG;
    $table = new html_table;
    $table->cellpadding = 1;
    $table->cellspacing = 1;
    $table->attributes['class'] = 'flexible generaltable generalbox';
    $table->attributes['style'] = 'font-size:12px';

    $table->head = array(
        get_string('lastname'),
        get_string('firstname'),
        get_string('mtrnr', 'report_eledia_assessment'),
        get_string('group'),
        get_string('assessment', 'report_eledia_assessment'),
        get_string('attempt', 'report_eledia_assessment'),
        get_string('status', 'report_eledia_assessment'),
    );

    $table->data = array();
    if (empty($data)) {
        return '';
    }
    $i = 0;
    foreach ($data as $key => $item) {
        $row = new html_table_row();
        if (($i % 2) == 1) {
            $row->style = 'background-color:LightGray';
        } else {
            $row->style = 'background-color:white';
        }
        $row->cells[] = '<a target="_new" href="'.$CFG->wwwroot.'/user/profile.php?id='.$item->uid.'">'.$item->name.'</a>';
        $row->cells[] = $item->vorname;
        $row->cells[] = $item->matrikelnummer;
        $row->cells[] = $item->gruppe;
        $row->cells[] = '<a target="_new" href="'.$CFG->wwwroot.'/mod/quiz/view.php?id='.$item->cmid.'">'.$item->assessment.'</a>';
        $row->cells[] = $item->versuch;
        $row->cells[] = $item->status;
        $table->data[] = $row;
        $i++;
    }
    $body = html_writer::table($table);
    return $body;
}

/**
 * Get the body block as html of course_overview.
 *
 * @param stdClass $course The $course the report is called in.
 * @return array
 * @throws dml_exception
 * @throws coding_exception
 * @throws moodle_exception
 */
function report_eledia_assessment_get_course_overview_data($course) {
    global $DB;

    $sql = 'SELECT
u.username AS "matrikelnummer",
 u.lastname AS "name",
 u.firstname AS "vorname",   g.name AS "gruppe",
 q.name AS "assessment",
 qa.attempt AS "versuch",
 case when qa.state is null then \'nicht gestartet\'
 when qa.state = \'inprogress\' then \'gestartet\'
 when qa.state = \'finished\' then \'beendet\'
 else qa.state end AS "status", u.id AS uid, cm.id AS cmid
FROM {role_assignment}s AS ra
JOIN {context} context ON context.id = ra.contextid AND context.contextlevel = 50
JOIN {course} c ON c.id = context.instanceid
JOIN {course_modules} cm ON cm.course = c.id
JOIN {modules} m ON m.id = cm.module
JOIN {quiz} q ON q.course = c.id AND cm.instance = q.id
JOIN {user} u ON u.id = ra.userid
LEFT JOIN {quiz_attempts} qa ON qa.userid = u.id AND qa.quiz = q.id
LEFT JOIN {groups} g ON g.courseid = c.id
LEFT JOIN {groups_members} gm ON g.id = gm.groupid AND gm.userid = u.id
WHERE m.name LIKE \'quiz\'
AND c.id =  ?
ORDER BY "status","name","vorname","versuch"';
    $params = array($course->id);
    $data = $DB->get_records_sql($sql, $params);

    return $data;
}

/**
 * Get the course custom data.
 *
 * @param stdClass $course The course object to get the custom fields for.
 * @return stdClass
 * @throws moodle_exception
 */
function report_eledia_assessment_load_course_custom($course) {

    $handler = \core_customfield\handler::get_handler('core_course', 'course');
    $datas = $handler->get_instance_data($course->id);
    foreach ($datas as $data) {
        if (empty($data->export_value())) {
            continue;
        }
        $fieldname = 'custom_field_'.$data->get_field()->get('shortname');
        $course->$fieldname = $data->export_value();
    }
    if (empty($course->custom_field_programme)) {
        $course->custom_field_programme = '';
    }
    if (empty($course->custom_field_programme2)) {
        $course->custom_field_programme2 = '';
    }
    return $course;
}

/**
 * Generate pdf file content.
 *
 * @param $course to report about.
 * @param $context of the course to report.
 * @return string the pdf content as string.
 * @throws coding_exception
 * @throws moodle_exception
 */
function report_eledia_assessment_get_course_overview_pdf($course, $context) {
    global $CFG;

    require_once("$CFG->libdir/pdflib.php");
    require_once($CFG->libdir . '/tcpdf/tcpdf.php');

    // Start new PDF, set protection and author field.
    $pdf = new PDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->AddPage();

    // Set default font subsetting mode.
    $pdf->setFontSubsetting(true);
    // Set font.
    $pdf->SetFont('helvetica', '', 10, '', true);

    // Get report header block.
    $text = report_eledia_assessment_get_course_overview_header($course).'<br><br>';
    // Get report table.
    $data = report_eledia_assessment_get_course_overview_data($course);
    $text .= report_eledia_assessment_get_course_overview_body($data);
    $pdf->writeHTML($text);

    // Write processed file to temporary path.
    $filename = $course->fullname.'_'.date('YmdHis', time()).'.pdf';
    $content = $pdf->Output($filename, 'D');
    return $content;
}

require_once($CFG->libdir . '/formslib.php');
/**
 * Fake form for file download.
 */
class assessment_download extends moodleform {
    public function definition() {
        $mform =& $this->_form;

        $courseid = optional_param('courseid', '', PARAM_RAW);
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_RAW);

        $userid = optional_param('userid', '', PARAM_RAW);
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_RAW);

        $mform->addElement('submit', 'submitbutton', get_string('pdf_download', 'report_eledia_assessment'));
    }
}
