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

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_issues.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>";
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Issues') . "</div>";
    print "</div>";

    print "<h3>";
        print __($guid, "Filters");
    print "</h3>";

    $technician = getTechnician($connection2, $_SESSION[$guid]["gibbonPersonID"]);
    if ($technician != null) {
        $techGroup = getTechnicianGroup($connection2, $technician['groupID']);
    }
    //Relation Filter
    //All Filters: All, Assigned to me, My Issues
    $relationFilters = array();
    $relationFilter = null;

    if ($technician != null) {
        if ($techGroup['fullAccess']) {
            $relationFilters[""] = "All";
        }
        $relationFilters["AM"] = "Assigned to me";
    }
    $relationFilters["MI"] = "My Issues";


    $relationFilter = null;
    if (isset($_POST['relationFilter'])) {
        $val = $_POST['relationFilter'];
        if (in_array($val, array_keys($relationFilters))) {
            $relationFilter = $val;
        }
    }

    if ($relationFilter == null) {
        $relationFilter = array_keys($relationFilters)[0];
    }

    //Status Filter
    //All Filters: All, Unassigned, Pending, Resolved, Unassigned and Pending, Pending and Resolved
    $statusFilters = array();
    $statusFilter = null;
    if ($technician != null) {
        $viewIssueStatus = $techGroup['viewIssueStatus'];
        if ($techGroup['fullAccess']) {
            $viewIssueStatus = "All";
        }
        switch ($viewIssueStatus) {
            case "All":
                $statusFilters = array("" => "All", "UP" => "Unassigned and Pending", "PR" => "Pending and Resolved", "U" => "Unassigned", "P" => "Pending", "R" => "Resolved");
                break;
            case "UP":
                $statusFilters = array("UP" => "Unassigned and Pending", "U" => "Unassigned", "P" => "Pending");
                break;
            case "PR":
                $statusFilters = array("PR" => "Pending and Resolved", "P" => "Pending", "R" => "Resolved");
                break;
        }   
    }

    if (empty($statusFilters)) {
        $statusFilters = array("" => "All", "U" => "Unassigned", "P" => "Pending", "R" => "Resolved"); 
    }

    if (isset($_POST["statusFilter"])) {
        $val = $_POST["statusFilter"];
        if (in_array($val, array_keys($statusFilters))) {
            $statusFilter = $val;
        }
    }

    if ($statusFilter == null) {
        $statusFilter = array_keys($statusFilters)[0];
    }

    //Category Filter
    //All Filters: Get from settings
    $categoryFilters = array("All");
    $categoryFilter = null;

    try {
        $sql = "SELECT value FROM gibbonSetting WHERE name = 'issueCategory' AND scope = 'Help Desk'";
        $result = $connection2->prepare($sql);
        $result->execute();
        if ($result->rowCount() == 1) {
            foreach(explode(",", $result->fetch()['value']) as $category) {
                $categoryFilters[] =  $category;
            } 
        }
    } catch (PDOException $e) {
    }

    if (count($categoryFilters) > 0) {
        if (isset($_POST["categoryFilter"])) {
            $val = $_POST["categoryFilter"];
            if (in_array($val, $categoryFilters)) {
                $categoryFilter = $val;
            }
        }

        if ($categoryFilter == null) {
            $categoryFilter = $categoryFilters[0];
        }
    }

    //Priority Filter
    //All Filters: Get from settings
    $priorityFilters = array("All");
    $priorityFilter = null;
    $priorityName = null;

    try {
        $sql = "SELECT value FROM gibbonSetting WHERE name = 'issuePriority' AND scope = 'Help Desk'";
        $result = $connection2->prepare($sql);
        $result->execute();
        if ($result->rowCount() == 1) {
            foreach(explode(",", $result->fetch()['value']) as $priority) {
                $priorityFilters[] =  $priority;
            } 
        }

        $sql2 = "SELECT value FROM gibbonSetting WHERE name = 'issuePriorityName' AND scope = 'Help Desk'";
        $result2 = $connection2->prepare($sql2);
        $result2->execute();
        if ($result2->rowCount() == 1) {
            $priorityName = $result2->fetch()['value'];
        }
    } catch (PDOException $e) {
    }

    if (count($priorityFilters) > 0) {
        if (isset($_POST["priorityFilter"])) {
            $val = $_POST["priorityFilter"];
            if (in_array($val, $priorityFilters)) {
                $priorityFilter = $val;
            }
        }

        if ($priorityFilter == null) {
            $priorityFilter = $priorityFilters[0];
        }
    }

    //ID Filter
    //Positive Integer inputed by User
    $IDFilter = -1;

    if (isset($_POST['IDFilter'])) {
        $val = intval($_POST['IDFilter']);
        if (gettype($val) == "integer") {
            $IDFilter = abs($val);
        }
    }

    //Year Filter
    //All Filters: Get from gibbonSchoolYear
    $yearFilters = array();
    $yearFilter = null;

    try {
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear";
        $result = $connection2->prepare($sql);
        $result->execute();
        while ($row = $result->fetch()) {
            $yearFilters[$row['gibbonSchoolYearID']] = $row['name'];
        }
    } catch (PDOException $e) {
    }

    if (isset($_POST["yearFilter"])) {
        $val = $_POST["yearFilter"];
        if (in_array($val, array_keys($yearFilters))) {
            $yearFilter = $val;
        }
    }

    if ($yearFilter == null) {
        $yearFilter = $_SESSION[$guid]["gibbonSchoolYearID"];
    }

    print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>"; ?>
        <table class='noIntBorder' cellspacing='0' style='width: 100%'>
            <?php
            if (count($relationFilters) > 0) {
            ?>
                <tr>
                    <td> 
                        <b><?php echo __($guid, 'Relation Filter') ?> *</b><br/>
                    </td>
                    <td class="right">
                        <?php
                        echo "<select name='relationFilter' id='relationFilter' style='width:302px'>";
                            foreach($relationFilters as $key => $realtion) {
                                $selected = "";
                                if ($key == $relationFilter) {
                                    $selected = "selected";
                                }
                                echo "<option $selected value='$key'>" . __($guid, $realtion) . '</option>';
                            }
                        echo '</select>';
                        ?>
                    </td>
                </tr>
            <?php
            }
            ?>
            <tr>
                <td> 
                    <b><?php echo __($guid, 'Status Filter') ?> *</b><br/>
                </td>
                <td class="right">
                    <?php
                    echo "<select name='statusFilter' id='statusFilter' style='width:302px'>";
                        foreach($statusFilters as $key => $status) {
                            $selected = "";
                            if ($key == $statusFilter) {
                                $selected = "selected";
                            }
                            echo "<option $selected value='$key'>" . __($guid, $status) . '</option>';
                        }
                    echo '</select>';
                    ?>
                </td>
            </tr>
            <?php
            if (count($categoryFilters) > 0) {
            ?>
                <tr>
                    <td> 
                        <b><?php echo __($guid, 'Category Filter') ?> *</b><br/>
                    </td>
                    <td class="right">
                        <?php
                        echo "<select name='categoryFilter' id='categoryFilter' style='width:302px'>";
                            foreach($categoryFilters as $category) {
                                $selected = "";
                                if ($category == $categoryFilter) {
                                    $selected = "selected";
                                }
                                echo "<option $selected value='$category'>" . __($guid, $category) . '</option>';
                            }
                        echo '</select>';
                        ?>
                    </td>
                </tr>
            <?php
            }
            ?>
            <?php
            if (count($priorityFilters) > 0) {
            ?>
                <tr>
                    <td> 
                        <b><?php echo __($guid, 'Priority Filter') ?> *</b><br/>
                    </td>
                    <td class="right">
                        <?php
                        echo "<select name='priorityFilter' id='priorityFilter' style='width:302px'>";
                            foreach($priorityFilters as $priority) {
                                $selected = "";
                                if ($priority == $priorityFilter) {
                                    $selected = "selected";
                                }
                                echo "<option $selected value='$priority'>" . __($guid, $priority) . '</option>';
                            }
                        echo '</select>';
                        ?>
                    </td>
                </tr>
            <?php
            }
            ?>
            <tr>
                <td> 
                    <b><?php echo __($guid, 'Year Filter') ?> *</b><br/>
                </td>
                <td class="right">
                    <?php
                    echo "<select name='yearFilter' id='yearFilter' style='width:302px'>";
                        foreach($yearFilters as $key => $year) {
                            $selected = "";
                            if ($key == $yearFilter) {
                                $selected = "selected";
                            }
                            echo "<option $selected value='$key'>" . __($guid, $year) . '</option>';
                        }
                    echo '</select>';
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php echo __($guid, "ID Filter") ?></b><br/>
                </td>
                <td class="right">
                    <input type='text' value='<?php echo $IDFilter ?>' id='IDFilter' name='IDFilter' style='width: 300px'>
                </td>
            </tr>
            <tr>
                <td class='right' colspan=2>
                    <input type='submit' value='<?php print _('Go') ?>'>
                </td>
            </tr>
        </table>
    </form>
    <?php

    print "<h3>";
        print __($guid, "Issues");
    print "</h3>";
}

?>