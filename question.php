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
 * @package qtype_sc
 * @author        Jürgen Zimmer (juergen.zimmer@edaktik.at)
 * @author        Andreas Hruska (andreas.hruska@edaktik.at)
 * @copyright     2017 eDaktik GmbH {@link http://www.edaktik.at}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


class qtype_sc_question extends question_graded_automatically_with_countback {

    public $rows;

    public $scoringmethod;

    public $shuffleanswers;

    public $numberofrows;

    public $order = null;

    public $editedquestion;

    public $correctrow;

    // All the methods needed for option shuffling.
    /**
     * (non-PHPdoc).
     *
     * @see question_definition::start_attempt()
     */
    public function start_attempt(question_attempt_step $step, $variant) {
        $this->order = array_keys($this->rows);
        if ($this->shuffleanswers) {
            shuffle($this->order);
        }
        $step->set_qt_var('_order', implode(',', $this->order));
    }

    /**
     * (non-PHPdoc).
     *
     * @see question_definition::apply_attempt_state()
     */
    public function apply_attempt_state(question_attempt_step $step) {
        $this->order = explode(',', $step->get_qt_var('_order'));

        // Add any missing answers. Sometimes people edit questions after they
        // have been attempted which breaks things.
        // Retrieve the question rows (mtf options).

        if (!isset($this->rows[$this->order[0]])) {
            global $DB;
            $rows = $DB->get_records('qtype_sc_rows',
                    array('questionid' => $this->id
                    ), 'number ASC', 'id, number', 0, $this->numberofrows);

            $arr = array();
            foreach ($rows as $row) {
                $arr[$row->number - 1] = $row->id;
            }
            unset($this->order);
            $this->order = $arr;
            $this->editedquestion = 1;
        }
        parent::apply_attempt_state($step);
    }

    /**
     *
     * @param question_attempt $qa
     *
     * @return multitype:
     */
    public function get_order(question_attempt $qa) {
        $this->init_order($qa);

        return $this->order;
    }

    /**
     * Initialises the order (if it is not set yet) by decoding
     * the question attempt variable '_order'.
     *
     * @param question_attempt $qa
     */
    protected function init_order(question_attempt $qa) {
        if (is_null($this->order)) {
            $this->order = explode(',', $qa->get_step(0)->get_qt_var('_order'));
        }
    }

    /**
     * Returns the name field name for option choice. Every option has its own fieldname because
     * options essentially behave like checkboxes.
     *
     * @param unknown $key
     * @return string
     */
    public function optionfield($key) {
        return 'option' . $key;
    }

    /**
     * Returns the name field name for distractor buttons.
     *
     * @param unknown $key
     * @return type
     */
    public function distractorfield($key) {
        return 'distractor' . $key;
    }

    /**
     * Checks whether an row is answered by a given response.
     *
     * @param type $response
     * @param type $row
     * @param type $col
     *
     * @return bool
     */
    public function is_answered($response, $rownumber) {
        $optionfield = $this->optionfield($rownumber);
        // Get the value of the radiobutton array, if it exists in the response.
        return array_key_exists($optionfield, $response) && $response[$optionfield];
    }

    /**
     * @param $response
     * @param $key
     * @return bool
     */
    public function is_row_selected($response, $key) {
        $optionfield = $this->optionfield($key);
        if (array_key_exists($optionfield, $response) && $response[$optionfield] == 1) {
            return true;
        }
        return false;
    }

    /**
     * Returns the last response in a question attempt.
     * @param question_attempt $qa
     * @return array|mixed
     */
    public function get_response(question_attempt $qa) {
        return $qa->get_last_qt_data();
    }

    /**
     * Returns true if an option was chosen, false otherwise.
     * @param array $response responses, as returned by
     *        {@link question_attempt_step::get_qt_data()}.
     *
     * @return bool whether this response is a complete answer to this question.
     */
    public function is_complete_response(array $response) {
        foreach ($this->order as $key => $rowid) {
            $optionfield = $this->optionfield($key);
            $distractorfield = $this->distractorfield($key);

            if ((array_key_exists($optionfield, $response) && $response[$optionfield] == 1)
                || (array_key_exists($distractorfield, $response) && $response[$distractorfield] == 1) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if an option was chosen or, in case of aprime and subpoints, if at least one distractor was marked.
     *
     * @see question_graded_automatically::is_gradable_response()
     */
    public function is_gradable_response(array $response) {
        if ($this->is_complete_response($response)) {
            return true;
        }

        if ($this->scoringmethod == 'aprime' || $this->scoringmethod == 'subpoints') {
            return $this->any_distractor_chosen($response);
        }
        return false;
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     *
     * @return string the message.
     */
    public function get_validation_error(array $response) {
        $isgradable = $this->is_gradable_response($response);
        if ($isgradable) {
            return '';
        }
        return get_string('oneradiobutton', 'qtype_sc');
    }

    /**
     *
     * @param array $response responses, as returned by
     *        {@link question_attempt_step::get_qt_data()}.
     * @return int the number of choices that were selected. in this response.
     */
    public function get_num_selected_choices(array $response) {
        $numselected = 0;
        foreach ($response as $key => $value) {
            // Response keys starting with _ are internal values like _order, so ignore them.
            if (!empty($value) && $key[0] != '_') {
                $numselected += 1;
            }
        }
        return $numselected;
    }

    /**
     * Produce a plain text summary of a response.
     *
     * @param $response a response, as might be passed to {@link grade_response()}.
     *
     * @return string a plain text summary of that response, that could be used in reports.
     */
    public function summarise_response(array $response) {
        $result = array();

        foreach ($this->order as $key => $rowid) {
            $optionfield = $this->optionfield($key);

            if (array_key_exists($optionfield, $response) && $response[$optionfield]) {
                $row = $this->rows[$rowid];
                $result[] = $this->html_to_text($row->optiontext, $row->optiontextformat);
            }
        }
        foreach ($this->order as $key => $rowid) {
            $field = $this->distractorfield($key);
            if (array_key_exists($field, $response) && $response[$field]) {
                $row = $this->rows[$rowid];
                $result[] = $this->html_to_text($row->optiontext, $row->optiontextformat) . ' ' .
                    get_string('iscrossedout', 'qtype_sc');
            }
        }

        return implode('; ', $result);
    }

    /**
     * Returns true if at least one distractor was marked in a response.
     * @param array $response
     * @return bool
     */
    public function any_distractor_chosen(array $response) {
        foreach ($this->order as $key => $rowid) {
            $field = $this->distractorfield($key);
            if (array_key_exists($field, $response) && $response[$field] == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * (non-PHPdoc).
     *
     * @see question_with_responses::classify_response()
     */
    public function classify_response(array $response) {
        list($partialcredit, $state) = $this->grade_response($response);
        $parts = array();
        foreach ($this->order as $key => $rowid) {
            $optionfield = $this->optionfield($key);
            if (array_key_exists($optionfield, $response) && ($response[$optionfield] == 1)) {
                $row = $this->rows[$rowid];
                if ($row->number == $this->correctrow) {
                    $partialcredit = 1.0;
                } else {
                    $partialcredit = 0; // Due to non-linear math.
                }
                $parts[$rowid] = new question_classified_response($rowid . '1',
                    question_utils::to_plain_text($row->optiontext, $row->optiontextformat), $partialcredit);
            }
        }
        return $parts;
    }

    /**
     * Use by many of the behaviours to determine whether the student's
     * response has changed.
     * This is normally used to determine that a new set
     * of responses can safely be discarded.
     *
     * @param array $prevresponse the responses previously recorded for this question,
     *        as returned by {@link question_attempt_step::get_qt_data()}
     * @param array $newresponse the new responses, in the same format.
     *
     * @return bool whether the two sets of responses are the same - that is
     *         whether the new set of responses can safely be discarded.
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        foreach ($this->order as $key => $rowid) {
            $optionfield = $this->optionfield($key);
            if (!question_utils::arrays_same_at_key($prevresponse, $newresponse, $optionfield)) {
                return false;
            }
            $distractorfield = $this->distractorfield($key);
            if (!question_utils::arrays_same_at_key($prevresponse, $newresponse, $distractorfield)) {
                return false;
            }
        }

        return true;
    }

    /**
     * What data would need to be submitted to get this question correct.
     * If there is more than one correct answer, this method should just
     * return one possibility.
     *
     * @return array parameter name => value.
     */
    public function get_correct_response() {
        $result = array();

        foreach ($this->order as $key => $rowid) {
            $optionfield = $this->optionfield($key);
            $row = $this->rows[$rowid];

            if ($row->number == $this->correctrow) {
                $result[$optionfield] = 1;
            } else {
                $result[$optionfield] = 0;
            }
        }

        return $result;
    }

    /**
     * Returns an instance of the grading class according to the scoringmethod of the question.
     *
     * @return The grading object.
     */
    public function grading() {
        global $CFG;

        $type = $this->scoringmethod;
        $gradingclass = 'qtype_sc_grading_' . $type;

        require_once($CFG->dirroot . '/question/type/sc/grading/' . $gradingclass . '.class.php');

        return new $gradingclass();
    }

    /**
     * Grade a response to the question, returning a fraction between
     * get_min_fraction() and 1.0, and the corresponding {@link question_state}
     * right, partial or wrong.
     *
     * @param array $response responses, as returned by
     *        {@link question_attempt_step::get_qt_data()}.
     *
     * @return array (number, integer) the fraction, and the state.
     */
    public function grade_response(array $response) {
        $grade = $this->grading()->grade_question($this, $response);
        $state = question_state::graded_state_for_fraction($grade);

        return array($grade, $state);
    }

    /**
     * What data may be included in the form submission when a student submits
     * this question in its current state?
     *
     * This information is used in calls to optional_param. The parameter name
     * has {@link question_attempt::get_field_prefix()} automatically prepended.
     *
     * @return array|string variable name => PARAM_... constant, or, as a special case
     *         that should only be used in unavoidable, the constant question_attempt::USE_RAW_DATA
     *         meaning take all the raw submitted data belonging to this question.
     */
    public function get_expected_data() {
        $result = array();

        // Add the fields for distractors.
        foreach ($this->order as $key => $notused) {
            // Add the field 'option'.
            $optionfield = $this->optionfield($key);
            $result[$optionfield] = PARAM_INT;

            $distractorfield = $this->distractorfield($key);
            $result[$distractorfield] = PARAM_INT;
        }

        return $result;
    }

    /**
     * Makes HTML text (e.g. option or feedback texts) suitable for inline presentation in renderer.php.
     *
     * @param string html The HTML code.
     *
     * @return string the purified HTML code without paragraph elements and line breaks.
     */
    public function make_html_inline($html) {
        $html = preg_replace('~\s*<p>\s*~u', '', $html);
        $html = preg_replace('~\s*</p>\s*~u', '<br />', $html);
        $html = preg_replace('~(<br\s*/?>)+$~u', '', $html);

        return trim($html);
    }

    /**
     * Convert some part of the question text to plain text.
     * This might be used,
     * for example, by get_response_summary().
     *
     * @param string $text The HTML to reduce to plain text.
     * @param int $format the FORMAT_... constant.
     *
     * @return string the equivalent plain text.
     */
    public function html_to_text($text, $format) {
        return question_utils::to_plain_text($text, $format);
    }

    /**
     * Computes the final grade when "Multiple Attempts" or "Hints" are enabled
     *
     * @param array $responses Contains the user responses. 1st dimension = attempt, 2nd dimension = answers
     * @param int $totaltries Not needed
     */
    public function compute_final_grade($responses, $totaltries) {
        $last_response = sizeOf($responses) - 1;
        $num_points = isset($responses[$last_response]) ? $this->grading()->grade_question($this, $responses[$last_response]) : 0;
        return max(0, $num_points - max(0, $last_response) * $this->penalty);
    }

    /**
     * Disable those hint settings that we don't want when the student has selected
     * more choices than the number of right choices.
     * This avoids giving the game away.
     *
     * @param question_hint_with_parts $hint a hint.
     */
    protected function disable_hint_settings_when_too_many_selected(question_hint_with_parts $hint) {
        $hint->clearwrong = false;
    }

    public function get_hint($hintnumber, question_attempt $qa) {
        $hint = parent::get_hint($hintnumber, $qa);
        if (is_null($hint)) {
            return $hint;
        }
        
        if ($this->get_num_selected_choices($qa->get_last_qt_data()) > 1) {
            $hint = clone ($hint);
            $this->disable_hint_settings_when_too_many_selected($hint);
        }
        return $hint;
    }

    /**
     * (non-PHPdoc).
     *
     * @see question_definition::check_file_access()
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'qtype_sc' && $filearea == 'optiontext') {
            return true;
        } else if ($component == 'qtype_sc' && $filearea == 'feedbacktext') {
            return true;
        } else if ($component == 'question' && in_array($filearea,
                array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'
                ))) {
            if ($this->editedquestion == 1) {
                return true;
            } else {
                return $this->check_combined_feedback_file_access($qa, $options, $filearea);
            }
        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args,
                    $forcedownload);
        }
    }
}
