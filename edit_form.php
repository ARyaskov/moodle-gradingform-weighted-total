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
