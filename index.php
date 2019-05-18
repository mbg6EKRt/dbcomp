<?php

require_once('db.php');
require_once('utils.php');

echo "<style>";
echo "h1 { margin: 40px 0 0 0; }";
echo "h3 { margin: 20px 0; }";
echo "</style>";
echo "Note: the idea is make the left table like the right table. So we need to suggest changes we need to make to the left table in order to make it like the right table. For example, if the left table does not exist and the right table does, then the suggested change should be a CREATE TABLE statement based on the table on the right. Columns that exist in the table on the right but not on the left should be added to the table on the left by a ALTER TABLE statement. Columns that exist in the table on the left but not on the table on the right should be dropped from the left table via an ALTER TABLE statement. Finally, tables that exist on the left but on the right should be dropped from the left via a DROP TABLE statement.";
echo "<table cellspacing='0' cellpadding='0' width='100%'>";

$db1Name = 'aiv2_server';
$db2Name = 'ai';

$db1 = new db('localhost', 'root', 'root', $db1Name);
$db2 = new db('localhost', 'root', 'root', $db2Name);

if ($db1Tables = $db1->query("SHOW TABLES"))
{
    if ($db2Tables = $db2->query("SHOW TABLES"))
    {
        $db1Tables = $db1->extractColumn($db1Tables, "Tables_in_{$db1Name}");
        $db2Tables = $db1->extractColumn($db2Tables, "Tables_in_{$db2Name}");

        //debug($db1Tables);
        //debug($db2Tables);

        $allTables = array_merge($db1Tables, $db2Tables);
        $allTables = array_unique($allTables);
        sort($allTables);

        $allChanges = Array();

        //debug($allTables);

        foreach ($allTables as $table)
        {
            $db1TableInfo = Array();
            $db2TableInfo = Array();

            if (in_array($table, $db1Tables)) $db1TableInfo = $db1->query("DESCRIBE `{$table}`");
            if (in_array($table, $db2Tables)) $db2TableInfo = $db2->query("DESCRIBE `{$table}`");

            $db1TableColumnCount = count($db1TableInfo);
            $db2TableColumnCount = count($db2TableInfo);

            echo "<tr><td colspan='2'><h1>{$table}</h1></td></tr>";
            echo "<tr><td width='50%'><h3>{$db1Name} ({$db1TableColumnCount} columns)</h3></td><td><h3>{$db2Name} ({$db2TableColumnCount} columns)</h3></td></tr><tr>";

            if (in_array($table, $db1Tables))
            {
                echo "<td valign='top'>";
                displayTable($db1TableInfo);
                echo "</td>";
            }
            else echo "<td></td>";

            if (in_array($table, $db2Tables))
            {
                echo "<td valign='top'>";
                displayTable($db2TableInfo);
                echo "</td>";
            }
            else echo "<td></td>";

            // Check if table exists in db1 but not db2

            $result = $db1->checkForDropTable($table, $db1Tables, $db2Tables);

            if ($result == FALSE)
            {
                // Check if table exists in db2 but not db1

                $result = $db2->checkForCreateTable($table, $db1Tables, $db2Tables);

                if ($result == FALSE)
                {
                    // Check if table exists in both db1 and db2

                    if (in_array($table, $db1Tables) && in_array($table, $db2Tables))
                    {
                        // Check if there is at least one matching column between the two tables
                        // If not one column matches, then suggest to drop the db1 table and create the db2 table

                        $result = $db2->checkForAllColumns($db1TableInfo, $db2TableInfo);

                        if ($result == FALSE)
                        {
                            // Check for columns to drop and columns to add

                            $result = $db2->checkForColumnsToDropAndCreate($table, $db1TableInfo, $db2TableInfo);
                        }
                    }
                }
            }

            if ($result != FALSE)
            {
                $allChanges[] = $result;

                echo "<tr><td colspan='2'><h3>Suggested Changes</h3></td><td></td></tr>";
                echo "<tr><td colspan='2'>{$result}</td><td></td></tr>";
            }

            echo "</tr>";
        }

        $allChanges = "<br /><br />".implode("<br /><br />", $allChanges);

        echo "<tr><td colspan='2'><h1>All Suggested Changes</h1></td><td></td></tr>";
        echo "<tr><td colspan='2'>{$allChanges}</td><td></td></tr>";
    }
}

echo "</body></html>";

function displayTable($table)
{
    echo "<table cellspacing='0' cellpadding='0' width='100%'>";

    echo "<tr>";
    foreach ($table[0] as $column => $value) echo "<td><strong>{$column}</strong></td>";
    echo "</tr>";

    foreach($table as $field)
    {
        echo "<tr>";
        foreach ($field as $column => $value) echo "<td>{$value}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$db1->close();
$db2->close();
