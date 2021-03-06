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

use Gibbon\Module\HelpDesk\Domain\TechGroupGateway;
use Gibbon\Module\HelpDesk\Domain\TechnicianGateway;

require_once '../../gibbon.php';

require_once './moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=/modules/Help Desk';

if (isActionAccessible($guid, $connection2, '/modules/Help Desk/helpDesk_manageTechnicianGroup.php')==false) {
    //Fail 0
    $URL .= '/issues_view.php&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    //Proceed!
    $techGroupGateway = $container->get(TechGroupGateway::class);

    if ($techGroupGateway->countAll() < 2) {
        $URL .= '/helpDesk_manageTechnicianGroup.php&return=errorA';
        header("Location: {$URL}");
        exit();
    }

    $groupID = $_GET['groupID'] ?? '';
    if (empty($groupID) || !$techGroupGateway->exists($groupID)) {
        $URL .= '/helpDesk_manageTechnicianGroup&return=error1';
        header("Location: {$URL}");
        exit();
    }

    $newGroupID = $_POST['group'] ?? '';
    if (empty($newGroupID) || !$techGroupGateway->exists($newGroupID) || $groupID == $newGroupID) {
        $URL .= "/helpdesk_technicianGroupDelete&groupID=$groupID&return=error1";
        header("Location: {$URL}");
        exit();
    }


    $technicianGateway = $container->get(TechnicianGateway::class);

    //TODO: Start transaction?
    if (!$technicianGateway->updateWhere(['groupID' => $groupID], ['groupID' => $newGroupID]) || !$techGroupGateway->delete($groupID)) {
        $URL .= "/helpdesk_technicianGroupDelete&groupID=$groupID&group=$group&return=error2";
        header("Location: {$URL}");
        exit();
    }

    $gibbonModuleID = getModuleIDFromName($connection2, 'Help Desk');

    setLog($connection2, $gibbon->session->get('gibbonSchoolYearID'), $gibbonModuleID, $gibbon->session->get('gibbonPersonID'), 'Technician Group Removed', array('newGroupID' => $newGroupID), null);

    //Success 0
    $URL .= '/helpDesk_manageTechnicianGroup.php&return=success0';
    header("Location: {$URL}");
    exit();
}
?>
