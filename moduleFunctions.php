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

//-Technician Functions-

/*
Description: This function returns the technicianIDs, gibbonPersonIDs and groupIDs of all Technicians.
Arguments:
    A PDO connection.
Returns:
    A PDO result: If there are Technicians and no database errors occured.
    null: If there are no Technicians or a database error occured.
*/
function getAllTechnicians($connection2)
{
    try {
        $sql = "SELECT technicianID, gibbonPersonID, groupID FROM helpDeskTechnicians";
        $result = $connection2->prepare($sql);
        $result->execute();
        if ($result->rowCount() > 0) {
            return $result;
        }
    } catch (PDOException $e) {
    }

    return null;
}

/*
Description: This function returns the technicianID and groupID of a Technician with the inputed gibbonPersonID.
Arguments:
    A PDO connection.
    The gibbonPersonID of the Technician.
Returns:
    An array: If there is a Technician with the provided gibbonPersonID and no database errors occured.
    null: If there is no Technician with the provided gibbonPersonID, two technicians with the same gibbonPersonID were found or a databse error occured.
*/
function getTechnician($connection2, $gibbonPersonID)
{
    try {
        $data = array("gibbonPersonID" => $gibbonPersonID);
        $sql = "SELECT technicianID, groupID FROM helpDeskTechnicians WHERE gibbonPersonID=:gibbonPersonID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            return $result->fetch();
        }
    } catch (PDOException $e) {
    }

    return null;
}

/*
Description: This function is used to determine if a technicianID matches a Technician in the database.
Arguments:
    A PDO connection.
    The technicianID to be tested
Returns:
    true: If a Technician was found with the provided technicianID and no database errors occured.
    false: If a Technician was not found with the provided technicianID or a databse error occured.
*/
function technicianExists($connection2, $technicianID)
{
    try {
        $data = array("technicianID" => $technicianID);
        $sql = "SELECT gibbonPersonID FROM helpDeskTechnicians WHERE technicianID=:technicianID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            return true;
        }
    } catch (PDOException $e) {
    }

    return false;
}

/*
Description: This functions returns the surname, preferredName and title of a technician using the inputed technicianID.
Arguments:
    A PDO connection.
    The technicianID of the Technician.
Returns:
    An array: If there is a technician with the provided technicianID and no database errors occured.
    null: If no technician was found with the provided technicianId or a database error occured.
*/
function getTechnicianName($connection2, $technicianID) {
    try {
        $data = array("technicianID" => $technicianID);
        $sql = "SELECT surname, preferredName, title FROM gibbonPerson JOIN helpDeskTechnicians ON (gibbonPerson.gibbonPersonID = helpDeskTechnicians.gibbonPersonID) WHERE helpDeskTechnicians.technicianID=:technicianID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            return $result->fetch();
        }
    } catch (PDOException $e) {
    }

    return null;
}

//-Technician Group Functions-

/*
Description: This function returns the groupName and other permissions of a technician group using the inputed groupID.
Arguments:
    A PDO connection.
    The groupID of the Technician Group.
Returns:
    An array: If there is a Technician Group with the provided groupID and no database errors occured.
    null: If no Technician Group was found with the provided groupID or a database error occured.
*/
function getTechnicianGroup($connection2, $groupID)
{
    try {
        $data = array("groupID" => $groupID);
        $sql = "SELECT groupName, viewIssue, viewIssueStatus, assignIssue, acceptIssue, resolveIssue, createIssueForOther, fullAccess, reassignIssue, reincarnateIssue FROM helpDeskTechGroup WHERE groupID=:groupID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if ($result->rowCount() == 1) {
            return $result->fetch();
        }
    } catch (PDOException $e) {
    }

    return null;
}

//-Issue Functions-

/*
Description:
Arguments:
Returns:
*/


//-Help Desk Functions-

?>
