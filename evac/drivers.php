<?php
include "auth.php";
$page_title = "Drivers";
$msg = "";

// INSERT
if (isset($_POST['add'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO Driver (Name, License_No, Experience_Years) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $_POST['name'], $_POST['license'], $_POST['exp']);
        $stmt->execute();
        $msg = "<div class='ok'>Driver added.</div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not add driver: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM Driver WHERE Driver_ID = ?");
        $stmt->bind_param("i", $_GET['delete']);
        $stmt->execute();
        $msg = "<div class='ok'>Driver deleted.</div>";
    } catch (mysqli_sql_exception $e) {
        // happens when the driver already has dispatches (foreign key)
        $msg = "<div class='error'>Cannot delete this driver, he/she is used in a dispatch record.</div>";
    }
}

include "header.php";
echo $msg;
?>
<form method="post" class="box">
    <b>Add a new driver</b>
    <label>Name</label><input type="text" name="name" required>
    <label>License number</label><input type="text" name="license" required>
    <label>Experience (years)</label><input type="number" name="exp" min="0" required>
    <input type="submit" name="add" value="Add driver">
</form>

<table>
<tr><th>ID</th><th>Name</th><th>License No</th><th>Experience</th><th>Action</th></tr>
<?php
$res = $conn->query("SELECT * FROM Driver ORDER BY Driver_ID");
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Driver_ID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['License_No']) . "</td>";
    echo "<td>" . $row['Experience_Years'] . " years</td>";
    echo "<td><a href='edit_driver.php?id=" . $row['Driver_ID'] . "'>Edit</a> | ";
    echo "<a href='drivers.php?delete=" . $row['Driver_ID'] . "' onclick=\"return confirm('Delete this driver?')\">Delete</a></td>";
    echo "</tr>";
}
?>
</table>
<?php include "footer.php"; ?>
