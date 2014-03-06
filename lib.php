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
 * Grading method controller for the WeightedTotal plugin
 *
 * @package    gradingform
 * @subpackage weightedtotal
 * @author     Andrew Ryaskov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/grading/form/lib.php');

/**
 * This controller encapsulates the weightedtotal grading logic
 */
class gradingform_weightedtotal_controller extends gradingform_controller {

    /** weightedtotal display mode: For editing (moderator or teacher creates a weightedtotal) */
    const DISPLAY_EDIT_FULL     = 1;
    /** weightedtotal display mode: Preview the weightedtotal design with hidden fields */
    const DISPLAY_EDIT_FROZEN   = 2;
    /** weightedtotal display mode: Preview the weightedtotal design (for person with manage permission) */
    const DISPLAY_PREVIEW       = 3;
    /** weightedtotal display mode: Preview the weightedtotal (for people being graded) */
    const DISPLAY_PREVIEW_GRADED= 8;
    /** weightedtotal display mode: For evaluation, enabled (teacher grades a student) */
    const DISPLAY_EVAL          = 4;
    /** weightedtotal display mode: For evaluation, with hidden fields */
    const DISPLAY_EVAL_FROZEN   = 5;
    /** weightedtotal display mode: Teacher reviews filled weightedtotal */
    const DISPLAY_REVIEW        = 6;
    /** weightedtotal display mode: Display filled weightedtotal (i.e. students see their grades) */
    const DISPLAY_VIEW          = 7;

    /**
     * Returns the weightedtotal plugin renderer
     *
     * @param moodle_page $page the target page
     * @return gradingform_weightedtotal_renderer
     */
    public function get_renderer(moodle_page $page) {
        return $page->get_renderer('gradingform_'. $this->get_method_name());
    }

    /** TODO
     * Returns the HTML code displaying the preview of the weightedtotal grading form
     *
     * @throws coding_exception
     * @param moodle_page $page the target page
     * @return string
     */
    public function render_preview(moodle_page $page) {
        if (!$this->is_form_defined()) {
            throw new coding_exception('It is the caller\'s responsibility to make sure that the form is actually defined');
        }

        $output = $this->get_renderer($page);
        $options = $this->get_options();
        $weightedtotal = '';
        if (has_capability('moodle/grade:managegradingforms', $page->context)) {
            $weightedtotal .= $output->display_weightedtotal_mapping_explained(array('minpoints' => 0, 'maxpoints' => 100));
            $weightedtotal .= $output->display_weightedtotal($options, self::DISPLAY_PREVIEW, 'weightedtotal');
        } else {
            $weightedtotal .= $output->display_weightedtotal($options, self::DISPLAY_PREVIEW_GRADED, 'weightedtotal');
        }

        return $weightedtotal;
    }

    /**
     * Deletes the weightedtotal definition and all the associated information
     */
    protected function delete_plugin_definition() {
        global $DB;

        $instances = array_keys($DB->get_records('grading_instances', array('definitionid' => $this->definition->id), '', 'id'));
        $DB->delete_records_list('gradingform_wt_fills', 'instanceid', $instances);
        $DB->delete_records_list('grading_instances', 'id', $instances);
        $DB->delete_records('gradingform_wt_crits', array('definitionid' => $this->definition->id) );
    }

    /**
     * If instanceid is specified and grading instance exists and it is created by this rater for
     * this item, this instance is returned.
     * If there exists a draft for this raterid+itemid, take this draft (this is the change from parent)
     * Otherwise new instance is created for the specified rater and itemid
     *
     * @param int $instanceid
     * @param int $raterid
     * @param int $itemid
     * @return gradingform_instance
     */
    public function get_or_create_instance($instanceid, $raterid, $itemid) {
        global $DB;
        if ($instanceid &&
            $instance = $DB->get_record('grading_instances', array('id'  => $instanceid, 'raterid' => $raterid, 'itemid' => $itemid))) {
            return $this->get_instance($instance);
        }
        if ($itemid && $raterid) {
            if ($rs = $DB->get_records('grading_instances', array('definitionid' => $this->definition->id, 'raterid' => $raterid, 'itemid' => $itemid), 'timemodified DESC', '*', 0, 1)) {
                $record = reset($rs);
                $currentinstance = $this->get_current_instance($raterid, $itemid);
                if ($record->status == gradingform_weightedtotal_instance::INSTANCE_STATUS_INCOMPLETE &&
                    (!$currentinstance || $record->timemodified > $currentinstance->get_data('timemodified'))) {
                    $record->isrestored = true;
                    return $this->get_instance($record);
                }
            }
        }
        return $this->create_instance($raterid, $itemid);
    }

