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

    public function get_renderer(moodle_page $page) {
    }

    public function render_preview(moodle_page $page) {
    }

    protected function delete_plugin_definition() {
    }

    public function get_or_create_instance($instanceid, $raterid, $itemid) {
        return null;
    }

    public function extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node=null) {
    }

    public function extend_navigation(global_navigation $navigation, navigation_node $node=null) {
    }

    public function update_definition(stdClass $newdefinition, $usermodified = null) {
    }

    public function update_or_check_weightedtotal(stdClass $newdefinition, $usermodified = null, $doupdate = false) {
        return null;
    }

    public function get_definition_for_editing($addemptygroup = false) {
        return null;
    }

    public function get_definition_copy(gradingform_controller $target) {
        return null;
    }

    public function get_formatted_description() {
    }

    public function mark_for_regrade() {
    }

    protected function load_definition() {
    }

    public function render_grade($page, $itemid, $gradinginfo, $defaultcontent, $cangrade) {
        return null;
    }

    public function get_min_max_score() {
        return null;
    }

    public static function sql_search_from_tables($gdid) {
        return null;
    }

    public static function sql_search_where($token) {
        return null;
    }

    public static function description_form_field_options($context) {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'context'  => $context,
        );
    }

    public static function get_default_options() {
        return null;
    }

    public function get_options() {
        return null;
    }
}