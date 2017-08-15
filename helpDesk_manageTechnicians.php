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

use Gibbon\Forms\Form;

include "./modules/Help Desk/moduleFunctions.php";

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_issues.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Technicians') . "</div>";
    print "</div>";

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    ?>
    <div class="linkTop">
        <a style='position:relative; bottom:10px; float:right;' href='<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_createTechnician.php" ?>'>
            <?php
                print __($guid, "Create");
            ?>
            <img style='margin-left: -2px' title='<?php print __($guid, "Create");?>' src='<?php print $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png" ?>'/>
        </a>
    </div>
    <?php

    print "<h3>";
        print __($guid, "Manage Technicians");
    print "</h3>";

    ?>
    

    <table cellspacing = '0' style = 'width: 100% !important'>
        <tr>
            <th>
                <?php print __($guid, "Technician Name") ?>
            </th>

            <th>
                <?php print __($guid, "Assigned Issues") ?>
            </th>

            <th>
                <?php print __($guid, "Technician Group") ?>
            </th>

            <th>
                <?php print __($guid, "Actions") ?>
            </th>
        </tr>
        <?php

        try {
            $sql = "SELECT helpDeskTechnicians.technicianID, helpDeskTechnicians.gibbonPersonID, helpDeskTechnicians.groupID, gibbonPerson.title, gibbonPerson.preferredName, gibbonPerson.surname, helpDeskTechGroups.groupName FROM helpDeskTechnicians JOIN gibbonPerson ON (helpDeskTechnicians.gibbonPersonID = gibbonPerson.gibbonPersonID) JOIN helpDeskTechGroups ON (helpDeskTechnicians.groupID = helpDeskTechGroups.groupID) ORDER BY technicianID ASC";
            $resultTechs = $connection2->prepare($sql);
            $resultTechs->execute();

            $sql2 = "SELECT issueID, technicianID, issueName FROM helpDeskIssue WHERE technicianID != NULL";
            $resultIssues = $connection2->prepare($sql2);
            $resultIssues->execute();

            $sqlTechGroups = "SELECT groupID FROM helpDeskTechGroups";
            $resultTechGroups = $connection2->prepare($sqlTechGroups);
            $resultTechGroups->execute();

        } catch(PDOExeception $e) {
        }

        $assignedIssues = array();

        while($row = $resultIssues->fetch()) {
            $assignedIssue = '';
            if (isset($assignedIssues[$row['technicianID']])) {
                $assignedIssue = $assignedIssues[$row['technicianID']];
            }

            $assignedIssue .= '<a href="' . $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/Help Desk/helpDesk_issueDiscuss.php&issueID=' . $row['issueID'] . '">' . $row['issueName'] . '</a>';
            $assignedIssues[$row['technicianID']] = $assignedIssue;
        }

        while($row = $resultTechs->fetch()) {
            $techIssues = "";
            if(isset($assignedIssues[$row["technicianID"]])) {
                $techIssues = $assignedIssues[$row["technicianID"]];
            }
            print "<tr>";
                print "<td>" . formatName($row['title'], $row['preferredName'], $row['surname'], "Student", FALSE, FALSE) . "</td>";
                print "<td>" . $techIssues . "</td>";
                print "<td>" . $row['groupName'] . "</td>";
                print "<td>";
                    if ($resultTechGroups->rowcount() > 1) {
                        print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_editTechnician.php&technicianID=". $row['technicianID'] ."'><img title='" . __($guid, 'Edit') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>";
                    }
                    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_technicianStats.php&technicianID=". $row['technicianID'] ."'><img title='" . __($guid, 'Stats') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/internalAssessment.png'/></a>";       
                    print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_technicianDeleteProcess.php?technicianID=". $row['technicianID'] ."'><img title='" . __($guid, 'Delete') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>";
                print "</td>";
            print "</tr>";
        }

        ?>
    </table>
    <?php
}