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
        $sql = "SELECT groupName, viewIssue, viewIssueStatus, assignIssue, acceptIssue, resolveIssue, createIssueForOther, fullAccess, reassignIssue, reincarnateIssue FROM helpDeskTechGroups WHERE groupID=:groupID";
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

/*
Description: THis function retunrs a formatted value of a Help Desk Setitng, when given the name of the setting.
Arguments:
    A PDO connection.
    The name of the Help Desk setting.
Returns:
    An array: If the setting is either issuePriority or issueCategory.
    A string: If the setting is any other valid setting name.
    null: If the setting name is invalid, a database error occured, or the setting has no value.
*/
function getHelpDeskSetting($connection2, $setting) {
    $return = null;

    try {
        $data = array("name" => $setting);
        $sql = "SELECT value FROM gibbonSetting WHERE name =:name AND scope = 'Help Desk'";
        $result = $connection2->prepare($sql);
        $result->execute($data);

        $return = $result->fetch()["value"];

        if ($return == "") {
            return null;
        }

        if ($setting == "issuePriority" || $setting == "issueCategory") {
            foreach(explode(",", $return) as $split) {
                if($split != "" || $split != null) {
                    $splits[] =  $split;
                }
            }
            $return = $splits;
        }
    } catch (PDOException $e) {
    }
    return $return;
}

//-Miscellaneous Functions-

/*
Description: This function will truncate a string by seperating the string into words and rebuilding the string until the max length is exceeded.
Arguments:
    A string
    An integer
Returns:
    A string
*/

function smartTruncate($string, $maxLength) {
    $stringSplit = explode(" ", $string);
    $string = "";
    $totalLength = 0;
    foreach ($stringSplit as $stringBit) {
        if (($totalLength + strlen($stringBit)) > $maxLength) {
            $totalLength += strlen($stringBit) + 1;
            $string .= $stringBit . " ";
        }
    }
    return substr($string, 0, -1);
}

?>
