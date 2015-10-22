<?php
/* ========================================================================
 * Open eClass 2.8
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2011  Greek Universities Network - GUnet
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



/*
===========================================================================
    admin/summarizeLogins.php
    @last update: 23-09-2006
    @authors list: ophelia neofytou
==============================================================================
    @Description:  Summarize data from table 'loginout' per month and add to table
     'loginout_summary'. Data that have been summarized and moved to 'loginout_summary'
     are deleted from 'loginout'.
==============================================================================
*/

    $stop_stmp = time() - (14-1) * 30 * 24 * 3600; // for last 14 months
    $stop_month = date('Y-m-01 00:00:00', $stop_stmp);

    $sql_0 = "SELECT min(`when`) as min_date, max(`when`) as max_date FROM loginout";

    $result = db_query($sql_0, $mysqlMainDb);
    while ($row = mysql_fetch_assoc($result)) {
        $min_date = $row['min_date'];
        $max_date = $row['max_date'];
    }
    mysql_free_result($result);


    $minstmp = strtotime($min_date);
    $maxstmp = strtotime($max_date);


    if ( $minstmp + (14-1) *30*24*3600 < $maxstmp ) { //data more than 14 months old
	$stmp = strtotime($min_date);
        $end_stmp = $stmp + 31*24*60*60;  //min time + 1 month
        $start_date = $min_date;
        $end_date = date('Y-m-01 00:00:00', $end_stmp);


        while ($end_date < $stop_month){
                $sql_1 = "SELECT count(idLog) as visits FROM loginout ".
                    " WHERE `when` >= '$start_date' AND `when` < '$end_date' AND action='LOGIN'";

                $result_1 = db_query($sql_1, $mysqlMainDb);
                while ($row1 = mysql_fetch_assoc($result_1)) {
                    $visits = $row1['visits'];
                }
                mysql_free_result($result_1);

                $sql_2 = "INSERT INTO loginout_summary SET ".
                    " login_sum = '$visits', ".
                    " start_date = '$start_date', ".
                    " end_date = '$end_date' ";
                $result_2 = db_query($sql_2, $mysqlMainDb);
                @mysql_free_result($result_2);

                $sql_3 = "DELETE FROM loginout ".
                    "WHERE `when` >= '$start_date' AND ".
                    " `when` < '$end_date' ";
                $result_3 = db_query($sql_3, $mysqlMainDb);
                @mysql_free_result($result_3);


            #next month
            $start_date = $end_date;
	    $stmp = $end_stmp;
            $end_stmp += 31*24*60*60;  //end time + 1 month
            $end_date = date('Y-m-01 00:00:00', $end_stmp);
	    $start_date = date('Y-m-01 00:00:00', $stmp);

        }
    }
?>
