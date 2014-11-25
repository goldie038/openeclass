<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

$require_current_course = true;
require_once '../../include/baseTheme.php';
require_once 'work_functions.php';
require_once 'modules/group/group_functions.php';


function show_assignments() {
    global $tool_content, $m, $langEdit, $langDelete, $langNoAssign, $langNewAssign, $langCommands,
    $course_code, $themeimg, $course_id, $langConfirmDelete, $langDaysLeft, $m,
    $langWarnForSubmissions, $langDelSure;
    

    $result = Database::get()->queryArray("SELECT *, CAST(UNIX_TIMESTAMP(deadline)-UNIX_TIMESTAMP(NOW()) AS SIGNED) AS time
              FROM assignment WHERE course_id = ?d ORDER BY CASE WHEN CAST(deadline AS UNSIGNED) = '0' THEN 1 ELSE 0 END, deadline", $course_id);
 $tool_content .= action_bar(array(
            array('title' => $langNewAssign,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;add=1",
                  'button-class' => 'btn-success',
                  'icon' => 'fa-plus-circle',
                  'level' => 'primary-label')  
            ));

    if (count($result)>0) {
        $tool_content .= "
                    <div class='table-responsive'>
                    <table class='table-default'>
                    <tr>
                      <th>$m[title]</th>
                      <th class='text-center'>$m[subm]</th>
                      <th class='text-center'>$m[nogr]</th>
                      <th class='text-center'>$m[deadline]</th>
                      <th class='text-center'>".icon('fa-gears')."</th>
                    </tr>";
        $index = 0;
        foreach ($result as $row) {
            // Check if assignement contains submissions
            $num_submitted = Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit WHERE assignment_id = ?d", $row->id)->count;
            if (!$num_submitted) {
                $num_submitted = '&nbsp;';
            }
                    
            $num_ungraded = Database::get()->querySingle("SELECT COUNT(*) AS count FROM assignment_submit WHERE assignment_id = ?d AND grade IS NULL", $row->id)->count;            
            if (!$num_ungraded) {
                $num_ungraded = '&nbsp;';
            }
            
            $tool_content .= "\n<tr class='".(!$row->active ? "not_visible":"")."'>";
            $deadline = (int)$row->deadline ? nice_format($row->deadline, true) : $m['no_deadline'];
            $tool_content .= "<td>
                                <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id={$row->id}'>$row->title</a>
                            </td>
                            <td class='text-center'>$num_submitted</td>
                            <td class='text-center'>$num_ungraded</td>
                            <td class='text-center'>$deadline"; 
            if ($row->time > 0) {
                $tool_content .= " <br><span class='label label-warning'>$langDaysLeft" . format_time_duration($row->time) . "</span>";
            } else if((int)$row->deadline){
                $tool_content .= " <br><span class='label label-danger'>$m[expired]</span>";
            }                         
           $tool_content .= "</td>
              <td class='option-btn-cell'>" .
              action_button(array(
                    array('title' => $langEdit,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=edit",
                          'icon' => 'fa-edit'),
                    array('title' => $m['WorkSubsDelete'],
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=do_purge",
                          'icon' => 'fa-eraser',
                          'confirm' => $langWarnForSubmissions. $langDelSure,
                          'show' => is_numeric($num_submitted) && $num_submitted > 0),
                    array('title' => $langDelete,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$row->id&amp;choice=do_delete",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmDelete),
                    array('title' => $row->active == 1 ? $m['deactivate']: $m['activate'],
                          'url' => $row->active == 1 ? "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=disable&amp;id=$row->id" : "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=enable&amp;id=$row->id",
                          'icon' => $row->active == 1 ? 'fa-eye': 'fa-eye-slash'))).
                   "</td></tr>";
            $index++;
        }
        $tool_content .= '</table></div>';
    } else {
        $tool_content .= "\n<div class='alert alert-warning'>$langNoAssign</div>";        
    }
}

function show_report($id, $sid, $assign,$sub, $auto_judge_scenarios, $auto_judge_scenarios_output) {
         global $course_code,$tool_content;
               $tool_content = "
                                <table  style=\"table-layout: fixed; width: 99%\" class='table-default'>
                                <tr> <td> <b>Αποτελέσματα για</b>: ".  q(uid_to_name($sub->uid))."</td> </tr>
                                <tr> <td> <b>Βαθμός</b>: $sub->grade /$assign->max_grade </td>
                                     <td><b> Κατάταξη</b>: - </td>
                                </tr>
                                  <tr> <td> <b>Είσοδος</b> </td>
                                       <td> <b>Έξοδος</b> </td>
                                       <td> <b>Αναμενόμενη έξοδος</b> </td>
                                       <td> <b>Αποτέλεσμα</b> </td>
                                </tr>
                                ".get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output)."
                                </table>
                                <p align='left'><a href='/openeclass/modules/work/rank_report.php?course=".$course_code."&assignment=".$assign->id."&submission=".$sid."&downloadpdf=1'>Λήψη σε μορφή PDF</a></p>
                                <p align='right'><a href='/openeclass/modules/work/index.php?course=".$course_code."'>Επιστροφή</a></p>
                             <br>";
  }

function get_table_content($auto_judge_scenarios, $auto_judge_scenarios_output) {
    global $themeimg;
    $table_content = "";
    $i=0;
    foreach($auto_judge_scenarios as $cur_senarios){
                     $icon = ($auto_judge_scenarios_output[$i]['passed']==1) ? 'tick.png' : 'delete.png';
                     $table_content.="
                                      <tr>
                                      <td style=\"word-break:break-all;\">".$cur_senarios['input']."</td>
                                      <td style=\"word-break:break-all;\">".$auto_judge_scenarios_output[$i]['student_output']."</td>
                                      <td style=\"word-break:break-all;\">".$cur_senarios['output']."</td>
                                      <td align=\"center\"><img src=\"http://".$_SERVER['HTTP_HOST'].$themeimg."/" .$icon."\"></td></tr>";
                     $i++;
                }
    return $table_content;
  }

printf("hello world");
show_assignments();