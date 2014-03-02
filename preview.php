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
 * Weightedtotal preview page
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/lib.php');
require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/edit_form.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

$areaid = required_param('areaid', PARAM_INT);

$manager = get_grading_manager($areaid);

list($context, $course, $cm) = get_context_info_array($manager->get_context()->id);

require_login($course, true, $cm);

$controller = $manager->get_controller('weightedtotal');
$options = $controller->get_options();

if (!$controller->is_form_defined() || empty($options['alwaysshowdefinition'])) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('previewweightedtotal', 'gradingform_weightedtotal'));
}

$title = get_string('gradingof', 'gradingform_weightedtotal', $manager->get_area_title());
$PAGE->set_url(new moodle_url('/grade/grading/form/weightedtotal/preview.php', array('areaid' => $areaid)));
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $OUTPUT->box($controller->get_formatted_description(), 'gradingform_weightedtotal-description');

echo $controller->render_preview($PAGE);
echo $OUTPUT->footer();