    /**
     * Extends the module settings navigation
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING, the user has the permission moodle/grade:managegradingforms
     * and there is an area with the active grading method set to the given plugin.
     *
     * @param settings_navigation $settingsnav {@link settings_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
        $node->add(get_string('defineweightedtotal', 'gradingform_weightedtotal'),
            $this->get_editor_url(), settings_navigation::TYPE_CUSTOM,
            null, null, new pix_icon('icon', '', 'gradingform_weightedtotal'));
    }

    /**
     * Extends the module navigation
     *
     * This function is called when the context for the page is an activity module with the
     * FEATURE_ADVANCED_GRADING and there is an area with the active grading method set to the given plugin.
     *
     * @param global_navigation $navigation {@link global_navigation}
     * @param navigation_node $node {@link navigation_node}
     */
    public function extend_navigation(global_navigation $navigation, navigation_node $node=null) {
        if (has_capability('moodle/grade:managegradingforms', $this->get_context())) {
            // no need for preview if user can manage forms, he will have link to manage.php in settings instead
            return;
        }
        if ($this->is_form_defined() && ($options = $this->get_options()) && !empty($options['alwaysshowdefinition'])) {
            $node->add(get_string('gradingof', 'gradingform_weightedtotal', get_grading_manager($this->get_areaid())->get_area_title()),
                new moodle_url('/grade/grading/form/'.$this->get_method_name().'/preview.php', array('areaid' => $this->get_areaid())),
                settings_navigation::TYPE_CUSTOM);
        }
    }

    /**
     * Saves the weightedtotal definition into the database
     *
     * @see parent::update_definition()
     * @param stdClass $newdefinition weightedtotal definition data
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     */
    public function update_definition(stdClass $newdefinition, $usermodified = null) {
        $this->update_or_check_weightedtotal($newdefinition, $usermodified, true);
        if (isset($newdefinition->weightedtotal['regrade']) && $newdefinition->weightedtotal['regrade']) {
            $this->mark_for_regrade();
        }
    }

    /** TODO
     * Either saves the weightedtotal definition into the database or check if it has been changed.
     * Returns the level of changes:
     * 0 - no changes
     * 1 - students probably do not require re-grading (minimal changes e.g. description of criterion)
     * 2 - all students require manual re-grading
     *
     * @param stdClass $newdefinition weightedtotal definition data
     * @param int|null $usermodified optional userid of the author of the definition, defaults to the current user
     * @param boolean $doupdate if true actually updates DB, otherwise performs a check
     *
     * @return int
     */
    public function update_or_check_weightedtotal(stdClass $newdefinition, $usermodified = null, $doupdate = false) {
        global $DB;

        // firstly update the common definition data in the {grading_definition} table
        if ($this->definition === false) {
            if (!$doupdate) {
                // if we create the new definition there is no such thing as re-grading anyway
                return 2;
            }
            // if definition does not exist yet, create a blank one
            // (we need id to save files embedded in description)
            parent::update_definition(new stdClass(), $usermodified);
            parent::load_definition();
        }
        if (!isset($newdefinition->weightedtotal['options'])) {
            $newdefinition->weightedtotal['options'] = self::get_default_options();
        }
        $newdefinition->options = json_encode($newdefinition->weightedtotal['options']);
        $editoroptions = self::description_form_field_options($this->get_context());
        $newdefinition = file_postupdate_standard_editor($newdefinition, 'description', $editoroptions, $this->get_context(),
            'grading', 'description', $this->definition->id);

        $haschanges = array();

        $critsfields = array('name', 'sortorder', 'definitionid', 'weight', 'description');

        $record = $newdefinition->weightedtotal;

        $data = array();
        foreach ($record as $key=>$value) {
            if ($key == 'definitionid') {
                $record[$key] = trim(clean_param($record[$key], PARAM_TEXT));
            }
            $data[$key] = $record[$key];
        }
        if (!empty($data)) {
            // update only if something is changed
            $data['id'] = $record['id'];
            if ($doupdate) {
                $DB->update_record('gradingform_wt_crits', $data);
            }
        }

        foreach (array('status', 'description', 'descriptionformat', 'name', 'options') as $key) {
            if (isset($newdefinition->$key) && $newdefinition->$key != $this->definition->$key) {
                $haschanges[1] = true;
            }
        }
        if ($usermodified && $usermodified != $this->definition->usermodified) {
            $haschanges[1] = true;
        }
        if (!count($haschanges)) {
            return 0;
        }
        if ($doupdate) {
            parent::update_definition($newdefinition, $usermodified);
            $this->load_definition();
        }
        // return the maximum level of changes
        $changelevels = array_keys($haschanges);
        sort($changelevels);
        return array_pop($changelevels);
    }

