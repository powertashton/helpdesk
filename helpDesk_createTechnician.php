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

@session_start();

include "./modules/Help Desk/moduleFunctions.php";

use Gibbon\Forms\Form;

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_manageTechnicians.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_manageTechnicians.php'>" . __($guid, "Manage Technicians") . "</a> > </div><div class='trailEnd'>" . __($guid, 'Create Technician') . "</div>";
    print "</div>";
    
    if (isset($_GET['return'])) {
        $editLink = "";
        if (isset($_GET["technicianID"])) {
            $editLink = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_editTechnician.php&technicianID=" . $_GET["technicianID"];
        }
        returnProcess($guid, $_GET["return"], $editLink, null);
    }

    print "<h3>";
        print __($guid, "Create Technician");
    print "</h3>";

    $people = array();
    $techGroups = array();

    try {
        $sqlTechs = 'SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title FROM gibbonPerson LEFT JOIN helpDeskTechnicians ON (helpDeskTechnicians.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status="Full" AND helpDeskTechnicians.gibbonPersonID IS NULL ORDER BY surname, preferredName ASC';
        $resultTechs = $connection2->prepare($sqlTechs);
        $resultTechs->execute();

        while($row = $resultTechs->fetch()) {
            $people[$row['gibbonPersonID']] = formatName($row['title'], $row['preferredName'], $row['surname'], "Student", FALSE, FALSE);
        }

        $sqlTechGroups = 'SELECT groupID, groupName FROM helpDeskTechGroups ORDER BY groupName, groupID ASC';
        $resultTechGroups = $connection2->prepare($sqlTechGroups);
        $resultTechGroups->execute();

        while($row = $resultTechGroups->fetch()) {
            $techGroups[$row['groupID']] = $row['groupName'];
        }
 
    } catch(PDOException $e) {
        returnProcess($guid, "error2", null, null);
    }

    $form = Form::create('issueFilters', $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_createTechnicianProcess.php");

    $row = $form->addRow();
        $row->addLabel('techPersonIDLabel', "Person *");
        $row->addSelect("techPersonID")->fromArray($people)->placeholder("Please select...")->setRequired(true);;

    $row = $form->addRow();
        $row->addLabel("techGroupIDLabel", "Technician Group *");
        $row->addSelect("techGroupID")->fromArray($techGroups)->placeholder("Please select...")->setRequired(true);;

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();

}
