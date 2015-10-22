<?php
/* ========================================================================
 * Open eClass 2.9
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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


require_once '../../include/libchart/libchart.php';
$usage_defaults = array (
    'u_stats_type' => 'visits',
    'u_interval' => 'daily',
    'u_user_id' => -1,
    'u_date_start' => strftime('%Y-%m-%d', strtotime('now -15 day')),
    'u_date_end' => strftime('%Y-%m-%d', strtotime('now')),
);

foreach ($usage_defaults as $key => $val) {
    if (!isset($_POST[$key])) {
        $$key = $val;
    } else {
        $$key = q($_POST[$key]);
    }
}

#see if chart has content
$chart_content = 0;

$date_fmt = '%Y-%m-%d';
$u_date_start = mysql_real_escape_string($u_date_start);
$u_date_end = mysql_real_escape_string($u_date_end);
$date_where = " (`when` BETWEEN '$u_date_start 00:00:00' AND '$u_date_end 23:59:59') ";
$date_what  = "DATE_FORMAT(MIN(`when`), '$date_fmt') AS date_start, DATE_FORMAT(MAX(`when`), '$date_fmt') AS date_end ";


switch ($u_interval) {
    case "summary":
        $date_what = '';
        $date_group = '';
    break;
    case "daily":
        $date_what .= ", DATE_FORMAT(`when`, '$date_fmt') AS date ,";
        $date_group = " GROUP BY DATE(`when`) ";
    break;
    case "weekly":
        $date_what .= ", DATE_FORMAT(`when` - INTERVAL WEEKDAY(`when`) DAY, '$date_fmt') AS week_start ".
                      ", DATE_FORMAT(`when` + INTERVAL (6 - WEEKDAY(`when`)) DAY, '$date_fmt') AS week_end ,";
        $date_group = " GROUP BY WEEK(`when`)";
    break;
    case "monthly":
        $date_what .= ", MONTH(`when`) AS month ,";
        $date_group = " GROUP BY MONTH(`when`)";
    break;
    case "yearly":
        $date_what .= ", YEAR(`when`) AS year ,";
        $date_group = "  GROUP BY YEAR(`when`) ";
    break;
    default:
        $date_what = '';
        $date_group = '';
    break;
}
if ($u_user_id != -1) {
    $user_where = " (id_user = '$u_user_id') ";
} else {
    $user_where = " (1) ";
}


switch ($u_stats_type) {
    case "visits":
    $query = "SELECT ".$date_what." COUNT(*) AS cnt FROM loginout WHERE $date_where AND $user_where AND action='LOGIN' $date_group ORDER BY `when` ASC";
    $result = db_query($query, $mysqlMainDb);
    $chart = new VerticalBarChart();
    $dataSet = new XYDataSet();
    switch ($u_interval) {
        case "summary":
            while ($row = mysql_fetch_assoc($result)) {
                $dataSet->addPoint(new Point($langSummary, $row['cnt']));                
                $chart->setDataSet($dataSet);
                $chart_content = 1;
            }
        break;
        case "daily":
            while ($row = mysql_fetch_assoc($result)) {
                $dataSet->addPoint(new Point($row['date'], $row['cnt']));                
                $chart->setDataSet($dataSet);
                $chart_content = 1;
            }
        break;
        case "weekly":
            while ($row = mysql_fetch_assoc($result)) {
                $dataSet->addPoint(new Point($row['week_start'].' - '.$row['week_end'], $row['cnt']));                
                $chart->setDataSet($dataSet);
                $chart_content = 1;
            }
        break;
        case "monthly":
            while ($row = mysql_fetch_assoc($result)) {
                $dataSet->addPoint(new Point($langMonths[$row['month']], $row['cnt']));                
                $chart->setDataSet($dataSet);
                $chart_content=1;
            }
        break;
        case "yearly":
            while ($row = mysql_fetch_assoc($result)) {
                $dataSet->addPoint(new Point($row['year'], $row['cnt']));                
                $chart->setDataSet($dataSet);
                $chart_content=1;
            }
        break;
    }
    $chart->setTitle($langVisits);
    break;
}
if ($result !== false)
    mysql_free_result($result);

if (!file_exists("../../courses/temp")) {
    mkdir("../../courses/temp", 0777);
}

//check if there are statistics to show
if ($chart_content) {
    $chart_path = 'courses/temp/chart_'.md5(serialize($chart)).'.png';
    $chart->render($webDir.$chart_path);    
    $tool_content .= '
      <table class="FormData" width="99%" align="left">
      <tbody>
      <tr>
        <th width="220"  class="left">'.$langVisits.' :</th>
        <td valign="top"><img src="'.$urlServer.$chart_path.'" /></td>
      </tr>
      </tbody>
      </table>';
} elseif (isset($_POST['btnUsage']) and $chart_content == 0) {
    $tool_content .= '
      <table class="FormData" width="99%" align="left">
      <tbody>
      <tr>
        <th width="220"  class="left">'.$langVisits.' :</th>
        <td>'.$langNoStatistics.'</td>
      </tr>
      </tbody>
      </table>';
}
