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
 * Renderer for the Weightedtotal plugin
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Weightedtotal grading method plugin renderer
 *
 */
class gradingform_weightedtotal_renderer extends plugin_renderer_base {

    protected function weightedtotal_template($mode, $options, $elementname, $critstemplate) {
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode
        switch ($mode) {
            case gradingform_weightedtotal_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable'; break;
            case gradingform_weightedtotal_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';  break;
            case gradingform_weightedtotal_controller::DISPLAY_PREVIEW:
            case gradingform_weightedtotal_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';  break;
            case gradingform_weightedtotal_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable'; break;
            case gradingform_weightedtotal_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';  break;
            case gradingform_weightedtotal_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';  break;
            case gradingform_weightedtotal_controller::DISPLAY_VIEW:
                $classsuffix = ' view';  break;
        }

        $weightedtotaltemplate = html_writer::start_tag('div', array('id' => 'weightedtotal-{NAME}', 'class' => 'clearfix gradingform_weightedtotal'.$classsuffix));
        if ($mode == gradingform_weightedtotal_controller::DISPLAY_EDIT_FULL) {
            $weightedtotaltemplate .= html_writer::tag('span', 'This is full mode');
        }
        $weightedtotaltemplate .= $this->weightedtotal_edit_options($mode, $options);
        $weightedtotaltemplate .= html_writer::end_tag('div');

        return str_replace('{NAME}', $elementname, $weightedtotaltemplate);
    }

    protected function weightedtotal_edit_options($mode, $options) {
        if ($mode != gradingform_weightedtotal_controller::DISPLAY_EDIT_FULL
            && $mode != gradingform_weightedtotal_controller::DISPLAY_EDIT_FROZEN
            && $mode != gradingform_weightedtotal_controller::DISPLAY_PREVIEW) {
            // Options are displayed only for people who can manage
            return '';
        }
        $html = html_writer::start_tag('div', array('class' => 'options'));
        $html .= html_writer::tag('div', get_string('weightedtotaloptions', 'gradingform_weightedtotal'), array('class' => 'optionsheading'));
        $attrs = array('type' => 'hidden', 'name' => '{NAME}[options][optionsset]', 'value' => 1);
        foreach ($options as $option => $value) {
            $html .= html_writer::start_tag('div', array('class' => 'option '.$option));
            $attrs = array('name' => '{NAME}[options]['.$option.']', 'id' => '{NAME}-options-'.$option);

            if ($mode == gradingform_weightedtotal_controller::DISPLAY_EDIT_FROZEN && $value) {
                $html .= html_writer::empty_tag('input', $attrs + array('type' => 'hidden', 'value' => $value));
            }
            // Display option as checkbox
            $attrs['type'] = 'checkbox';
            $attrs['value'] = 1;
            if ($value) {
                $attrs['checked'] = 'checked';
            }
            if ($mode == gradingform_weightedtotal_controller::DISPLAY_EDIT_FROZEN || $mode == gradingform_weightedtotal_controller::DISPLAY_PREVIEW) {
                $attrs['disabled'] = 'disabled';
                unset($attrs['name']);
            }
            $html .= html_writer::empty_tag('input', $attrs);
            $html .= html_writer::tag('label', get_string($option, 'gradingform_weightedtotal'), array('for' => $attrs['id']));

            $html .= html_writer::end_tag('div'); // .option
        }
        $html .= html_writer::end_tag('div'); // .options
        return $html;
    }


    public function display_weightedtotal($options, $mode, $elementname = null, $values = null) {

        $critstemplate = '';

        // Temporal solution...
        $critstemplate .= '<table border="1"><caption>';
        $critstemplate .= get_string('critsandweightstablecaption','gradingform_weightedtotal');
        $critstemplate .= '</caption>';
        $critstemplate .= '<th>'.get_string('criterion','gradingform_weightedtotal').'</th>';
        $critstemplate .= '<th>'.get_string('weight','gradingform_weightedtotal').'</th>';

        foreach($values as $value){
            $critstemplate .= '<tr><td>'.$value['name'].'</td><td>'.$value['weight'].'</td></tr>';
        }
        $critstemplate .= '</table>';

        return $this->weightedtotal_template($mode, $options, $elementname, $critstemplate);
    }

    /** MAYBE TODO
     * Generates and returns HTML code to display information box about criterions and weights
     *
     * @param array
     * @return string
     */
    public function display_weightedtotal_mapping_explained($crits_and_weights) {
        $html = '';
        if (!$crits_and_weights) {
            return $html;
        }
        $html .= $this->box(
            html_writer::tag('h4', get_string('weightedtotalmapping', 'gradingform_weightedtotal')).
            html_writer::tag('div'
                , get_string('weightedtotalmappingexplained'
                , 'gradingform_weightedtotal'
                ,(object)$crits_and_weights))
                , 'generalbox weightedtotalmappingexplained');
        return $html;
    }

    /**
     * Displays for the student the list of instances or default content if no instances found
     *
     * @param array $instances array of objects of type gradingform_weightedtotal_instance
     * @param string $defaultcontent default string that would be displayed without advanced grading
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function display_instances($instances, $defaultcontent, $cangrade) {
        $return = '';
        if (sizeof($instances)) {
            $return .= html_writer::start_tag('div', array('class' => 'advancedgrade'));
            $idx = 0;
            foreach ($instances as $instance) {
                $return .= $this->display_instance($instance, $idx++, $cangrade);
            }
            $return .= html_writer::end_tag('div');
        }
        return $return. $defaultcontent;
    }
}