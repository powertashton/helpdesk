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

    //Relation Filter
    //All Filters: All, Assigned to me, My Issues
    $relationFilters = array();

    $relationFilter = null;
    if (isset($_POST['relationFilter'])) {
        $relationFilter = $_POST['relationFilter'];
    }

    if ($relationFilter == null || $relationFilter == "") {
        $relationFilter = $relationFilters[0];
    }

    //Status Filter
    //All Filters: All, Unassigned, Pending, Resolved, Unassigned and Pending, Pending and Resolved
    $statusFilters = array();

    //Category Filter
    //All Filters: Get from settings
    $categoryFilters = array();

    //Priority Filter
    //All Filters: Get from settings
    $priorityFilters = array();

    //ID Filter
    //Positive Integer inputed by User

    //Year Filter
    //All Filters: Get from gibbonSchoolYear

    print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>"; ?>
        <table class='noIntBorder' cellspacing='0' style='width: 100%'>
            <tr>
                <td> 
                    <b><?php echo __($guid, 'Status Filter') ?> *</b><br/>
                </td>
                <td class="right">
                    <?php
                    echo "<select name='statusFilter' id='statusFilter' style='width:302px'>";
                        foreach($statuses as $status) {
                            $selected = "";
                            if ($status == $statusFilter) {
                                $selected = "selected";
                            }
                            echo "<option $selected value='$status'>" . __($guid, $status) . '</option>';
                        }
                    echo '</select>';
                    ?>
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