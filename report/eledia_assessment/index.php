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
require_once($CFG->dirroot.'/lib/statslib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/report/eledia_assessment/locallib.php');

$report     = optional_param('report', STATS_REPORT_ACTIVE_COURSES, PARAM_INT);
$time       = optional_param('time', 0, PARAM_INT);
$numcourses = optional_param('numcourses', 20, PARAM_INT);
$systemcontext = context_system::instance();

admin_externalpage_setup('reporteledia_assessment', '', null, '', array('pagelayout' => 'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading($reportoptions[$report]);

echo $OUTPUT->footer();
