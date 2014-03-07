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
 * Weightedtotal editor form
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot . '/lib/formslib.php');
//require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/weightedtotaleditor.php');
//require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/input.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/grade/grading/form/weightedtotal/weightedtotaleditor.php');

MoodleQuickForm::registerElementType('weightedtotaleditor', $CFG->dirroot.'/grade/grading/form/weightedtotal/weightedtotaleditor.php', 'MoodleQuickForm_weightedtotaleditor');

/**
 * Defines the weightedtotal edit form
 */
class gradingform_weightedtotal_editform extends moodleform {

    public function definition() {
        global $DB;
        $form = $this->_form;

        $form->addElement('hidden', 'areaid');
        $form->setType('areaid', PARAM_INT);

        $form->addElement('hidden', 'returnurl');

        $form->addElement('text', 'name', get_string('name', 'gradingform_weightedtotal'), array('size'=>52));
        $form->addRule('name', get_string('required'), 'required');
        $form->setType('name', PARAM_TEXT);

        $options = gradingform_weightedtotal_controller::description_form_field_options($this->_customdata['context']);
        $form->addElement('editor', 'description_editor', get_string('description', 'gradingform_weightedtotal'), null, $options);
        $form->setType('description_editor', PARAM_RAW);

        $choices = array();
        $choices[gradingform_controller::DEFINITION_STATUS_DRAFT]    = html_writer::tag('span', get_string('statusdraft', 'core_grading'), array('class' => 'status draft'));
        $choices[gradingform_controller::DEFINITION_STATUS_READY]    = html_writer::tag('span', get_string('statusready', 'core_grading'), array('class' => 'status ready'));
        $form->addElement('select', 'status', get_string('weightedtotalstatus', 'gradingform_weightedtotal'), $choices)->freeze();

        $repeatarray = array();
        $repeatarray[] = $form->createElement('header', 'criterionheader');
        $repeatarray[] = $form->createElement('text', 'name', get_string('criterionname','gradingform_weightedtotal'),array('size'=>45));
        $repeatarray[] = $form->createElement('textarea', 'description', get_string('criteriondescription','gradingform_weightedtotal'));
        $repeatarray[] = $form->createElement('text', 'weight', get_string('criterionweight','gradingform_weightedtotal'));
        $sources[0] = 'manually';

        $usedgraders = $DB->get_records('gradingform_wt_graders');
        foreach($usedgraders as $usedgraderrecord) {
            require_once($usedgraderrecord->path);
            $sources[$usedgraderrecord->graderid] = $usedgraderrecord->name;

            $form->addElement('hidden', 'grader' . (count($sources) - 1), $usedgraderrecord->id);
            $form->setType('grader' . (count($sources) - 1), PARAM_INT);
        }
        $repeatarray[] = $form->createElement('select', 'source', get_string('criterionsource','gradingform_weightedtotal'),$sources);
        $repeatarray[] = $form->createElement('checkbox', 'delete', get_string('deletecriterion', 'gradingform_weightedtotal'));
        $repeatarray[] = $form->createElement('hidden', 'criterionid', -1);

        $repeatno = 1;

        $repeateloptions = array();

        $repeateloptions['name']['helpbutton'] = array('criterionname', 'gradingform_weightedtotal');
        $repeateloptions['description']['helpbutton'] = array('criteriondescription', 'gradingform_weightedtotal');
        $repeateloptions['weight']['helpbutton'] = array('criterionweight', 'gradingform_weightedtotal');
        $repeateloptions['source']['helpbutton'] = array('criterionsource', 'gradingform_weightedtotal');

        $repeateloptions['delete']['default'] = 0;
        $repeateloptions['delete']['disabledif'] = array('criterionid', 'eq', -1);

        $form->setType('criterionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions, 'option_repeats', 'option_add_fields', 1);

        //$mform->addElement('hidden', 'id', $instance['id']);
        //$mform->setType('id', PARAM_INT);

        //$form->addElement('hidden', 'page', 'criterions');
        //$form->setType('page', PARAM_TEXT);

        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'saveweightedtotal', get_string('saveweightedtotal', 'gradingform_weightedtotal'));
        if ($this->_customdata['allowdraft']) {
            $buttonarray[] = &$form->createElement('submit', 'saveweightedtotaldraft', get_string('saveweightedtotaldraft', 'gradingform_weightedtotal'));
        }
        $editbutton = &$form->createElement('submit', 'editweightedtotal', ' ');
        $editbutton->freeze();
        $buttonarray[] = &$editbutton;
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    public function definition_after_data() {
        $form = $this->_form;
        $el = $form->getElement('status');

        if (!$el->getValue()) {
            $form->removeElement('status');
        } else {
            $vals = array_values($el->getValue());
            if ($vals[0] == gradingform_controller::DEFINITION_STATUS_READY) {
                $this->findButton('saveweightedtotal')->setValue(get_string('save', 'gradingform_weightedtotal'));
            }
        }
    }

    public function validation($data, $files) {
        $err = array();
        return $err;
    }

    public function get_data() {
        $data = parent::get_data();
        if (!empty($data->weightedtotal)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_READY;
        } else if (!empty($data->saveweightedtotaldraft)) {
            $data->status = gradingform_controller::DEFINITION_STATUS_DRAFT;
        }
        return $data;
    }

    public function need_confirm_regrading($controller) {
        return false;
    }

    protected function &findButton($elementname) {
        $form = $this->_form;
        $buttonar =& $form->getElement('buttonar');
        $elements =& $buttonar->getElements();
        foreach ($elements as $el) {
            if ($el->getName() == $elementname) {
                return $el;
            }
        }
        return null;
    }
}
