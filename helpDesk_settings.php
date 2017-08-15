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

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_settings.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>" ;
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Help Desk Settings') . "</div>" ;
    print "</div>" ;

    $dbError = false;

    try {
        $sql = "SELECT nameDisplay, description, name, value FROM gibbonSetting WHERE scope = 'Help Desk'";
        $result = $connection2->prepare($sql);
        $result->execute();
    } catch (PDOException $e) {
        $dbError = true;
    }
    
    if (isset($_GET['return']) || $dbError) {
        $return = $_GET['return'];
        if ($dbError) {
            $return = "error2";
        }
        returnProcess($guid, $return, null, array("success1" => "Your request was completed successfully, but one of your options was invalid."));
    }

    $form = Form::create("helpDeskSettings", $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_settingsProcess.php");

    while ($row = $result->fetch()) {
        $fRow = $form->addRow();
            $fRow->addLabel($row["name"] . "Label", $row["nameDisplay"])->description($row["description"]);
            
            if ($row['name'] == "issuePriorityName") {
                $fRow->addTextField($row["name"])->maxlength(100)->setValue($row["value"]);
            } elseif ($row['name'] == "issuePriority" || $row['name'] == "issueCategory") {
                $fRow->addTextArea($row["name"])->setRows(4)->setValue($row["value"]);
            } elseif ($row['name'] == "resolvedIssuePrivacy") {
                $fRow->addSelect($row["name"])->fromArray(array("Everyone", "Related", "Owner", "No one"))->selected($row["value"])->setRequired(true);
            }
    }

    $fRow = $form->addRow();
        $fRow->addFooter();
        $fRow->addSubmit();

    print $form->getOutput();
}

?>