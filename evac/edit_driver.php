<?php
include "auth.php";
$page_title = "Edit Driver";
$msg = "";
$id = (int)$_GET['id'];

// UPDATE
if (isset($_POST['update'])) {
    try {
        $stmt = $conn->prepare("UPDATE Driver SET Name = ?, License_No = ?, Experience_Years = ? WHERE Driver_ID = ?");
        $stmt->bind_param("ssii", $_POST['name'], $_POST['license'], $_POST['exp'], $id);
        $stmt->execute();
        $msg = "<div class='ok'>Driver updated. <a href='drivers.php'>Back to list</a></div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not update: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$stmt = $conn->prepare("SELECT * FROM Driver WHERE Driver_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$d = $stmt->get_result()->fetch_assoc();

include "header.php";
echo $msg;
if (!$d) {
    echo "<p>Driver not found.</p>";
    include "footer.php";
    exit;
}
?>
<form method="post" class="box">
    <label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($d['Name']); ?>" required>
    <label>License number</label><input type="text" name="license" value="<?php echo htmlspecialchars($d['License_No']); ?>" required>
    <label>Experience (years)</label><input type="number" name="exp" value="<?php echo $d['Experience_Years']; ?>" required>
    <input type="submit" name="update" value="Update driver">
</form>
<a href="drivers.php">Back</a>
<?php include "footer.php"; ?>
