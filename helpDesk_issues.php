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
    $data = array();
    //    $sql = "SELECT helpDeskIssue.issueID, helpDeskIssue.technicianID, helpDeskIssue.gibbonPersonID, helpDeskIssue.issueName, helpDeskIssue.description, helpDeskIssue.date, helpDeskIssue.status, helpDeskIssue.category, helpDeskIssue.priority, helpDeskIssue.gibbonSchoolYearID, helpDeskIssue.createdByID, helpDeskIssue.privacySetting, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.title, gibbonRole.category as nameCategory FROM helpDeskIssue JOIN gibbonPerson ON (helpDeskIssue.gibbonPersonID = gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID) WHERE";
    $sql = "SELECT helpDeskIssue.issueID, helpDeskIssue.technicianID, helpDeskIssue.gibbonPersonID, helpDeskIssue.issueName, helpDeskIssue.description, helpDeskIssue.date, helpDeskIssue.status, helpDeskIssue.category, helpDeskIssue.priority, helpDeskIssue.gibbonSchoolYearID, helpDeskIssue.createdByID, helpDeskIssue.privacySetting, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.title FROM helpDeskIssue LEFT JOIN gibbonPerson ON (helpDeskIssue.gibbonPersonID = gibbonPerson.gibbonPersonID) WHERE";

    //Relation Filter
    //All Filters: All, Assigned to me, My Issues
    $relationFilters = array();
    $relationFilter = null;

    if ($technician != null) {
        $relationFilters["A"] = "All";
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

    switch ($relationFilter) {
        case "MI":
            $data["gibbonPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
            $sql .= " helpDeskIssue.gibbonPersonID=:gibbonPersonID AND";
            break;
        case "AM":
            $data["technicianID"] = $technician["technicianID"];
            $sql .= " helpDeskIssue.technicianID=:technicianID AND";
            break;
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

    if ($statusFilter != "") {
        $sql .= " (";

        if (strpos($statusFilter, "U") !== false) {
            $data["status0"] = "Unassigned";
            $sql .= "status=:status0 OR ";
        }

        if (strpos($statusFilter, "P") !== false) {
            $data["status1"] = "Pending";
            $sql .= "status=:status1 OR ";
        }

        if (strpos($statusFilter, "R") !== false) {
            $data["status2"] = "Resolved";
            $sql .= "status=:status2 OR ";
        }


        $sql = substr($sql, 0, -4) . ") AND";
    }

    //Category Filter
    //All Filters: Get from settings
    $categoryFilters = array("All");
    $categoryFilter = null;

    try {
        $sqlCategory = "SELECT value FROM gibbonSetting WHERE name = 'issueCategory' AND scope = 'Help Desk'";
        $resultCategory = $connection2->prepare($sqlCategory);
        $resultCategory->execute();
        if ($resultCategory->rowCount() == 1) {
            foreach(explode(",", $resultCategory->fetch()['value']) as $category) {
                $categoryFilters[] =  $category;
            } 
        }
    } catch (PDOException $e) {
    }

    $renderCategory = count($categoryFilters) > 1;

    if ($renderCategory) {
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

    if ($categoryFilter != "All") {
        $data["category"] = $categoryFilter;
        $sql .= " category=:category AND";
    }

    //Priority Filter
    //All Filters: Get from settings
    $priorityFilters = array("All");
    $priorityFilter = null;
    $priorityName = null;

    try {
        $sqlPriority = "SELECT value FROM gibbonSetting WHERE name = 'issuePriority' AND scope = 'Help Desk'";
        $resultPriority = $connection2->prepare($sqlPriority);
        $resultPriority->execute();
        if ($resultPriority->rowCount() == 1) {
            foreach(explode(",", $resultPriority->fetch()['value']) as $priority) {
                $priorityFilters[] =  $priority;
            } 
        }

        $sqlPriority1 = "SELECT value FROM gibbonSetting WHERE name = 'issuePriorityName' AND scope = 'Help Desk'";
        $resultPriority1 = $connection2->prepare($sqlPriority1);
        $resultPriority1->execute();
        if ($resultPriority1->rowCount() == 1) {
            $priorityName = $resultPriority1->fetch()['value'];
        }
    } catch (PDOException $e) {
    }

    $renderPriority = count($priorityFilters) > 1;

    if ($renderPriority) {
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

    if ($priorityFilter != "All") {
        $data["priority"] = $priorityFilter;
        $sql .= " priority=:priority AND";
    }

    //ID Filter
    //Positive Integer inputed by User
    $IDFilter = -1;

    if (isset($_POST['IDFilter'])) {
        $val = intval($_POST['IDFilter']);
        if (gettype($val) == "integer") {
            if ($val != -1) {
                $IDFilter = abs($val);
            }
        }
    }

    if ($IDFilter >= 0) {
        $data["issueID"] = $IDFilter;
        $sql .= " issueID=:issueID AND";
    }

    //Year Filter
    //All Filters: Get from gibbonSchoolYear
    $yearFilters = array("All" => "All");
    $yearFilter = null;

    try {
        $sqlYear = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear";
        $resultYear = $connection2->prepare($sqlYear);
        $resultYear->execute();
        while ($row = $resultYear->fetch()) {
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

    if ($yearFilter != "All") {
        $data["gibbonSchoolYearID"] = $yearFilter;
        $sql .= " gibbonSchoolYearID=:gibbonSchoolYearID AND";
    }

    if (substr($sql, -6) == " WHERE") {
        $sql = substr($sql, 0, -6);
    } else {
        $sql = substr($sql, 0, -4);
    }

    $sql .= " ORDER BY FIELD(status, 'Unassigned', 'Pending', 'Resolved'), ";
    if ($renderPriority) {
        $sql .= "FIELD(priority, ";
        foreach ($priorityFilters as $priority) {
            $sql .= "'" . $priority . "',";
        }
        $sql = substr($sql, 0, -1) . "), ";
    }
    $sql .= "date DESC, issueID DESC";

    try {
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch(PDOException $e) {
        print $e;
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
            if ($renderCategory) {
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
            if ($renderPriority) {
            ?>
                <tr>
                    <td> 
                        <b><?php echo __($guid, $priorityName . ' Filter') ?> *</b><br/>
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

    <h3>
        <?php print __($guid, "Issues"); ?>
    </h3>

    <div class="linkTop">
        <a style='position:relative; bottom:10px; float:right;' href='<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_createIssue.php" ?>'>
            <?php
                print __($guid, "Create");
            ?>
            <img style='margin-left: -2px' title='<?php print __($guid, "Create") ?>' src='<?php print $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png" ?>'/>
        </a>
    </div>

    <table cellspacing = '0' style = 'width: 100% !important'>
        <tr>
            <th>
                <?php print __($guid, "ID") ?>
            </th>
            <th>
                <?php print __($guid, "Title") ?>
                <br/>
                <span style='font-size: 85%; font-style: italic'>
                    <?php print __($guid, "Description") ?>
                </span>
            </th>
            <th>
                <?php print __($guid, "Owner") ?>
                <br/>
                <?php 
                if ($renderCategory) {
                ?>
                    <span style='font-size: 85%; font-style: italic'>
                        <?php print __($guid, "Category") ?>
                    </span>
                <?php
                }
                ?>
            </th>
            <?php
            if ($renderPriority) {
            ?> 
                <th>
                    <?php print __($guid, $priorityName) ?>
                </th>
            <?php
            }
            ?>
            <th>
                <?php print __($guid, "Assigned Technician") ?>
            </th>
            <th>
                <?php print __($guid, "Status") ?>
                <br/>
                <span style='font-size: 85%; font-style: italic'>
                    <?php print __($guid, "Date") ?>
                </span>
            </th>
            <th>
                <?php print __($guid, "Actions") ?>
            </th>
        </tr>
        <?php
        if ($result->rowCount() > 0) {
            $maxNameLength = 50;
            $maxDescriptionLength = 100;
            while ($row = $result->fetch()) {
                $class = "even";
                switch ($row["status"]) {
                    case "Unassigned":
                        $class = "error";
                        break;
                    case "Pending":
                        $class = "warning";
                        break;
                    case "Resolved":
                        $class = "success";
                        break;
                }
                ?>
                <tr class='<?php print $class ?>'>
                    <td style='text-align: center;'>
                        <b><?php print intval($row["issueID"]) ?></b>
                    </td>
                    <td>
                        <b>
                        <?php
                            $title = $row["issueName"];
                            if (strlen($title) > $maxNameLength) {
                                $titleSplit = explode(" ", $title);
                                $title = "";
                                $totalLength = 0;
                                foreach ($titleSplit as $titleBit) {
                                    if (($totalLength + strlen($titleBit)) > $maxNameLength) {
                                        $totalLength += strlen($titleBit) + 1;
                                        $title .= $titleBit . " ";
                                    }
                                }
                                $title = substr($title, 0, -1);
                            }
                            print __($guid, $title);
                        ?> 
                        </b>
                        </br>
                        <span style='font-size: 85%; font-style: italic'>
                        <?php
                            $description = strip_tags($row["description"]);
                            if (strlen($title) > $maxDescriptionLength) {
                                $descriptionSplit = explode(" ", $description);
                                $description = "";
                                $totalLength = 0;
                                foreach ($descriptionSplit as $descriptionBit) {
                                    if (($totalLength + strlen($descriptionBit)) > $maxDescriptionLength) {
                                        $totalLength += strlen($descriptionBit) + 1;
                                        $description .= $descriptionBit . " ";
                                    }
                                }
                                $description = substr($description, 0, -1);
                            }
                            print __($guid, $description);
                        ?>
                        </span>
                    </td>
                    <td>
                    <?php
                        $name = formatName($row['title'], $row['preferredName'], $row['surname'], $row["nameCategory"], FALSE, FALSE);
                    ?>
                    </td>
                </tr>
                <?php
            }
        } else {
        ?>
            <tr>
                <td colspan='<?php print ($renderPriority && $renderCategory ? 7 : ($renderPriority || $renderCategory ? 6 : 5)) ?>'>
                    <?php print __($guid, "There are no records to display.") ?>
                </td>
            </tr>
        <?php
        }
        ?>
    </table>

    <?php

}

?>