    /**
     * Converts the current definition into an object suitable for the editor form's set_data()
     *
     * @return stdClass
     */
    public function get_definition_for_editing() {

        $definition = $this->get_definition();
        $properties = new stdClass();
        $properties->areaid = $this->areaid;
        if ($definition) {
            foreach (array('id', 'name', 'description', 'descriptionformat', 'status') as $key) {
                $properties->$key = $definition->$key;
            }
            $options = self::description_form_field_options($this->get_context());
            $properties = file_prepare_standard_editor($properties, 'description', $options, $this->get_context(),
                'grading', 'description', $definition->id);
        }
        $properties->weightedtotal = array('options' => $this->get_options());

        return $properties;
    }

    /**
     * Returns the form definition suitable for cloning into another area
     *
     * @see parent::get_definition_copy()
     * @param gradingform_controller $target the controller of the new copy
     * @return stdClass definition structure to pass to the target's {@link update_definition()}
     */
    public function get_definition_copy(gradingform_controller $target) {

        $new = parent::get_definition_copy($target);
        $old = $this->get_definition_for_editing();
        $new->description_editor = $old->description_editor;
        $new->weightedtotal = array('options' => $old->weightedtotal['options']);

        return $new;
    }

    /**
     * Formats the definition description for display on page
     *
     * @return string
     */
    public function get_formatted_description() {
        if (!isset($this->definition->description)) {
            return '';
        }
        $context = $this->get_context();

        $options = self::description_form_field_options($this->get_context());
        $description = file_rewrite_pluginfile_urls($this->definition->description, 'pluginfile.php', $context->id,
            'grading', 'description', $this->definition->id, $options);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->definition->descriptionformat, $formatoptions);
    }

    /**
     * Marks all instances filled with this weightedtotal with the status INSTANCE_STATUS_NEEDUPDATE
     */
    public function mark_for_regrade() {
        global $DB;
        if ($this->has_active_instances()) {
            $conditions = array('definitionid'  => $this->definition->id,
                'status'  => gradingform_instance::INSTANCE_STATUS_ACTIVE);
            $DB->set_field('grading_instances', 'status', gradingform_instance::INSTANCE_STATUS_NEEDUPDATE, $conditions);
        }
    }

    /** TODO TEST IT! Especially SQL request
     * Loads the weightedtotal form definition if it exists
     *
     */
    protected function load_definition() {
        global $DB;

        $sql = "SELECT gd.*,
                       crtrn.id AS crtrnid, crtrn.sortorder AS crtrnsortorder, crtrn.description AS crtrndescription,
                       crtrn.name AS crtrnname, crtrn.description AS crtrndescription, crtrn.weight AS crtrnweight,
                       crtrn.graderid AS crtrngraderid
                  FROM {grading_definitions} gd
             LEFT JOIN {gradingform_wt_crits} crtrn ON (crtrn.definitionid = gd.id)
                 WHERE gd.areaid = :areaid AND gd.method = :method
              ORDER BY crtrn.sortorder";
        $params = array('areaid' => $this->areaid, 'method' => $this->get_method_name());

        $rs = $DB->get_recordset_sql($sql, $params);
        $this->definition = false;
        foreach ($rs as $record) {
            // pick the common definition data
            if ($this->definition === false) {
                $this->definition = new stdClass();
                foreach (array('id', 'name', 'description', 'descriptionformat', 'status', 'copiedfromid',
                             'timecreated', 'usercreated', 'timemodified', 'usermodified', 'timecopied', 'options') as $fieldname) {
                    $this->definition->$fieldname = $record->$fieldname;
                }
                $this->definition->weightedtotal = array();
            }

            // pick the items data
            if (!empty($record->crtrnid)) {
                foreach (array('id', 'definitionid', 'name', 'weight', 'sortorder', 'description', 'graderid') as $fieldname) {
                    $value = $record->{'crtrn'.$fieldname};
                    if ($fieldname == 'weight') {
                        $value = (float)$value; // To prevent display like 1.00000
                    }
                    $this->definition->weightedtotal[$fieldname] = $value;
                }
            }
        }
        $rs->close();
    }

    /**
     * Returns html code to be included in student's feedback.
     *
     * @param moodle_page $page
     * @param int $itemid
     * @param array $gradinginfo result of function grade_get_grades
     * @param string $defaultcontent default string to be returned if no active grading is found
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     */
    public function render_grade($page, $itemid, $gradinginfo, $defaultcontent, $cangrade) {
        return $this->get_renderer($page)->display_instances($this->get_active_instances($itemid), $defaultcontent, $cangrade);
    }

    /**
     * Calculates and returns the possible minimum and maximum score (in points) for this weightedtotal
     *
     * @return array
     */
    public function get_min_max_score() {
        if (!$this->is_form_available()) {
            return null;
        }
        $returnvalue = array('minpoints' => 0, 'maxpoints' => 100);

        return $returnvalue;
    }

    //// full-text search support /////////////////////////////////////////////

    /**
     * Prepare the part of the search query to append to the FROM statement
     *
     * @param string $gdid the alias of grading_definitions.id column used by the caller
     * @return string
     */
    public static function sql_search_from_tables($gdid) {
        return " LEFT JOIN {gradingform_wt_crits} crtrn ON (crtrn.definitionid = $gdid)";
    }

    /**
     * Prepare the parts of the SQL WHERE statement to search for the given token
     *
     * The returned array cosists of the list of SQL comparions and the list of
     * respective parameters for the comparisons. The returned chunks will be joined
     * with other conditions using the OR operator.
     *
     * @param string $token token to search for
     * @return array
     */
    public static function sql_search_where($token) {
        global $DB;

        $subsql = array();
        $params = array();

        $subsql[] = $DB->sql_like('crtrn.definitionid', '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($token).'%';

        return array($subsql, $params);
    }

    /**
     * Options for displaying the weightedtotal description field in the form
     *
     * @param object $context
     * @return array options for the form description field
     */
    public static function description_form_field_options($context) {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'context'  => $context,
        );
    }

    /**
     * Returns the default options for the weightedtotal display
     *
     * @return array
     */
    public static function get_default_options() {
        $options = array(
            'alwaysshowdefinition' => 1,
            'showcritpointseval' => 1,
            'showcritpointstudent' => 1,
            'enablecritremarks' => 1,
            'showremarksstudent' => 1
        );
        return $options;
    }

    /**
     * Gets the options of this weightedtotal definition, fills the missing options with default values
     *
     * @return array
     */
    public function get_options() {
        $options = self::get_default_options();
        if (!empty($this->definition->options)) {
            $thisoptions = json_decode($this->definition->options);
            foreach ($thisoptions as $option => $value) {
                $options[$option] = $value;
            }
        }
        return $options;
    }
}

