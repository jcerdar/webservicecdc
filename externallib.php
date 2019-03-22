<?php

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
 * External Web Service
 *
 * @package    local
 * @copyright  2017 Mihail Pozarski <mihailpozarski@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_webservice_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function webservice_surveycheck_parameters() {
        return new external_function_parameters(
                array(
                	'initialdate' => new external_value(PARAM_INT, 'the initial date from where you want to get the attendance', VALUE_DEFAULT, 0),
                	'enddate' => new external_value(PARAM_INT, 'the last day from where you want to get the attendance', VALUE_DEFAULT, 0),
                )
        );
    }

    /**
     * Returns presence of paperattendance
     * @return json presence of paperattendance 
     */
    public static function webservice_surveycheck($initialdate = 0, $enddate = 0) {
        global $DB;
        
        //Parameter validation
        $params = self::validate_parameters(self::webservice_surveycheck_parameters(),
            array('initialdate' => $initialdate, 'enddate' => $enddate));
        $query = 'SELECT q.id as questionnaireid, c.id as courseid, q.opendate, q.closedate FROM {course} AS c
                        INNER JOIN {course_modules} AS cm ON (c.id = cm.course)
                        INNER JOIN {modules} AS m ON (cm.module = m.id AND m.name = ?)
                        INNER JOIN {questionnaire} AS q ON (c.id = q.course AND q.closedate < ?)
                        INNER JOIN {questionnaire_response} AS qr ON (q.id = qr.questionnaireid)
                        WHERE q.intro like "<ul>%" AND c.category != 39 group by q.id';
        $parameters = array(
            "questionnaire",
            time(),
        );
        
        $questionnaires = $DB->get_records_sql($query, $parameters);
        $return = array();
        $prefix = '';
        echo '[';
        foreach($questionnaires as $quetionnaire){
            $textresponses = $DB->get_records_sql('SELECT qrt.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position, q.intro as info, qq.name as sectioncategory, qq.content as question, qrt.response as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_response_text} AS qrt ON (qrt.question_id = qq.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $rankresponses = $DB->get_records_sql('SELECT qrr.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qqc.content as question, qrr.rankvalue+1 as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                                INNER JOIN {questionnaire_response_rank} AS qrr ON (qrr.choice_id = qqc.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $dateresponses = $DB->get_records_sql('SELECT qrd.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qrd.response as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_response_date} AS qrd ON (qrd.question_id = qq.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $boolresponses = $DB->get_records_sql('SELECT qrd.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qrd.choice_id as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_response_bool} AS qrd ON (qrd.question_id = qq.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $singleresponses = $DB->get_records_sql('SELECT qrs.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qqc.content as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                                INNER JOIN {questionnaire_resp_single} AS qrs ON (qrs.choice_id = qqc.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $multiresponses = $DB->get_records_sql('SELECT qrm.id as id, cc.name as category, c.fullname as coursename, q.name as questionnaire, qqt.response_table, qq.length, qq.position,q.intro as info, qq.name as sectioncategory, qq.content as question, qqc.content as response FROM {questionnaire} AS q
                                                                INNER JOIN {course} AS c ON (c.id = q.course AND c.id = ? AND q.id = ?)
                                                                INNER JOIN {course_categories} AS cc ON (cc.id = c.category)
                                                                INNER JOIN {questionnaire_question} AS qq ON (qq.surveyid = q.id)
                                                                INNER JOIN {questionnaire_quest_choice} AS qqc ON (qqc.question_id = qq.id)
                                                                INNER JOIN {questionnaire_resp_multiple} AS qrm ON (qrm.choice_id = qqc.id)
                                                                INNER JOIN {questionnaire_question_type} AS qqt ON (qqt.typeid = qq.type_id)
                                                                WHERE q.intro like "<ul>%" AND cc.id != 39', array($quetionnaire->courseid,$quetionnaire->questionnaireid));
            $result = array_merge($textresponses,$rankresponses,$dateresponses,$boolresponses,$singleresponses,$multiresponses);
            foreach($result as $position => $response){
                $result[$position]->question = strip_tags($response->question);
                if($response->response_table === 'response_rank'){
                    $explode = explode(")", $response->question);
                    unset($explode[0]);
                    $response->question = ltrim(implode(")", $explode));
                }
                $explode = explode("</li>",$response->info);
                foreach($explode as $key => $item){
                    $explode[$key] = strip_tags($item);
                }
                foreach($explode as $key => $exploded){
                    $info = explode(":",$exploded);
                    $explode[$key] = $info[1];
                    
                }
                $obj = new stdClass();
                $obj->fecha = $explode[5];
                $obj->fechaapertura = date("d-m-Y",$quetionnaire->opendate);
                $obj->fechacierre = date("d-m-Y",$quetionnaire->closedate);
                $obj->category = $response->category;
                $obj->coursename = $response->coursename;
                $obj->questionnaire = $response->questionnaire;
                $obj->sectioncategory = $response->sectioncategory;
                $obj->question = $response->question;
                if($response->response_table === 'response_rank'){
                    $obj->responseint = $response->response;
                    $obj->responsetext = '';
                }else{
                    $obj->responseint = '';
                    $obj->responsetext = $response->response;
                    
                }
                $obj->programa = $explode[0];
                $obj->cliente = $explode[1];
                $obj->actividad = $explode[2];
                if(strlen($explode[4]) > 5){
                    if($response->position == 6 || $response->position == 7 || $response->question === "El Profesor/Facilitador 2"){
                        $obj->profesor = $explode[4];
                    }else{
                        $obj->profesor = $explode[3];
                    }
                }else{
                    $obj->profesor = $explode[3];
                }
                $obj->grupo = $explode[6];
                $obj->coordinadora = $explode[7];
                $obj->position = $response->position;
                if($response->position != $oldposition){
                    $count = 1;
                }
                $obj->ordenpregunta = $count;
                if($response->length == 4){
                    $obj->tiporid = 1;
                    $obj->tiporp = '1 a 4 con N/A';
                    if($response->response == 0){
                        $obj->idvalortipo = 5;
                    }else{
                        $obj->idvalortipo = $response->response;
                    }
                }elseif($response->length == 7){
                    $obj->tiporid = 2;
                    $obj->tiporp = '1 a 7 con N/A';
                    if($response->response == 0){
                        $obj->idvalortipo = 13;
                    }else{
                        $obj->idvalortipo = $response->response + 5;
                    }
                }else{
                    $obj->tiporid = 3;
                    $obj->tiporp = 'Texto Comentario';
                    $obj->idvalortipo = 0;
                }
                
                unset($obj->info);
                echo $prefix, json_encode( $obj);
                $prefix = ',';
                $oldposition = $response->position;
            }
        }
        echo ']';
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function webservice_surveycheck_returns() {
        return new external_value(PARAM_TEXT, 'json encoded array that returns, courses and its surveys with the last time the survey was changed');
    }



}
