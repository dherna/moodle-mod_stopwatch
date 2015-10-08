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
 * The main stopwatch configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_stopwatch
 * @copyright  2015 Iñaki Villanueva <inakivillanueva@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_stopwatch
 * @copyright  2015 Iñaki Villanueva <inakivillanueva@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_stopwatch_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
    	
       	global $DB, $COURSE; 
       	
       	$cm = $this->_cm;
  	
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('stopwatchname', 'stopwatch'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'stopwatchname', 'stopwatch');

        // Adding the standard "intro" and "introformat" fields.
	//        $this->add_intro_editor();
	$this->standard_intro_elements();
        // Adding the rest of stopwatch settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        //$mform->addElement('static', 'label2', 'Descripción', 'Configuración actividad');

        $mform->addElement('header', 'stopwatchfieldset', get_string('stopwatchfieldset', 'stopwatch'));
		
        // actividad condicional 

        $sql = "SELECT cm.id AS activityid, cm.course, cm.instance, cm.section, q.name AS activityname
		FROM mdl_course_modules cm JOIN 
     		mdl_modules m ON cm.module = m.id JOIN
     		mdl_quiz q ON q.course = cm.course AND q.id = cm.instance
		WHERE cm.course =".$COURSE->id;

	$records = $DB->get_records_sql($sql);
        
	$attributes='size="1"';
	$options = array('0' => 'Seleccionar');
	$select = $mform->addElement('select', 'swactivitytoid', get_string('stopwatchactivityid', 'stopwatch'),  $options, $attributes);
	foreach($records as $record) {
			$select->addOption($record->activityname, $record->activityid );
	}
	$select->setMultiple(false);


        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
    
    
}
