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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Tables\DataTable;
use Gibbon\Tables\Action;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Module\HelpDesk\Domain\IssueDiscussGateway;
use Gibbon\Module\HelpDesk\Domain\IssueGateway;
use Gibbon\Module\HelpDesk\Domain\TechGroupGateway;
use Gibbon\Module\HelpDesk\Domain\TechnicianGateway;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\FacilityGateway;
use Gibbon\View\View;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Discuss Issue'));

if (!isModuleAccessible($guid, $connection2)) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $issueID = $_GET['issueID'] ?? '';

    $issueGateway = $container->get(IssueGateway::class);
    $issue = $issueGateway->getByID($issueID);

    if (empty($issueID) || empty($issue)) {
        $page->addError(__('No Issue Selected.'));
    } else {
        //Set up gateways
        $techGroupGateway = $container->get(TechGroupGateway::class);
        $technicianGateway = $container->get(TechnicianGateway::class);

        //Information about the current user
        $gibbonPersonID = $gibbon->session->get('gibbonPersonID');
        $isPersonsIssue = ($issue['gibbonPersonID'] == $gibbonPersonID);
        $isTechnician = $technicianGateway->getTechnicianByPersonID($gibbonPersonID)->isNotEmpty();
        $isRelated = $issueGateway->isRelated($issueID, $gibbonPersonID);
        $hasViewAccess = $techGroupGateway->getPermissionValue($gibbonPersonID, 'viewIssue');
        $hasFullAccess = $techGroupGateway->getPermissionValue($gibbonPersonID, 'fullAccess');

        //Information about the issue's technician
        $technician = $technicianGateway->getTechnician($issue['technicianID']);
        $technician = $technician->isNotEmpty() ? $technician->fetch() : [];
        $hasTechAssigned = !empty($technician);
        $isResolved = ($issue['status'] == 'Resolved');

        $allowed = $isRelated
            || (!$hasTechAssigned && $isTechnician) 
            || $hasViewAccess;

        $privacySetting = $issue['privacySetting'];
        if ($isResolved && !$hasViewAccess) {
            if ($privacySetting == 'No one') {
                $allowed = false;
            } else if ($privacySetting == 'Related' && !$isRelated) {
                $allowed = false;
            }
            else if ($privacySetting == 'Owner' && !$isPersonsIssue) {
                $allowed = false;
            } else if ($privacySetting == 'Everyone') {
                $allowed = true;
            }
        }

        if ($allowed) {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }
        
            $createdByShow = ($issue['createdByID'] != $issue['gibbonPersonID']);
            
            $userGateway = $container->get(UserGateway::class);
            $owner = $userGateway->getByID($issue['gibbonPersonID']);

            $facilityGateway = $container->get(FacilityGateway::class);
            $facility = $facilityGateway->getByID($issue['gibbonSpaceID']);

            $detailsData = array(
                'issueID' => $issueID,
                'owner' => Format::nameLinked($owner['gibbonPersonID'], $owner['title'] , $owner['preferredName'] , $owner['surname'] , 'Staff'),
                'technician' => $hasTechAssigned ? Format::name($technician['title'] , $technician['preferredName'] , $technician['surname'] , 'Student') : __('Unassigned'),
                'date' => Format::date($issue['date']),
                'privacySetting' => $issue['privacySetting'],
                'facility' => empty($facility) ? __('N/A') : $facility['name'],
            );

            $tdWidth = count($detailsData);
            if ($createdByShow) {
                $tdWidth++;
            }

            $tdWidth = 100 / $tdWidth;
            $tdWidth .= '%';

            $table = DataTable::createDetails('details');
            $table->setTitle($issue['issueName']);            

            //TODO: Double check these permission
            if ($isResolved) {
                if ($isPersonsIssue || ($isRelated && $techGroupGateway->getPermissionValue($gibbonPersonID, 'reincarnateIssue'))) {
                    $table->addHeaderAction('reincarnate', __('Reincarnate'))
                            ->setIcon('reincarnate')
                            ->directLink()
                            ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_reincarnateProcess.php')
                            ->addParam('issueID', $issueID);
                }
            } else {
                if (!$hasTechAssigned) {
                     if ($techGroupGateway->getPermissionValue($gibbonPersonID, 'acceptIssue') && !$isPersonsIssue) {
                        $table->addHeaderAction('accept', __('Accept'))
                                ->setIcon('page_new')
                                ->directLink()
                                ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_acceptProcess.php')
                                ->addParam('issueID', $issueID);
                    }
                    if ($techGroupGateway->getPermissionValue($gibbonPersonID, 'assignIssue') && (!$isPersonsIssue || $hasFullAccess)) {
                        $table->addHeaderAction('assign', __('Assign'))
                                ->setIcon('attendance')
                                ->modalWindow()
                                ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_assign.php')
                                ->addParam('issueID', $issueID);
                    }
                } else {
                    $table->addHeaderAction('refresh', __('Refresh'))
                            ->setIcon('refresh')
                            ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_discussView.php')
                            ->addParam('issueID', $issueID);

                    if ($techGroupGateway->getPermissionValue($gibbonPersonID, 'reassignIssue') && (!$isPersonsIssue || $hasFullAccess)) {
                        $table->addHeaderAction('reassign', __('Reassign'))
                                ->setIcon('attendance')
                                ->modalWindow()
                                ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_assign.php')
                                ->addParam('issueID', $issueID);
                    }
                }

                if ($isPersonsIssue || ($isRelated && $techGroupGateway->getPermissionValue($gibbonPersonID, 'resolveIssue'))) {
                    $table->addHeaderAction('resolve', __('Resolve'))
                            ->setIcon('iconTick')
                            ->directLink()
                            ->setURL('/modules/' . $gibbon->session->get('module') . '/issues_resolveProcess.php')
                            ->addParam('issueID', $issueID);
                }
            }

            $table->addColumn('issueID', __('ID'))
                    ->width($tdWidth)
                    ->format(Format::using('number', ['issueID', 0]));

            $table->addColumn('owner', __('Owner'))
                    ->width($tdWidth);

            $table->addColumn('technician', __('Technician'))
                    ->width($tdWidth);

            $table->addColumn('date', __('Date'))
                    ->width($tdWidth);

            $table->addColumn('facility', __('Facility'))
                    ->width($tdWidth);

            if ($createdByShow) {
                $createdBy = $userGateway->getByID($issue['createdByID']);
                $detailsData['createdBy'] = Format::name($createdBy['title'] , $createdBy['preferredName'] , $createdBy['surname'] , 'Student');
                $table->addColumn('createdBy', __('Created By'))
                    ->width($tdWidth);
            }

            $table->addColumn('privacySetting', __('Privacy'))
                    ->width($tdWidth)
                    ->format(function($row) use ($gibbon, $isPersonsIssue, $hasFullAccess) {
                        if ($isPersonsIssue || $hasFullAccess) {
                            return Format::link('./index.php?q=/modules/' . $gibbon->session->get('module') . '/issues_discussEdit.php&issueID='. $row['issueID'], __($row['privacySetting']));
                        } else {
                            return __($row['privacySetting']);
                        }
                    });

            $detailsData['description'] = $issue['description'];
            $table->addColumn('description', __('Description'))
                    ->width('100%');

            echo $table->render([$detailsData]);

            /*
            //Description Table
            $table = DataTable::createDetails('description');
            $table->setTitle(__('Description'));

            //TODO: Can this be simplified?
            
            $table->addColumn('description')
                    ->width('100%');

            echo $table->render([$issue]);
            */
           
            $form = Form::create('issueDiscuss',  $gibbon->session->get('absoluteURL') . '/modules/' . $gibbon->session->get('module') . '/issues_discussPostProccess.php?issueID=' . $issueID, 'post');
            $form->addHiddenValue('address', $gibbon->session->get('address'));
            $row = $form->addRow();
            $col = $row->addColumn();
                $col->addHeading(__('Comments'))->addClass('inline-block');
               
            if ($issue['status'] == 'Pending' && ($isRelated || $hasFullAccess)) {
                $col->addWebLink('<img title="'.__('Add Comment').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/plus.png" />')->addData('toggle', '.comment')->addClass('floatRight');
                $row = $form->addRow()->setClass('comment hidden flex flex-col sm:flex-row items-stretch sm:items-center');
                    $column = $row->addColumn();
                    $column->addLabel('comment', __('Comment'));
                    $column->addEditor('comment', $guid)
                        ->setRows(5)
                        ->showMedia()
                        ->isRequired();
                
                $row = $form->addRow()->setClass('comment hidden flex flex-col sm:flex-row items-stretch sm:items-center');;
                    $row->addFooter();
                    $row->addSubmit();
            }

            $issueDiscussGateway = $container->get(IssueDiscussGateway::class);
            $logs = $issueDiscussGateway->getIssueDiscussionByID($issueID)->fetchAll();

            if (count($logs) > 0) {
                array_walk($logs, function (&$discussion, $key) use ($issue) {
                    if ($discussion['gibbonPersonID'] == $issue['gibbonPersonID']) {
                        $discussion['type'] = 'Owner';
                    } else {
                        $discussion['type'] = 'Technician';
                    }
                });

                $form->addRow()
                    ->addContent('comments')
                    ->setContent($page->fetchFromTemplate('ui/discussion.twig.html', [
                        'title' => __(''),
                        'discussion' => $logs
                    ])); 
            }

            if (count($form->getRows()) > 1) {
                echo $form->getOutput();
            }
        } else {
            $page->addError(__('You do not have access to this action.'));
        }
    }
}
?>
