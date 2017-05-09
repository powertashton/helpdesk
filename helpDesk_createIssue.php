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

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_createIssue.php')) {
    print "<div class='error'>";
        print "You do not have access to this action.";
    print "</div>";
} else {
    print "<div class='trail'>" ;
        print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Create Issue') . "</div>" ;
    print "</div>" ;
    
    if (isset($_GET['return'])) {
        $editLink = "";
        if (isset($_GET["issueID"])) {
            $editLink = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=modules/Help Desk/helpDesk_issueDiscuss.php&issueID=" . $_GET["issueID"];
        }
        returnProcess($guid, $_GET["return"], $editLink, null);
    }

    $form = Form::create('issueFilters', $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"]);

    $row = $form->addRow();
        $row->addLabel("issueNameLabel", "Issue Name *");
        $row->addTextField("issueName")->maxlength(55)->setRequired(true);

    $row = $form->addRow();
        $column = $row->addColumn()->setClass('');
        $column->addLabel("descriptionLabel", "Description *");
        $column->addEditor("description", $guid)->setRequired(true)->showMedia(true)->setRows(10);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();


    print $form->getOutput();

}
?>

<!-- <form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/Help Desk/helpDesk_createIssueProcess.php" ?>">
            <table class='smallIntBorder' cellspacing='0' style="width: 100%">
                <tr>
                    <td>
                        <b>
                            <?php print __($guid, "Issue Name") . " *"; ?>
                        </b>
                        <br/>
                        <span style='font-size: 90%'>
                            <i>
                            <?php
                                print __($guid, "Maximum 55 characters.");
                            ?>
                            </i>
                        </span>
                    </td>
                    <td class="right">
                        <input name="name" id="name" maxlength=55 value="" type="text" style="width: 300px">
                        <script type="text/javascript">
                            var name2 = new LiveValidation('name');
                            name2.add(Validate.Presence);
                            name2.add(Validate.Length, { minimum: 2, maximum: 55 });
                        </script>
                    </td>
                </tr>
                <tr>
                    <td class='right' colspan=2>
                        <input type='submit' value='<?php print __($guid, 'Go'); ?>'/>
                    </td>
                </tr>
            </table>
        </form>  --> 