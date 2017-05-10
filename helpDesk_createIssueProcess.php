<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

include "../../functions.php";
include "../../config.php";

include "./moduleFunctions.php";

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/" ;

if (isActionAccessible($guid, $connection2, "/modules/Help Desk/helpDesk_createIssue.php") == FALSE) {
    //Fail 0
    $URL .= "helpDesk_manage.php&return=error0" ;
    header("Location: {$URL}");
} else {
    $URL .= "helpDesk_createIssue.php";

    

    try {
        $data = array("gibbonPersonID" => $gibbonPersonID, "name" => $issueName, "description" => $description, "category" => $category, "priority" => $priority, $gibbonSchoolYearID=> $_SESSION[$guid]["gibbonSchoolYearID"], "createdByID" => $createdByID, "privacySetting" => $privacySetting, "date" => date("Y-m-d"));
        $sql = "INSERT INTO helpDeskIssue SET gibbonPersonID=:gibbonPersonID, issueName=:name, description=:description, category=:category, priority=:priority, gibbonSchoolYearID=:gibbonSchoolYearID, createdByID=:createdByID, privacySetting=:privacySetting, `date`=:date";
        $result = $connection2->prepare($sql);
        $result->execute($sql);
        $issueID = $connection2->lastInsertId();
    } catch(PDOException $e) {
    }

    $URL .="&return=success0&issueID=";
    header("Location: {$URL}");
    exit();
}
?>
