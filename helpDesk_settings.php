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
    //TODO: Replace with Form Class 
    ?>

    <form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_settingsProcess.php" ?>">
        <table class='smallIntBorder' cellspacing='0' style="width: 100%">
            <?php
                while ($row = $result->fetch()) {
                    print "<tr>";
                        print "<td style='width:275px'>";
                            print "<b>" . __($guid, $row["nameDisplay"]) . "</b><br/>";
                            if ($row["description"] != "") {
                                print "<span style='font-size: 90%''><i>" . __($guid, $row["description"]) . "</i></span>";
                            }
                        print "</td>";
                        print "<td class='right'>";
                            if ($row['name'] == "issuePriorityName") {
                                print "<input name='" . $row["name"] . "' id='" . $row["name"] . "' maxlength=100 value='" . $row["value"] . "' type='text' data-minlength='1' style='width: 300px'></input>";
                                print "<script type='text/javascript'>";
                                    print "var priorityName = new LiveValidation('issuePriorityName');";
                                    print "priorityName.add(Validate.Presence);";
                                print "</script>";
                            } elseif ($row['name'] == "issuePriority" || $row['name'] == "issueCategory") {
                                print "<textarea name='" . $row["name"] . "' id='" . $row["name"] . "' rows=4 type='text' style='width: 300px'>" . $row["value"] . "</textarea>";
                            } elseif ($row['name'] == "resolvedIssuePrivacy") {
                                print "<select name='".  $row["name"] . "' id='" . $row["name"] . "' style='width:302px'>";
                                    $options = array("Everyone", "Related", "Owner", "No one");
                                    foreach ($options as $option) {
                                        $selected = "";
                                        if ($option == $row["value"]) {
                                            $selected = "selected";
                                        }
                                        print "<option $selected value='" . $option . "'>". $option ."</option>" ;
                                    }
                                print "</select>";
                            }
                        print "</td>";
                    print "</tr>";
                }
            ?>
            <tr>
                <td class='right' colspan=2>
                    <input type='submit' value='<?php print _('Go') ?>'>
                </td>
            </tr>
        </table>
    </form>    

    <?php

}

?>