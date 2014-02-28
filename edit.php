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
 * Weightedtotal editor page
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/lib.php');
require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/edit_form.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

$areaid = required_param('areaid', PARAM_INT);

$manager = get_grading_manager($areaid);

list($context, $course, $cm) = get_context_info_array($manager->get_context()->id);

require_login($course, true, $cm);
require_capability('moodle/grade:managegradingforms', $context);

$controller = $manager->get_controller('weightedtotal');

$PAGE->set_url(new moodle_url('/grade/grading/form/weightedtotal/edit.php', array('areaid' => $areaid)));
$PAGE->set_title(get_string('defineweightedtotal', 'gradingform_weightedtotal'));
$PAGE->set_heading(get_string('defineweightedtotal', 'gradingform_weightedtotal'));

$mform = new gradingform_weightedtotal_editform(null, array('areaid' => $areaid, 'context' => $context, 'allowdraft' => !$controller->has_active_instances()), 'post', '', array('class' => 'gradingform_weightedtotal_editform'));
$data = $controller->get_definition_for_editing(true);
$returnurl = optional_param('returnurl', $manager->get_management_url(), PARAM_LOCALURL);
$data->returnurl = $returnurl;
$mform->set_data($data);
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($mform->is_submitted() && $mform->is_validated() && !$mform->need_confirm_regrading($controller)) {
    $controller->update_definition($mform->get_data());
    redirect($returnurl);
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();