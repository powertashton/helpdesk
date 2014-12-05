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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isModuleAccessible($guid, $connection2)==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Issues') . "</div>" ;
	print "</div>" ;
	print "<h3>" ;
	print _("Filter") ;
	print "</h3>" ;
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}

	$filter=NULL ;
	$filter2=NULL ;
	$filter3=NULL ;
	$filter4=NULL ;

	if (isset($_POST["filter"])) {
		$filter=$_POST["filter"] ;
	}
	if (isset($_POST["filter2"])) {
		$filter2=$_POST["filter2"] ;
	}
	if (isset($_POST["filter3"])) {
		$filter3=$_POST["filter3"] ;
	}
	if (isset($_POST["filter4"])) {
		$filter4=$_POST["filter4"] ;
	}
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSetting WHERE scope='Help Desk' AND name='issuePriority'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	$row=$result->fetch() ;
	$priorityFilters = array("All");
	foreach (explode(",", $row["value"]) as $type) {
		if(!($type=="")) { 
		  array_push($priorityFilters, $type);
		}
	}
	$renderPriority = count($priorityFilters)>1;
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSetting WHERE scope='Help Desk' AND name='issuePriorityName'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	$row=$result->fetch() ;
	$priorityName = $row["value"];
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonSetting WHERE scope='Help Desk' AND name='issueCategory'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	$row=$result->fetch() ;
	$categoryFilters = array("All");
	foreach (explode(",", $row["value"]) as $type) {
		if(!($type=="")) { 
		  array_push($categoryFilters, $type);
		}
	}
	
	$issueFilters = array("My Issues");
	if(isTechnician($_SESSION[$guid]["gibbonPersonID"], $connection2)) array_push($issueFilters, "My Working");
	if($highestAction=="View issues_All" || $highestAction=="View issues_All&Assign") array_push($issueFilters, "All");
	$statusFilters = array("All", "Unassigned", "Pending", "Resolved");
	$dataIssue["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"];
	$whereIssue = "";
	if ($filter=="") {
		$filter=$issueFilters[0];
	}

	if ($filter=="My Issues") {
		$dataIssue["helpDeskGibbonPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
		$whereIssue.= " AND helpDeskIssue.gibbonPersonID=:helpDeskGibbonPersonID";
	}
	else if ($filter=="My Working") {
		$dataIssue["helpDeskTechnicianID"] = getTechnicianID($_SESSION[$guid]["gibbonPersonID"], $connection2);
		$whereIssue.= " AND helpDeskIssue.technicianID=:helpDeskTechnicianID";
	}
	
	if ($filter2=="") {
		$filter2=$statusFilters[0];
	}
	if ($filter2=="Unassigned") {
		$dataIssue["helpDeskStatus"] = 'Unassigned';
		$whereIssue.= " AND helpDeskIssue.status=:helpDeskStatus";
	}
	else if ($filter2=="Pending") {
		$dataIssue["helpDeskStatus"] = 'Pending';
		$whereIssue.= " AND helpDeskIssue.status=:helpDeskStatus";
	}
	else if ($filter2=="Resolved") {
		$dataIssue["helpDeskStatus"] = 'Resolved';
		$whereIssue.= " AND helpDeskIssue.status=:helpDeskStatus";
	}
	
	if ($filter3=="") {
		$filter3=$categoryFilters[0];
	}
	if ($filter3!="All") {
		$dataIssue["helpDeskCategory"] = $filter3;
		$whereIssue.= " AND helpDeskIssue.category=:helpDeskCategory";
	}
	
	if ($filter4=="") {
		$filter4=$priorityFilters[0];
	}
	if ($filter4!="All") {
		$dataIssue["helpDeskPriority"] = $filter4;
		$whereIssue.= " AND helpDeskIssue.priority=:helpDeskPriority";
	}
	print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>" ;
		print"<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;	
			if(count($issueFilters)>1)
			{
				print "<tr>";
					print "<td> ";
						print "<b>". _('Issue Filter') ."</b><br/>";
						print "<span style=\"font-size: 90%\"><i></i></span>";
					print "</td>";
					print "<td class=\"right\">";
						print "<select name='filter' id='filter' style='width:302px'>" ;
						
							foreach($issueFilters as $option) {
								$selected="" ;
								if ($option==$filter) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $option . "'>". $option ."</option>" ;
							}
						print "</select>" ;
					print "</td>";
				print "</tr>";
			}
			print "<tr>";
				print "<td> ";
					print "<b>".  _('Status Filter') ."</b><br/>";
					print "<span style=\"font-size: 90%\"><i></i></span>";
				print "</td>";
				print "<td class=\"right\">";
					print "<select name='filter2' id='filter2' style='width:302px'>" ;
						foreach($statusFilters as $option) {
							$selected="" ;
							if ($option==$filter2) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $option . "'>". $option ."</option>" ;
						}
					print "</select>" ;
				print "</td>";
			print "</tr>";
			if(count($categoryFilters)>1) {
				print "<tr>";
					print "<td> ";
						print "<b>". _('Category') ."</b><br/>";
						print "<span style=\"font-size: 90%\"><i></i></span>";
					print "</td>";
					print "<td class=\"right\">";
						print "<select name='filter3' id='filter3' style='width:302px'>" ;
					
							foreach($categoryFilters as $option) {
								$selected="" ;
								if ($option==$filter3) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $option . "'>". $option ."</option>" ;
							}
						print "</select>" ;
					print "</td>";
				print "</tr>";
			}
			if($renderPriority) {
				print "<tr>";
					print "<td> ";
						print "<b>". $priorityName ."</b><br/>";
						print "<span style=\"font-size: 90%\"><i></i></span>";
					print "</td>";
					print "<td class=\"right\">";
						print "<select name='filter4' id='filter4' style='width:302px'>" ;
						
							foreach($priorityFilters as $option) {
								$selected="" ;
								if ($option==$filter4) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $option . "'>". $option ."</option>" ;
							}
						print "</select>" ;
					print "</td>";
				print "</tr>";
			}
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='submit' value='" . _('Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
		print"</table>" ;
	print "</form>" ;

  try {
    $sqlIssue="SELECT helpDeskIssue.* , surname , preferredName, gibbonPerson.title FROM helpDeskIssue JOIN gibbonPerson ON (helpDeskIssue.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID " . $whereIssue . " ORDER BY date DESC" ;
    $resultIssue=$connection2->prepare($sqlIssue);
    $resultIssue->execute($dataIssue);
  }
  catch(PDOException $e) {
	print $e;
  }

  	print "<h3>" ;
  	print "<a style='position:relative; bottom:10px;float:right;' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_submit.php'><img title=" . _('New ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>";
	print _("Issues") ;
	print "</h3>" ;
    print "<table class = 'smallIntBorder' cellspacing = '0' style = 'width: 100% !important'>";
    print "<tr> <th>Date</th> <th>Title</th> <th>Description</th> <th>Name</th> <th>Status</th> <th>Category</th>"; 
  	if($renderPriority) { print "<th>$priorityName</th>"; }
    print "<th>Action</th> </tr>";
	if ($resultIssue->rowCount()==0){
    	print "<tr>";
    	$colspan = 7;
    	if(!$renderPriority) { $colspan-=1; }
    	print "<td colspan=$colspan>";
    	print _("There are no records to display.");
		print "<td>";
		print "</tr>";
    }
    else {
		foreach($resultIssue as $row){
		  print "<tr>";
		  printf("<td>" .dateConvertBack($guid, $row["date"]). "</td>");
		  printf("<td>" .$row['issueName']. "</td>");
		  printf("<td>" .substr($row['description'], 0, 15). "...</td>");
		  printf("<td>" .formatName($row['title'],$row['preferredName'],$row['surname'], "Student", FALSE, FALSE). "</td>");
		  printf("<td>" .$row['status']. "</td>");
		  printf("<td>" .$row['category']. "</td>");
		  if($renderPriority) { printf("<td>" .$row['priority']. "</td>"); }
		  print "<td>";
		  if(isTechnician($_SESSION[$guid]["gibbonPersonID"], $connection2))
		  {
			  if($row['technicianID']==null && !($row['status']=="Resolved")) 
			  {
				?><input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>"><?php
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_acceptProcess.php?issueID=". $row["issueID"] . "'><img title=" . _('Accept ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a>";
			 	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_discuss_view.php&issueID=". $row["issueID"] . "'><img title=" . _('View ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/zoom.png'/></a>";
			  }
			  else if($row['technicianID']==getTechnicianID($_SESSION[$guid]["gibbonPersonID"], $connection2))
			  {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_discuss_view.php&issueID=". $row["issueID"] . "'><img title=" . _('Work ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/zoom.png'/></a>";
			    if(!($row['status']=="Resolved")) { print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_resolveProcess.php?issueID=". $row["issueID"] . "'><img title=" . _('Resolve ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>"; }
			  }
		  }
		  if($row['gibbonPersonID']==$_SESSION[$guid]["gibbonPersonID"] && !($row['technicianID']==getTechnicianID($_SESSION[$guid]["gibbonPersonID"], $connection2)) && !($row['status']=="Resolved"))
		  {
		    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/issues_resolveProcess.php?issueID=". $row["issueID"] . "'><img title=" . _('Resolve ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>";
		  }
		  if($row['technicianID']==null && $highestAction=="View issues_All&Assign" && !($row['status']=="Resolved"))
		  {
		    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/issues_assign.php&issueID=". $row["issueID"] . "'><img title=" . _('Assign ') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>";		  
		  }
		  print "</td>";
		  print "</tr>";
		}
	}
    print "</table>";

}
?>