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

use Gibbon\Module\HelpDesk\Domain\IssueDiscussGateway;
use Gibbon\Module\HelpDesk\Domain\IssueGateway;
use Gibbon\Module\HelpDesk\Domain\TechGroupGateway;
use Gibbon\Module\HelpDesk\Domain\TechnicianGateway;

require_once '../../gibbon.php';

require_once './moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=/modules/' . $gibbon->session->get('module');

if (!isActionAccessible($guid, $connection2, '/modules/Help Desk/issues_view.php')) {
    $URL .= '/issues_view.php&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    $issueID = $_GET['issueID'] ?? '';

    $issueGateway = $container->get(IssueGateway::class);
    $issue = $issueGateway->getByID($issueID);

    if (empty($issueID) || empty($issue)) {
        $URL .= '/issues_view.php&return=error1';
        header("Location: {$URL}");
        exit();
    }

    $gibbonPersonID = $gibbon->session->get('gibbonPersonID');

    $techGroupGateway = $container->get(TechGroupGateway::class);

    if ($issueGateway->isRelated($issueID, $gibbonPersonID) || $techGroupGateway->getPermissionValue($gibbonPersonID, 'fullAccess')) {
      //Proceed!
        $URL .= "/issues_discussView.php&issueID=$issueID";

        if ($issue['status'] != 'Pending') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
            exit();
        }

        $comment = $_POST['comment'] ?? '';

        if (empty($comment)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        }

        try {
            $gibbonModuleID = getModuleIDFromName($connection2, 'Help Desk');
            if ($gibbonModuleID == null) {
                throw new PDOException('Invalid gibbonModuleID.');
            }

            $data = array('issueID' => $issueID, 'comment' => $comment, 'timestamp' => date('Y-m-d H:i:s'), 'gibbonPersonID' => $gibbonPersonID);
            $issueDiscussGateway = $container->get(IssueDiscussGateway::class);

            $issueDiscussID = $issueDiscussGateway->insert($data);
            if ($issueDiscussID === false) {
                throw new PDOException('Could not insert comment.');
            }
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }
       
        $technicianGateway = $container->get(TechnicianGateway::class);
        $technician = $technicianGateway->getTechnicianByPersonID($gibbonPersonID);

        $isTech = $technician->isNotEmpty() && ($issue['gibbonPersonID'] != $gibbonPersonID);

        $message = 'A new message has been added to Issue #';
        $message .= $issueID;
        $message .= ' (' . $issue['issueName'] . ').';

        $personIDs = $issueGateway->getPeopleInvolved($issueID);

        foreach ($personIDs as $personID) {
            if ($personID != $gibbonPersonID) {
                setNotification($connection2, $guid, $personID, $message, 'Help Desk', '/index.php?q=/modules/Help Desk/issues_discussView.php&issueID=' . $issueID);
            } 
        }

        $array = array('issueDiscussID' => $issueDiscussID);

        if ($isTech) {
            $array['technicianID'] = $technician->fetch()['technicianID'];
        } 

        setLog($connection2, $gibbon->session->get('gibbonSchoolYearID'), $gibbonModuleID, $gibbonPersonID, 'Discussion Posted', $array, null);

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit();
    } else {
        //Fail 0 aka No permission
        $URL .= '/issues_view.php&return=error0';
        header("Location: {$URL}");
        exit();
    }
}
?>
