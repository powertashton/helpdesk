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
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_manageTechnicians.php'>" . __($guid, "Manage Technicians") . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Technician') . "</div>";
    print "</div>";
    
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET["return"], $editLink, null);
    }

    if (isset($_GET['technicianID'])) {
        $technicianID = $_GET['technicianID'];
        
    } else {
        returnProcess($guid, "error1", null, null);
    }

    if(isset($technicianID) && technicianExists($connection2, $technicianID)) {
        $tech = getTechnicianName($connection2, $technicianID);
    } else {
        returnProcess($guid, "error1", null, null);
    }


    print "<h3>";
        print __($guid, "Edit Technician: ") . formatName($tech['title'], $tech['preferredName'], $tech['surname'], "Student", FALSE, FALSE);
    print "</h3>";

    $techGroups = array();

    try {
        $data = array('technicianID' => $technicianID);
        $sqlTechGroups = 'SELECT groupID, groupName FROM helpDeskTechGroups WHERE groupID != (SELECT groupID FROM helpDeskTechnicians WHERE technicianID=:technicianID) ORDER BY groupName, groupID ASC';
        $resultTechGroups = $connection2->prepare($sqlTechGroups);
        $resultTechGroups->execute($data);

        while($row = $resultTechGroups->fetch()) {
            $techGroups[$row['groupID']] = $row['groupName'];
        }

    } catch(PDOException $e) {
        print $e;
        returnProcess($guid, "error2", null, null);
    }

    $form = Form::create('editTechnicianForm', $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_editTechnicianProcess.php?technicianID=" . $technicianID);

    $row = $form->addRow();
        $row->addLabel("techGroupIDLabel", "Technician Group *");
        $row->addSelect("techGroupID")->fromArray($techGroups)->placeholder("Please select...")->setRequired(true);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();

}