/**
 * Class to manage one weightedtotal grading instance. Stores information and performs actions like
 * update, copy, validate, submit, etc.
 *
 */
class gradingform_weightedtotal_instance extends gradingform_instance {

    protected $weightedtotal;

    /**
     * Deletes this (INCOMPLETE) instance from database.
     */
    public function cancel() {
        global $DB;

        parent::cancel();
        $DB->delete_records('gradingform_wt_fills', array('instanceid' => $this->get_id()));
    }

    /**
     * Duplicates the instance before editing (optionally substitutes raterid and/or itemid with
     * the specified values)
     *
     * @param int $raterid value for raterid in the duplicate
     * @param int $itemid value for itemid in the duplicate
     * @return int id of the new instance
     */
    public function copy($raterid, $itemid) {
        global $DB;
        $instanceid = parent::copy($raterid, $itemid);
        $currentgrade = $this->get_weightedtotal_filling();
        foreach ($currentgrade as $record) {
            $params = array('instanceid' => $instanceid, 'criterionid' =>  $record['criterionid'],
                            'value' =>  $record['value']);
            $DB->insert_record('gradingform_wt_fills', $params);
        }

        return $instanceid;
    }

    /**
     * Retrieves from DB and returns the data how this weightedtotal was filled
     *
     * @param boolean $force whether to force DB query even if the data is cached
     * @return array
     */
    public function get_weightedtotal_filling($force = false) {
        global $DB;

        if ($this->weightedtotal['fills'] === null || $force) {
            $records = $DB->get_records('gradingform_wt_fills', array('instanceid' => $this->get_id()));
            foreach($records as $row){
                $row['weight'] = $DB->get_record('gradingform_wt_crits', array('id' => $row['criterionid']) );
            }

            $this->weightedtotal['fills'] = $records;
        }
        return $this->weightedtotal['fills'];
    }

