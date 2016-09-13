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

include "../../functions.php";
include "../../config.php";

include "./moduleFunctions.php";

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Help Desk/helpDesk_settings.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Help Desk/helpDesk_settings.php") == FALSE) {
    //Fail 0
    $URL .= "&return=error0" ;
    header("Location: {$URL}");
} else {

    $allowedRIP = array("Everyone", "Related", "Owner", "No one");
    $notAllowed = false;

    try {
        $sql = "SELECT name FROM gibbonSetting WHERE scope = 'Help Desk'";
        $result = $connection2->prepare($sql);
        $result->execute();

        while ($row = $result->fetch()) {
            if (isset($_POST[$row["name"]])) {
                $value = $_POST[$row["name"]];
                if ($row["name"] == "resolvedIssuePrivacy") {
                    if (!in_array($value, $allowedRIP)) {
                        $notAllowed = true;
                        continue;
                    }
                }

                $data2 = array("name" => $row["name"], "value" => $value);
                $sql2 = "UPDATE gibbonSetting SET value=:value WHERE name=:name AND scope = 'Help Desk'";
                $result2 = $connection2->prepare($sql2);
                $result2->execute($data2);
            }
        }

        $URL .= "&return=success" . ($notAllowed ? "1" : "0");
        header("Location: {$URL}");
        exit();
    } catch (PDOException $e) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
        exit();
    }

    header("Location: {$URL}");
    exit();
}
?>
