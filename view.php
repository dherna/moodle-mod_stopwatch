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
 * Prints a particular instance of stopwatch
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_stopwatch
 * @copyright  2015 2015 Iñaki Villanueva <inakivillanueva@hotmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace stopwatch with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... stopwatch instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('stopwatch', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $stopwatch  = $DB->get_record('stopwatch', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $stopwatch  = $DB->get_record('stopwatch', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $stopwatch->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('stopwatch', $stopwatch->id, $course->id, false, MUST_EXIST);
} else {
    error('Debe especificar un ID course_module o un ID de instancia');
}

require_login($course, true, $cm);

$event = \mod_stopwatch\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $stopwatch);
$event->trigger();



// Print the page header.

$PAGE->set_url('/mod/stopwatch/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($stopwatch->name));
$PAGE->set_heading(format_string($course->fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('stopwatch-'.$somevar);
 */


// Output starts here.
echo $OUTPUT->header();

if (isset($_POST["submitbutton"]) && !empty($_POST["submitbutton"])) {

	$stopwatch->swactivityfromid = $cm->id;
	$stopwatch->swuserid = $USER->id;
	$stopwatch->swtimestart = $_POST['starttime'];
	$stopwatch->swtimefinish = $_POST['endtime'];
	$stopwatch->timecreated = time();
	$stopwatch->timemodified = time();
	$stopwatch->id = $DB->insert_record('stopwatch', $stopwatch);
	stopwatch_grade_item_update($stopwatch);
	
	if (!$PAGE->user_is_editing()) { // si no se está en modo edición del Curso
		echo $OUTPUT->heading("El tiempo de lectura ha sido guardado y pasamos a la prueba");
		$url = $CFG->httpswwwroot . "/mod/quiz/view.php?id=".$stopwatch->swactivitytoid;
		echo $OUTPUT->heading('<div class="continuebutton">(<a href="'.$url.'">Continuar</a>)</div>');
	}
	else echo $OUTPUT->heading("Pasamos a la prueba de lectura eficaz si no se está en modo edición del curso! Por favor desactiva el modo edición");
	
}
else {

	if ($stopwatch->intro) {
		echo $OUTPUT->box(format_module_intro('stopwatch', $stopwatch, $cm->id), 'generalbox mod_introbox', 'stopwatchintro');
	}
	
	echo $OUTPUT->heading('
	    <script>
	    var timein=0;
	    var timeout=0; // tiempo en segundos
	    var endtime=0; // 
	
	 
	    function startStop(element) {
	        if(timeout==0)         {
	            // empezar el cronometro
	             element.value="Detener";
	 
	            // Obtenemos el time inicial
	            timein=new Date().getTime();
		   		document.getElementById("swtimestart").value = timein;
	 
	            // Guardamos el valor inicial en la base de datos del navegador
	            localStorage.setItem("timein",timein);
	 
	            // iniciamos el proceso
	            cronometer();
	        }
		else{
	   		// obtener time final
	           	endtime= new Date().getTime();
			document.getElementById("swtimefinish").value = endtime;
	
		    	//alert(starttime+" "+endtime+" "+timein+" = "+timeout+" segundos");
	 
	            // detener el cronometro
	            element.value="Empezar";
	            clearTimeout(timeout);
	
	
	            // Eliminamos el valor inicial guardado
	            localStorage.removeItem("timein");
	            timeout=0;
			
	   	    document.getElementById("enviar").innerHTML = "<input name=\"submitbutton\" value=\"Pasar a la prueba\" id=\"id_submitbutton\" type=\"submit\">";
		    document.getElementById("boton").disabled = true; 
	        }
	    }
	 
	    function cronometer()     {
	        // obtener la fecha actual
	        var now = new Date().getTime();
	 
	        // obtenemos la diferencia entre la fecha actual y la de inicio
	        var diff=new Date(now-timein);
	 
	        // mostramos la diferencia entre la fecha actual y la inicial
	        var result=LeadingZero(diff.getUTCHours())+":"+LeadingZero(diff.getUTCMinutes())+":"+LeadingZero(diff.getUTCSeconds());
	        document.getElementById("crono").innerHTML = result;
	 
	        // Indicamos que se ejecute esta función nuevamente dentro de 1 segundo
	        timeout=setTimeout("cronometer()",1000);
	    }
	 
	    /* Funcion que pone un 0 delante de un valor si es necesario */
	    function LeadingZero(Time) {
	        return (Time < 10) ? "0" + Time : + Time;
	    }
	 
	    window.onload=function() {
	        if(localStorage.getItem("timein")!=null) {
	            // Si al iniciar el navegador, la variable timein que se guarda en la base de datos del navegador tiene valor, cargamos el valor // y iniciamos el proceso.
	            timein=localStorage.getItem("timein");
	            document.getElementById("boton").value="Detener";
	            cronometer();
	        }
	    }
	    </script>
	 
	    <style>
	    .crono_wrapper {text-align:center;width:200px;}
	    </style>
	
	 
		<form id="cronoform" name="cronoform" method="post">
		<input type="hidden" id="swtimestart" name="starttime" value="">	
		<input type="hidden" id="swtimefinish" name="endtime" value="">	
		<input type="hidden" type="text" id="id" name="id" value="'.$cm->id.'">
		<input type="hidden" type="text" id="n" name="n" value="'.$stopwatch->id.'">
					
		
		<div class="crono_wrapper">
		    <h2 id="crono">00:00:00</h2>
		    <input type="button" value="Empezar" id="boton" onclick="startStop(this);">
		</div>
		<div class="enviar_wrapper">
		    <h2 id="enviar"></h2>
		</div>
		</form>	
		');


	// Replace the following lines with you own code.
	//echo $OUTPUT->heading('Al terminar la lectura se accede a la prueba de lectura eficaz.');

}

// Finish the page.
echo $OUTPUT->footer();




