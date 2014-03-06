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
 * Grading method controller for the Weightedtotal plugin
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("HTML/QuickForm/input.php");

class MoodleQuickForm_weightedtotaleditor extends HTML_QuickForm_input {

    function MoodleQuickForm_weightedtotaleditor($elementName=null, $elementLabel=null, $attributes=null) {
        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
    }

    public function toHtml() {
    }

    public function exportValue(&$submitValues, $assoc = false) {
    }
}


class weightedtotaleditor_form extends moodleform {
    function definition(){
        global $DB;
        $mform = $this->_form;
        $instance = $this->_customdata;

        $repeatarray = array();
        $repeatarray[] = $mform->createElement('header', 'criterionheader');
        $repeatarray[] = $mform->createElement('text', 'name', get_string('criterionname','gradingform_weightedtotal'),array('size'=>45));
        $repeatarray[] = $mform->createElement('textarea', 'description', get_string('criteriondescription','gradingform_weightedtotal'));
        $repeatarray[] = $mform->createElement('text', 'weight', get_string('criterionweight','gradingform_weightedtotal'));
        $sources[0] = 'manually';

        $usedgraders = $DB->get_records('gradingform_wt_crits');
        foreach($usedgraders as $usedgraderrecord) {
            $grader = $DB->get_record('gradingform_wt_graders',array('id' => $usedgraderrecord->graderid));
            $gradername = $grader->name;
            require_once($grader->path);
            $sources[$usedgraderrecord->graderid] = $gradername::name();

            $mform->addElement('hidden', 'grader' . (count($sources) - 1), $usedgraderrecord->graderid);
            $mform->setType('grader' . (count($sources) - 1), PARAM_INT);
        }
        $repeatarray[] = $mform->createElement('select', 'source', get_string('criterionsource','gradingform_weightedtotal'),$sources);
        $repeatarray[] = $mform->createElement('checkbox', 'delete', get_string('deletecriterion', 'gradingform_weightedtotal'));
        $repeatarray[] = $mform->createElement('hidden', 'criterionid', -1);

        if ($instance){
            $repeatno = $DB->count_records('gradingform_wt_crits', array('definitiontid'=>$instance['definitiontid']));
            $repeatno += 1;
        } else {
            $repeatno = 2;
        }

        $repeateloptions = array();

        $repeateloptions['name']['helpbutton'] = array('criterionname', 'gradingform_weightedtotal');
        $repeateloptions['description']['helpbutton'] = array('criteriondescription', 'gradingform_weightedtotal');
        $repeateloptions['weight']['helpbutton'] = array('criterionweight', 'gradingform_weightedtotal');
        $repeateloptions['source']['helpbutton'] = array('criterionsource', 'gradingform_weightedtotal');

        $repeateloptions['delete']['default'] = 0;
        $repeateloptions['delete']['disabledif'] = array('criterionid', 'eq', -1);

        $mform->setType('criterionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno,$repeateloptions, 'option_repeats', 'option_add_fields', 2);

        $mform->addElement('hidden', 'id', $instance['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'page', 'criterions');
        $mform->setType('page', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('savechanges', 'admin'));
    }
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        for ($i = 0; $i < $data['option_repeats']; $i++) {
            $nameisempty = $data['name'][$i] == '';
            $descriptionisempty = $data['description'][$i] == '';
            $weightisempty = $data['weight'][$i] == '';
            if ($nameisempty && $descriptionisempty && $weightisempty)
                continue;

            if ($nameisempty) {
                $errors["name[$i]"] = get_string('errornoname', 'gradingform_weightedtotal');
            }
            if ($weightisempty) {
                $errors["weight[$i]"] = get_string('errornoweight', 'gradingform_weightedtotal');
            }
            else {
                if (!is_numeric($data['weight'][$i])) {
                    $errors["weight[$i]"] = get_string('errornoweightnumeric', 'gradingform_weightedtotal');
                }
                else {
                    if ($data['weight'][$i] <= 0) {
                        $errors["weight[$i]"] = get_string('errornotpositiveweight', 'gradingform_weightedtotal');
                    }
                }
            }
        }

//        while (!empty($data['name'][$i] )) {
//            if(!isset($data['name'][$i])) {
//                $errors["name[$i]"] = get_string('errornoname', 'poasassignment');
//            }
//            if(!isset($data['weight'][$i])) {
//                $errors["weight[$i]"] = get_string('errornoweight', 'poasassignment');
//            }
//            if($data['weight'][$i] <= 0) {
//                $errors["weight[$i]"] = get_string('errornotpositiveweight', 'poasassignment');
//            }
//            $i++;
//        }
        if(count($errors) > 0) {
            return $errors;
        }
        else {
            return true;
        }
    }
}