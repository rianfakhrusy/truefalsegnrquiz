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
 * Question type class for the true-false question type.
 *
 * @package    qtype
 * @subpackage truefalsegnrquiz
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');


/**
 * The true-false question type class.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_truefalsegnrquiz extends question_type {
    public function save_question_options($question) {
        global $DB;
        $result = new stdClass();
        $context = $question->context;

        // Fetch old answer ids so that we can reuse them.
        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // Save the true answer - update an existing answer if possible.
        $answer = array_shift($oldanswers);
        if (!$answer) {
            $answer = new stdClass();
            $answer->question = $question->id;
            $answer->answer = '';
            $answer->feedback = '';
            $answer->id = $DB->insert_record('question_answers', $answer);
        }

        $answer->question = $question->id;
        $answer->time = $question->time;
        $answer->difficulty = $question->difficulty;
        $answer->distinguishingdegree = $question->distinguishingdegree;

        $answer->answer   = get_string('true', 'qtype_truefalsegnrquiz');
        $answer->fraction = $question->correctanswer;
        $answer->feedback = $this->import_or_save_files($question->feedbacktrue,
                $context, 'question', 'answerfeedback', $answer->id);
        $answer->feedbackformat = $question->feedbacktrue['format'];
        $DB->update_record('question_answers', $answer);
        $trueid = $answer->id;

        // Save the false answer - update an existing answer if possible.
        $answer = array_shift($oldanswers);
        if (!$answer) {
            $answer = new stdClass();
            $answer->answer = '';
            $answer->feedback = '';
            $answer->id = $DB->insert_record('question_answers', $answer);
        }

        $answer->question = $question->id;
        $answer->time = $question->time;
        $answer->difficulty = $question->difficulty;
        $answer->distinguishingdegree = $question->distinguishingdegree;

        $answer->answer   = get_string('false', 'qtype_truefalsegnrquiz');
        $answer->fraction = 1 - (int)$question->correctanswer;
        $answer->feedback = $this->import_or_save_files($question->feedbackfalse,
                $context, 'question', 'answerfeedback', $answer->id);
        $answer->feedbackformat = $question->feedbackfalse['format'];
        $DB->update_record('question_answers', $answer);
        $falseid = $answer->id;

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        // Save question options in question_truefalsegnrquiz table.
        if ($options = $DB->get_record('question_truefalsegnrquiz', array('question' => $question->id))) {
            // No need to do anything, since the answer IDs won't have changed
            // But we'll do it anyway, just for robustness.
            $options->trueanswer  = $trueid;
            $options->falseanswer = $falseid;

            $options->time = $question->time;
            $options->difficulty = $question->difficulty;
            $options->distinguishingdegree = $question->distinguishingdegree;

            $DB->update_record('question_truefalsegnrquiz', $options);
        } else {
            $options = new stdClass();
            $options->question    = $question->id;
            $options->trueanswer  = $trueid;
            $options->falseanswer = $falseid;

            $options->time = $question->time;
            $options->difficulty = $question->difficulty;
            $options->distinguishingdegree = $question->distinguishingdegree;

            $DB->insert_record('question_truefalsegnrquiz', $options);
        }


        $this->save_hints($question);

        return true;
    }

    /**
     * Loads the question type specific options for the question.
     */
    public function get_question_options($question) {
        global $DB, $OUTPUT;
        // Get additional information from database
        // and attach it to the question object.
        if (!$question->options = $DB->get_record('question_truefalsegnrquiz',
                array('question' => $question->id))) {
            echo $OUTPUT->notification('Error: Missing question options!');
            return false;
        }
        // Load the answers.
        if (!$question->options->answers = $DB->get_records('question_answers',
                array('question' =>  $question->id), 'id ASC')) {
            echo $OUTPUT->notification('Error: Missing question answers for truefalsegnrquiz question ' .
                    $question->id . '!');
            return false;
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $answers = $questiondata->options->answers;
        if ($answers[$questiondata->options->trueanswer]->fraction > 0.99) {
            $question->rightanswer = true;
        } else {
            $question->rightanswer = false;
        }
        $question->truefeedback =  $answers[$questiondata->options->trueanswer]->feedback;
        $question->falsefeedback = $answers[$questiondata->options->falseanswer]->feedback;
        $question->truefeedbackformat =
                $answers[$questiondata->options->trueanswer]->feedbackformat;
        $question->falsefeedbackformat =
                $answers[$questiondata->options->falseanswer]->feedbackformat;
        $question->trueanswerid =  $questiondata->options->trueanswer;
        $question->falseanswerid = $questiondata->options->falseanswer;

        $question->time = $questiondata->options->time;
        $question->difficulty = $questiondata->options->difficulty;
        $question->distinguishingdegree = $questiondata->options->distinguishingdegree;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question_truefalsegnrquiz', array('question' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        return 0.5;
    }

    public function get_possible_responses($questiondata) {
        return array(
            $questiondata->id => array(
                0 => new question_possible_response(get_string('false', 'qtype_truefalsegnrquiz'),
                        $questiondata->options->answers[
                        $questiondata->options->falseanswer]->fraction),
                1 => new question_possible_response(get_string('true', 'qtype_truefalsegnrquiz'),
                        $questiondata->options->answers[
                        $questiondata->options->trueanswer]->fraction),
                null => question_possible_response::no_response()
            )
        );
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array where the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    public function extra_question_field() {
        return array('mdl_question_truefalsegnrquiz', //table name
            'time', 
            'difficulty',
            'distinguishingdegree'
        );
    }

    // IMPORT/EXPORT FUNCTIONS --------------------------------- .

    /*
     * Imports question from the Moodle XML format
     *
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    
    public function export_to_xml($question, qformat_xml $format, $extra=null) {
        $extraquestionfields = $this->extra_question_field();

        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $expout='';
        foreach ($extraquestionfields as $field) {
            $exportedvalue = $format->xml_escape($question->options->$field);
            $expout .= "    <{$field}>{$exportedvalue}</{$field}>\n";
        }

        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }
        foreach ($question->options->answers as $answer) {
            $extra = '';
            if (is_array($extraanswersfields)) {
                foreach ($extraanswersfields as $field) {
                    $exportedvalue = $format->xml_escape($answer->$field);
                    $extra .= "      <{$field}>{$exportedvalue}</{$field}>\n";
                }
            }

            $expout .= $format->write_answer($answer, $extra);
        }
        return $expout;
    }

    /*
    * Imports question from the Moodle XML format
    *
    * Imports question using information from extra_question_fields function
    * If some of you fields contains id's you'll need to reimplement this
    */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        $question_type = $data['@']['type'];
        if ($question_type != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_field();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        // Omit table name.
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $question_type;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#', $field, 0, '#'), '');
        }

        // Run through the answers.
        $answers = $data['#']['answer'];
        $a_count = 0;
        $extraanswersfields = $this->extra_answer_fields();
        if (is_array($extraanswersfields)) {
            array_shift($extraanswersfields);
        }
        $first = true;
        foreach ($answers as $answer) {
            $answertext = $format->getpath($answer,
                    array('#', 'text', 0, '#'), '', true);
            $feedback = $format->import_text_with_files($answer,
                    array('#', 'feedback', 0), '', $format->get_format($qo->questiontextformat));

            if ($answertext != 'True' && $answertext != 'False') {
                // Old style file, assume order is true/false.
                if ($first) {
                    $answertext = 'True';
                } else {
                    $answertext = 'False';
                }
            }

            if ($answertext == 'True') {
                $qo->answer = ($answer['@']['fraction'] == 100);
                $qo->correctanswer = $qo->answer;
                $qo->feedbacktrue = $feedback;
            } else {
                $qo->answer = ($answer['@']['fraction'] != 100);
                $qo->correctanswer = $qo->answer;
                $qo->feedbackfalse = $feedback;
            }
            $first = false;
        }

        return $qo;
    }
}
