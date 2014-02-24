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

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Defines the weightedtotal edit form
 */
class gradingform_weightedtotal_editform extends moodleform {

    public function definition() {
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

        $element = $form->addElement('weightedtotaleditor', 'weightedtotal', get_string('weightedtotal', 'gradingform_weightedtotal'));
        $form->setType('weightedtotal', PARAM_RAW);
       // $element->freeze();

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

    }

    public function validation($data, $files) {
        $err = null;
        return $err;
    }

    public function get_data() {
        $data = null;
        return $data;
    }

    public function need_confirm_regrading($controller) {
        return true;
    }

    protected function &findButton($elementname) {
        return null;
    }
}
