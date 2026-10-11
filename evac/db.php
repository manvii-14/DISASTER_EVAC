<?php
// connection to the database
// default XAMPP user is root with no password
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli("localhost", "root", "", "evac_db");

// prints any query result as an html table
function show_table($result) {
    if ($result->num_rows == 0) {
        echo "<p>No records found.</p>";
        return;
    }
    echo "<table><tr>";
    foreach ($result->fetch_fields() as $f) {
        echo "<th>" . htmlspecialchars($f->name) . "</th>";
    }
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '-') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>
