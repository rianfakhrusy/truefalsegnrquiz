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
 * Defines the editing form for the true-false question type.
 *
 * @package    qtype
 * @subpackage truefalsegnrquiz
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/question/type/edit_question_form.php');


/**
 * True-false question editing form definition.
 *
 * @copyright  2007 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_truefalsegnrquiz_edit_form extends question_edit_form {
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    protected function definition_inner($mform) {

        //expected answer
        $mform->addElement('select', 'correctanswer',
                get_string('correctanswer', 'qtype_truefalsegnrquiz'), array(
                0 => get_string('false', 'qtype_truefalsegnrquiz'),
                1 => get_string('true', 'qtype_truefalsegnrquiz')));

        $mform->addElement('editor', 'feedbacktrue',
                get_string('feedbacktrue', 'qtype_truefalsegnrquiz'), array('rows' => 10), $this->editoroptions);
        $mform->setType('feedbacktrue', PARAM_RAW);

        $mform->addElement('editor', 'feedbackfalse',
                get_string('feedbackfalse', 'qtype_truefalsegnrquiz'), array('rows' => 10), $this->editoroptions);
        $mform->setType('feedbackfalse', PARAM_RAW);

        //question attributes
        $mform->addElement('header', 'questionattributes', get_string('questionattributes', 'qtype_truefalsegnrquiz'));

        $mform->addElement('duration', 'time', get_string('time', 'qtype_truefalsegnrquiz'),
                array('optional' => false));
        $mform->setType('time', PARAM_INT);
        $mform->addRule('time', null, 'required', null, 'client');
        $mform->addHelpButton('time', 'time', 'qtype_truefalsegnrquiz');

        $mform->addElement('text', 'difficulty', get_string('difficulty', 'qtype_truefalsegnrquiz'),
                array('size' => 7));
        $mform->setType('difficulty', PARAM_TEXT);
        $mform->setDefault('difficulty', 0.5);
        $mform->addRule('difficulty', null, 'required', null, 'client');
        $mform->addHelpButton('difficulty', 'difficulty', 'qtype_truefalsegnrquiz');

        $mform->addElement('text', 'distinguishingdegree', get_string('distinguishingdegree', 'qtype_truefalsegnrquiz'),
                array('size' => 7));
        $mform->setType('distinguishingdegree', PARAM_TEXT);
        $mform->setDefault('distinguishingdegree', 0.5);
        $mform->addRule('distinguishingdegree', null, 'required', null, 'client');
        $mform->addHelpButton('distinguishingdegree', 'distinguishingdegree', 'qtype_truefalsegnrquiz');


        $mform->addElement('header', 'multitriesheader',
                get_string('settingsformultipletries', 'question'));

        $mform->addElement('hidden', 'penalty', 1);
        $mform->setType('penalty', PARAM_FLOAT);

        $mform->addElement('static', 'penaltymessage',
                get_string('penaltyforeachincorrecttry', 'question'), 1);
        $mform->addHelpButton('penaltymessage', 'penaltyforeachincorrecttry', 'question');
    }

    public function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (!empty($question->options->trueanswer)) {
            $trueanswer = $question->options->answers[$question->options->trueanswer];
            $question->correctanswer = ($trueanswer->fraction != 0);

            $draftid = file_get_submitted_draft_itemid('trueanswer');
            $answerid = $question->options->trueanswer;

            $question->feedbacktrue = array();
            $question->feedbacktrue['format'] = $trueanswer->feedbackformat;
            $question->feedbacktrue['text'] = file_prepare_draft_area(
                $draftid,             // Draftid
                $this->context->id,   // context
                'question',           // component
                'answerfeedback',     // filarea
                !empty($answerid) ? (int) $answerid : null, // itemid
                $this->fileoptions,   // options
                $trueanswer->feedback // text.
            );
            $question->feedbacktrue['itemid'] = $draftid;

            $question->time = $question->options->time;
            $question->difficulty = $question->options->difficulty;
            $question->distinguishingdegree = $question->options->distinguishingdegree;
        }


        if (!empty($question->options->falseanswer)) {
            $falseanswer = $question->options->answers[$question->options->falseanswer];

            $draftid = file_get_submitted_draft_itemid('falseanswer');
            $answerid = $question->options->falseanswer;

            $question->feedbackfalse = array();
            $question->feedbackfalse['format'] = $falseanswer->feedbackformat;
            $question->feedbackfalse['text'] = file_prepare_draft_area(
                $draftid,              // Draftid
                $this->context->id,    // context
                'question',            // component
                'answerfeedback',      // filarea
                !empty($answerid) ? (int) $answerid : null, // itemid
                $this->fileoptions,    // options
                $falseanswer->feedback // text.
            );
            $question->feedbackfalse['itemid'] = $draftid;

            $question->time = $question->options->time;
            $question->difficulty = $question->options->difficulty;
            $question->distinguishingdegree = $question->options->distinguishingdegree;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        if (($data['time']<=0)||(!is_numeric($data['time']))) {
            $errors['time'] = get_string('morethanzero', 'qtype_truefalsegnrquiz');
        }
        if (($data['difficulty']<0.0)||($data['difficulty']>1.0)||(!is_numeric($data['difficulty']))) {
            $errors['difficulty'] = get_string('betweenzeroandone', 'qtype_truefalsegnrquiz');
        }
        if (($data['distinguishingdegree']<0.0)||($data['distinguishingdegree']>1.0)||(!is_numeric($data['distinguishingdegree']))) {
            $errors['distinguishingdegree'] = get_string('betweenzeroandone', 'qtype_truefalsegnrquiz');
        }
        return $errors;
    }

    public function qtype() {
        return 'truefalsegnrquiz';
    }
}
