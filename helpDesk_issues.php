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
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Manage Issues') . "</div>";
    print "</div>";

    print "<h3>";
        print __($guid, "Filters");
    print "</h3>";

    $technician = getTechnician($connection2, $_SESSION[$guid]["gibbonPersonID"]);
    $isTech = $technician != null;
    if ($isTech) {
        $techGroup = getTechnicianGroup($connection2, $technician['groupID']);
        $fullAccess = $techGroup["fullAccess"];
    }
    $data = array();
    $sql = "SELECT * FROM (SELECT helpDeskIssue.issueID, helpDeskIssue.technicianID, helpDeskIssue.gibbonPersonID, helpDeskIssue.issueName, helpDeskIssue.description, helpDeskIssue.date, helpDeskIssue.status, helpDeskIssue.category, helpDeskIssue.priority, helpDeskIssue.gibbonSchoolYearID, helpDeskIssue.createdByID, helpDeskIssue.privacySetting, gibbonPerson.preferredName, gibbonPerson.surname, gibbonPerson.title, gibbonRole.category as nameCategory FROM helpDeskIssue JOIN gibbonPerson ON (helpDeskIssue.gibbonPersonID = gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID)";
    $where = " WHERE";

    //Relation Filter
    //All Filters: All, Assigned to me, My Issues
    $relationFilters = array();
    $relationFilter = null;

    if ($isTech) {
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
            $where .= " helpDeskIssue.gibbonPersonID=:gibbonPersonID AND";
            break;
        case "AM":
            $data["technicianID"] = $technician["technicianID"];
            $where .= " helpDeskIssue.technicianID=:technicianID AND";
            break;
    }

    //Status Filter
    //All Filters: All, Unassigned, Pending, Resolved, Unassigned and Pending, Pending and Resolved
    $statusFilters = array();
    $statusFilter = null;
    if ($isTech) {
        $viewIssueStatus = $techGroup['viewIssueStatus'];
        if ($fullAccess) {
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
        $where .= " (";

        if (strpos($statusFilter, "U") !== false) {
            $data["status0"] = "Unassigned";
            $where .= "helpDeskIssue.status=:status0 OR ";
        }

        if (strpos($statusFilter, "P") !== false) {
            $data["status1"] = "Pending";
            $where .= "helpDeskIssue.status=:status1 OR ";
        }

        if (strpos($statusFilter, "R") !== false) {
            $data["status2"] = "Resolved";
            $where .= "helpDeskIssue.status=:status2 OR ";
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
        $where .= " category=:category AND";
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
        $where .= " priority=:priority AND";
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
        $where .= " issueID=:issueID AND";
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
        $where .= " gibbonSchoolYearID=:gibbonSchoolYearID AND";
    }

    if (substr($where, -6) == " WHERE") {
        $where = substr($where, 0, -6);
    } else {
        $where = substr($where, 0, -4);
    }

    $sql .= $where;

    $sql .= ") as t1 LEFT JOIN (";

    $sql .= "SELECT helpDeskIssue.issueID, gibbonPerson.preferredName as techPrefName, gibbonPerson.surname as techSurname, gibbonPerson.title as techTitle, gibbonRole.category as techNameCategory FROM helpDeskIssue JOIN helpDeskTechnicians ON (helpDeskIssue.technicianID = helpDeskTechnicians.technicianID) JOIN gibbonPerson ON (helpDeskTechnicians.gibbonPersonID = gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID)";

    $sql .= $where;
    $sql .= ") as t2 ON (t1.issueID = t2.issueID)";

    $sql .= " ORDER BY FIELD(status, 'Unassigned', 'Pending', 'Resolved'), ";
    if ($renderPriority) {
        $sql .= "FIELD(priority, ";
        foreach ($priorityFilters as $priority) {
            $sql .= "'" . $priority . "',";
        }
        $sql = substr($sql, 0, -1) . "), ";
    }
    $sql .= "date DESC, t1.issueID DESC";

    try {
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch(PDOException $e) {
        print $e;
    }

    $form = Form::create('issueFilters', $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"]);

    if (count($relationFilters) > 0) {
        $row = $form->addRow();
            $row->addLabel("relationFilterLabel", "Relation Filter")->description("Filter issues by your relation to it.");
            $row->addSelect("relationFilter")->fromArray($relationFilters);
    }

    $row = $form->addRow();
            $row->addLabel("statusFilterLabel", "Status Filter");
            $row->addSelect("statusFilter")->fromArray($statusFilters);

    if ($renderCategory) {
        $row = $form->addRow();
            $row->addLabel("categoryFilterLabel", "Category Filter");
            $row->addSelect("categoryFilter")->fromArray($categoryFilters);
    }

    if($renderPriority) {
        $row = $form->addRow();
            $row->addLabel("priorityFilterLabel", "Priority Filter");
            $row->addSelect("priorityFilter")->fromArray($priorityFilters);
    }

    $row = $form->addRow();
            $row->addLabel("yearFilterLabel", "Year Filter");
            $row->addSelect("yearFilter")->fromArray($yearFilters);

    $row = $form->addRow();
            $row->addLabel("IDFilterLabel", "ID Filter")->description("Filter issue by their unique ID. Set to -1 to disable the filter.");
            $row->addNumber("IDFilter");

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>"; ?>
        <table class='noIntBorder' cellspacing='0' style='width: 100%'>
            <?php
            if (count($relationFilters) > 0) {
            ?>
                <tr>
                    <td> 
                        <b><?php echo __($guid, 'Relation Filter') ?> *</b><br/>
                        <span style='font-size: 90%'>
                            <i>
                            <?php
                                print __($guid, "Filter issues by your relation to it.");
                            ?>
                            </i>
                        </span>
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
                    <span style='font-size: 90%'>
                        <i>
                        <?php
                            print __($guid, "Filter issue by their unique ID. Set to -1 to disable the filter.");
                        ?>
                        </i>
                    </span>
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
                    <td style="width:15%">
                        <b>
                        <?php
                            $name = formatName($row['title'], $row['preferredName'], $row['surname'], $row["nameCategory"], FALSE, FALSE);
                            print $name;
                        ?>
                        </b>
                        <?php 
                        if ($renderCategory) {
                        ?>
                            <br/>
                            <span style='font-size: 85%; font-style: italic'>
                            <?php
                                print $row["category"];
                            ?>
                            </span>
                        <?php
                        }
                        ?>
                    </td>
                    <?php 
                    if ($renderPriority) {
                    ?>
                        <td>
                            <b>
                            <?php
                                print $row["priority"];
                            ?>
                            </b>
                        </td>
                    <?php
                    }
                    ?>
                    <td style="width:15%">
                        <b>
                        <?php
                            if (isset($row['technicianID'])) {
                                $name = formatName($row['techTitle'], $row['techPrefName'], $row['techSurname'], $row["techNameCategory"], FALSE, FALSE);
                                print $name;
                            }
                        ?>
                        </b>
                    </td>
                    <td style='width:10%;'>
                        <b>
                        <?php
                            print $row["status"];
                        ?>
                        </b>
                        <br/>
                        <span style='font-size: 85%; font-style: italic'>
                        <?php
                            print $row["date"];
                        ?>
                        </span>
                    </td>
                    <td style='width:17%'>
                    <?php
                        //View, Edit, Assign/Reassign, Accept, Resolve, Reincarnate
                        $createView = false;
                        $createEdit = false;
                        $createAssign = false;
                        $createReassign = false;
                        $createAccept = false;
                        $createResolve = false;
                        $createReincarnate = false;

                        $isOwner = $row["gibbonPersonID"] == $_SESSION[$guid]["gibbonPersonID"];            

                        if ($row["status"] == "Unassigned") {
                            //View if: Owner or tech with permission
                            $createView = $isOwner;

                            if (!$createView && $isTech) {
                                $createView = $techGroup["viewIssue"] || $fullAccess;
                            }

                            //Edit if: Owner or tech with full access
                            $createEdit = $isOwner;

                            if (!$createEdit && $isTech) {
                                $createEdit = $fullAccess;
                            }

                            //Assign if: tech with permission
                            if ($isTech) {
                                $createAssign = $techGroup["assignIssue"] || $fullAccess;
                            }

                            //Accept if: tech with permission and not Owner
                            if ($isTech && !$isOwner) {
                                $createAccept = $techGroup["acceptIssue"] || $fullAccess;
                            }

                            //Resolve if: Owner or tech with full access
                            $createResolve = $isOwner;

                            if (!$createResolve && $isTech) {
                                $createResolve = $fullAccess;
                            }
                        } elseif ($row["status"] == "Pending") {
                            $assignedTech = false;
                            if ($isTech) {
                                $assignedTech = $row["technicianID"] == $technician["technicianID"];
                            }

                            //View if: Owner or assigned tech or tech with full access
                            $createView = $isOwner || $assignedTech;

                            if (!$createView && $isTech) {
                                $createView = $fullAccess;
                            }

                            //Edit if: Owner or tech with full access
                            $createEdit = $isOwner;

                            if(!$createEdit && $isTech) {
                                $createEdit = $fullAccess;
                            }

                            //Reassign if: tech with permission
                            if ($isTech) {
                                $createReassign = $techGroup["reassignIssue"] || $fullAccess;
                            }

                            //Resolve if: Owner or assigned tech or tech with full acccess
                            $createResolve = $isOwner || $assignedTech;

                            if (!$createResolve && $isTech) {
                                $createResolve = $fullAccess;
                            }
                        } elseif ($row["status"] == "Resolved") {
                            //View if: check privacy
                            $createView = $fullAccess || ($row["privacySetting"] == "Owner" && $isOwner) || ($row["privacySetting"] == "Related" && ($isOwner || $assignedTech)) || $row["privacySetting"] == "Everyone";

                            //Reincarnate, if: Owner or tech with permission
                            $createReincarnate = $isOwner;

                            if (!$createReincarnate && $isTech) {
                                $createReincarnate = $techGroup["reincarnateIssue"] || $fullAccess;
                            }
                        }

                        if ($createView) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueDiscuss.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, 'Open') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/zoom.png'/></a>"; 
                        }

                        if ($createEdit) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueEdit.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, 'Edit') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a>"; 
                        }

                        if ($createAssign || $createReassign) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueAssign.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, ($createAssign ? "Assign" : "Reassign")) . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>"; 
                        }

                        if ($createAccept) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueAcceptProcess.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, 'Accept') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>"; 
                        }

                        if ($createResolve) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueResolveProcess.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, 'Resolve') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/></a>"; 
                        }

                        if ($createReincarnate) {
                            print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/helpDesk_issueReincarnateProcess.php&issueID=". intval($row["issueID"]) . "'><img style='margin-left: 5px' title='" . __($guid, 'Reincarnate') . "' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/reincarnate.png'/></a>"; 
                        }

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