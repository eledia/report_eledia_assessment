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
function report_eledia_assessment_extend_navigation_course($navigation, $course, $context)
{
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
function report_eledia_assessment_get_course_overview_header($course, $delimiter = '<br>')
{
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
function report_eledia_assessment_get_course_overview_body($data)
{
    global $CFG;

    $content_html = '<table cellspacing="0" cellpadding="5" border="0" class="flexible generaltable generalbox">'
        . '<tr style="font-weight:bold">';

    $tableheadcols = array(
        array('5%', ''),
        array('16%', get_string('lastname')),
        array('16%', get_string('firstname')),
        array('13%', get_string('mtrnr', 'report_eledia_assessment')),
        array('15%', get_string('group')),
        array('15%', get_string('assessment', 'report_eledia_assessment')),
        array('10%', get_string('attempt', 'report_eledia_assessment')),
        array('10%', get_string('status', 'report_eledia_assessment')),
    );

    foreach ($tableheadcols as $tablecol) {
        $content_html .= '<th style="width: ' . $tablecol[0] . ';">' . $tablecol[1] . '</th>';
    }
    $content_html .= '</tr>';

    if (empty($data)) {
        return '';
    }

    $i = 0;
    foreach ($data as $key => $item) {

        if (($i % 2) == 0) {
            $style = 'background-color:LightGray';
        } else {
            $style = 'background-color:white';
        }
        $content_html .= '<tr nobr="true" style="' . $style . '">';

        $content_html .= '<td>' . ($i + 1) . '</td>';
        $content_html .= '<td><a target="_new" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $item->uid . '">' . $item->name . '</a></td>';
        $content_html .= '<td>' . $item->vorname . '</td>';
        $content_html .= '<td>' . $item->matrikelnummer . '</td>';
        $content_html .= '<td>' . $item->gruppe . '</td>';
        $content_html .= '<td><a target="_new" href="' . $CFG->wwwroot . '/mod/quiz/view.php?id=' . $item->cmid . '">' . $item->assessment . '</a></td>';
        $content_html .= '<td>' . $item->versuch . '</td>';
        $content_html .= '<td>' . $item->status . '</td>';

        $content_html .= '</tr>';
        $i++;
    }
    $content_html .= '</table>';
    return $content_html;
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
function report_eledia_assessment_get_course_overview_data($course)
{
    global $DB;
    $sql = 'SELECT 
         u.lastname  AS "name", 
         u.firstname AS "vorname", 
         u.username AS "matrikelnummer",  
          case when gm.id is null then "" 
         else g.name end AS "gruppe",
        q.name  AS "assessment",
         qa.attempt AS "versuch", 
         case when qa.state is null then "nicht gestartet" 
         when qa.state = "inprogress" then "gestartet"
         when qa.state = "finished" then "beendet"
         else qa.state end AS Status,
         u.id AS uid, cm.id AS cmid, g.id AS gid, cm.availability AS availability
        FROM {role_assignments} AS ra
        JOIN {context} AS context ON context.id = ra.contextid AND context.contextlevel = 50
        JOIN {course} AS c ON c.id = context.instanceid 
        JOIN {course_modules} AS cm ON cm.course = c.id
        JOIN {modules} AS m ON m.id = cm.module
        JOIN {quiz} AS q ON q.course = c.id AND cm.instance = q.id 
        JOIN {user} AS u ON u.id = ra.userid
        LEFT JOIN {quiz_attempts} AS qa ON qa.userid = u.id AND qa.quiz = q.id 
        LEFT JOIN {groups} AS g ON g.courseid = c.id
        LEFT JOIN {groups_members} AS gm ON g.id = gm.groupid AND gm.userid = u.id 
        WHERE m.name LIKE "quiz" 
            AND c.id =  ?
        ORDER BY "assessment","status","name","vorname","versuch"';


    $params = array($course->id);
    $records = $DB->get_recordset_sql($sql, $params);

    $data = array();
    foreach ($records as $record) {
        $data[] = $record;
    }
    $records->close();

    // Get groups for each user.
    $userids = array_unique(array_column($data, 'matrikelnummer', 'uid'));
    $usergroups = array();
    $coursecontext = \context_course::instance($course->id);
    foreach ($userids as $userid => $matrikelnummer) {
        // Get all roles for user
        $roles = array_column(get_user_roles($coursecontext, $userid, true), 'shortname');
        // Filter all admin users, not numeric unsernames and all users who has more roles than student.
        if (!is_siteadmin($userid) && is_numeric($matrikelnummer) && (count(array_diff($roles, ['student']))) === 0) {
            $usergroups[$userid] = groups_get_user_groups($course->id, $userid)[0];
        }
    }

    // Get groups for each quiz.
    $availabilitys = array_unique(array_column($data, 'availability', 'cmid'));
    $quizgroups = array();
    foreach ($availabilitys as $cmid => $availability) {
        $availabilityobject = json_decode($availability);
        if (isset($availabilityobject->c)) {
            foreach ($availabilityobject->c as $availabilityitem) {
                if ($availabilityitem->type === 'group' && isset($availabilityitem->id)) {
                    $quizgroups[$cmid][] = $availabilityitem->id;
                }
            }
        }
    }

    // Filter the record, if groups in the quiz exist and the group is not in usergroups and quizgroups
    $returndata = array();
    foreach ($data as $record) {
        if (isset($usergroups[$record->uid]) && (empty($quizgroups[$record->cmid]) || in_array($record->gid, array_intersect($usergroups[$record->uid], $quizgroups[$record->cmid])))) {
            array_push($returndata, $record);
        }
    }

    return $returndata;
}

/**
 * Get the course custom data.
 *
 * @param stdClass $course The course object to get the custom fields for.
 * @return stdClass
 * @throws moodle_exception
 */
function report_eledia_assessment_load_course_custom($course)
{

    $handler = \core_customfield\handler::get_handler('core_course', 'course');
    $datas = $handler->get_instance_data($course->id);
    foreach ($datas as $data) {
        if (empty($data->export_value())) {
            continue;
        }
        $fieldname = 'custom_field_' . $data->get_field()->get('shortname');
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
function report_eledia_assessment_get_course_overview_pdf($course, $context)
{
    global $CFG;

    require_once("$CFG->libdir/pdflib.php");
    require_once($CFG->libdir . '/tcpdf/tcpdf.php');

    // Start new PDF, set protection and author field.
    $pdf = new PDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(true, 0);
    $pdf->AddPage();

    // Set default font subsetting mode.
    $pdf->setFontSubsetting(true);
    // Set font.
    $pdf->SetFont('helvetica', '', 10, '', true);

    // Get report header block.
    $text = report_eledia_assessment_get_course_overview_header($course) . '<br><br>';
    // Get report table.
    $data = report_eledia_assessment_get_course_overview_data($course);
    $text .= report_eledia_assessment_get_course_overview_body($data);
    $pdf->writeHTML($text);

    // Write processed file to temporary path.
    $filename = $course->fullname . '_' . date('YmdHis', time()) . '.pdf';
    $content = $pdf->Output($filename, 'D');
    return $content;
}

require_once($CFG->libdir . '/formslib.php');

/**
 * Fake form for file download.
 */
class assessment_download extends moodleform
{
    public function definition()
    {
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
