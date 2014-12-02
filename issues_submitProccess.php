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

include "../../functions.php" ;
include "../../config.php" ;

include "./moduleFunctions.php" ;

//New PDO DB connection
try {
  $connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/issues_view.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Help Desk/issues_submit.php")==FALSE) {
	header("Location: {$URL}");
}
else {
	//Proceed!
	if(isset($_POST["name"])) {
	  $name=$_POST["name"] ;
	}
	if(isset($_POST["category"])) {
	  $category=$_POST["category"] ;
	}
	if(isset($_POST["description"])) {
	  $description=$_POST["description"] ;
	}
	$priority = "";
	if(isset($_POST["priority"])) {
	  $priority=$_POST["priority"] ;
	}
	

	if ($name=="" || $category=="" || $description=="") {
		//Fail 3
		$URL=$URL . "&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		//Write to database
		try {
			$data=array("issueID"=> 0, "technicianID"=>null, "gibbonPersonID"=> $_SESSION[$guid]["gibbonPersonID"], "name"=> $name, "description"=> $description, "date" => date("Y-m-d"), "status"=> "Unassigned", "category"=> $category, "priority"=> $priority, "gibbonSchoolYearID"=> $_SESSION[$guid]["gibbonSchoolYearID"]);
			$sql="INSERT INTO helpDeskIssue SET issueID=:issueID, technicianID=:technicianID, gibbonPersonID=:gibbonPersonID, issueName=:name, description=:description, date=:date, status=:status, category=:category, priority=:priority, gibbonSchoolYearID=:gibbonSchoolYearID" ;
      		$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) {
			header("Location: {$URL}");
			break ;
		}		
		
		
		notifyTechnican($connection2, $guid, $connection2->lastInsertId());
		//Success 0
		$URL=$URL . "&addReturn=success0" ;
		header("Location: {$URL}");

	}
}
?>