    /**
     * Updates the instance with the data received from grading form. This function may be
     * called via AJAX when grading is not yet completed, so it does not change the
     * status of the instance.
     *
     * @param array $data
     */
    public function update($data) {
        global $DB;

        $currentgrade = $this->get_weightedtotal_filling();
        parent::update($data);

        foreach($currentgrade as $row) {
                $newrecord = array('instanceid' => $this->get_id(), 'criterionid' =>  $row['criterionid'],
                    'value' =>  $row['value']);
                $recs = $DB->get_records('gradingform_wt_fills', $newrecord);
                if ( !count($recs) ) {
                    $newrecordobject = new stdClass();
                    $newrecordobject->instanceid = $newrecord['instanceid'];
                    $newrecordobject->criterionid = $newrecord['criterionid'];
                    $newrecordobject->value = $newrecord['value'];
                    $DB->insert_record('gradingform_wt_fills', $newrecordobject);
                } else {
                    $DB->update_record('gradingform_wt_fills', $newrecordobject);
                }
        }

        $this->get_weightedtotal_filling(true);
    }

    /**
     * Calculates the grade to be pushed to the gradebook
     *
     * @return int the valid grade from $this->get_controller()->get_grade_range()
     */
    public function get_grade() {
        $result = null;
        $fills = $this->get_weightedtotal_filling();

        $nom = 0;
        $denom = 0;
        foreach ($fills as $row){
            $nom += $row['value'] * $row['weight'];
            $denom += $row['weight'];
        }
        $result = round($nom / $denom, 2);

        return $result;
    }

    /**
     * Returns html for form element of type 'grading'.
     *
     * @param moodle_page $page
     * @param MoodleQuickForm_grading $gradingformelement
     * @return string
     */
    public function render_grading_element($page, $gradingformelement) {
        if (!$gradingformelement->_flagFrozen) {
            $module = array('name'=>'gradingform_weightedtotal', 'fullpath'=>'/grade/grading/form/weightedtotal/js/weightedtotal.js');
            $page->requires->js_init_call('M.gradingform_weightedtotal.init', array(array('name' => $gradingformelement->getName())), true, $module);
            $mode = gradingform_weightedtotal_controller::DISPLAY_EVAL;
        } else {
            if ($gradingformelement->_persistantFreeze) {
                $mode = gradingform_weightedtotal_controller::DISPLAY_EVAL_FROZEN;
            } else {
                $mode = gradingform_weightedtotal_controller::DISPLAY_REVIEW;
            }
        }
        //$groups = $this->get_controller()->get_definition()->weightedtotal_groups;
        $options = $this->get_controller()->get_options();
        $value = $gradingformelement->getValue();
        $html = '';
        if ($value === null) {
            $value = $this->get_weightedtotal_filling();
        } else if (!$this->validate_grading_element($value)) {
            $html .= html_writer::tag('div', get_string('weightedtotalnotcompleted', 'gradingform_weightedtotal'), array('class' => 'gradingform_weightedtotal-error'));
        }
        $currentinstance = $this->get_current_instance();
        if ($currentinstance && $currentinstance->get_status() == gradingform_instance::INSTANCE_STATUS_NEEDUPDATE) {
            $html .= html_writer::tag('div', get_string('needregrademessage', 'gradingform_weightedtotal'), array('class' => 'gradingform_weightedtotal-regrade'));
        }

        $html .= html_writer::tag('div', $this->get_controller()->get_formatted_description(), array('class' => 'gradingform_weightedtotal-description'));

        $html .= $this->get_controller()->get_renderer($page)->display_weightedtotal( $options, $mode, $gradingformelement->getName(), $value);
        return $html;
    }

    /**
     * Gets the options of this weightedtotal definition, fills the missing options with default values
     *
     * @return array
     */
    public function get_options() {
        $options = self::get_default_options();
        if (!empty($this->definition->options)) {
            $thisoptions = json_decode($this->definition->options);
            foreach ($thisoptions as $option => $value) {
                $options[$option] = $value;
            }
        }
        return $options;
    }

}