<?php
include "auth.php";
$page_title = "Edit Driver";
$msg = "";
$id = (int)($_GET['id'] ?? 0);

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
?>
    <section class="panel">
        <div class="empty-state">
            <h3>Driver not found</h3>
            <p>This driver record doesn't exist or may have been removed.</p>
            <a href="drivers.php" class="button-link">Back to drivers</a>
        </div>
    </section>
<?php
    include "footer.php";
    exit;
}
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">TEAM MANAGEMENT</p>
        <h2>Edit driver details</h2>
        <p>Update the driver's information and professional experience.</p>
    </div>

    <div class="page-count">
        <span>Driver ID</span>
        <strong>#<?php echo htmlspecialchars((string)$d['Driver_ID']); ?></strong>
    </div>
</section>

<section class="panel edit-form-panel">
    <div class="panel-heading">
        <div>
            <h3>Driver information</h3>
            <p>Make your changes below and save the updated record.</p>
        </div>
        <span class="panel-step">EDIT RECORD</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="edit-driver-name">Full name</label>
                <input
                    id="edit-driver-name"
                    type="text"
                    name="name"
                    value="<?php echo htmlspecialchars($d['Name']); ?>"
                    required
                >
            </div>

            <div class="form-field">
                <label for="edit-driver-license">License number</label>
                <input
                    id="edit-driver-license"
                    type="text"
                    name="license"
                    value="<?php echo htmlspecialchars($d['License_No']); ?>"
                    required
                >
            </div>

            <div class="form-field">
                <label for="edit-driver-exp">Experience (years)</label>
                <input
                    id="edit-driver-exp"
                    type="number"
                    name="exp"
                    min="0"
                    value="<?php echo htmlspecialchars((string)$d['Experience_Years']); ?>"
                    required
                >
            </div>
        </div>

        <div class="form-actions edit-form-actions">
            <a href="drivers.php" class="button-link button-secondary">Cancel</a>
            <input type="submit" name="update" value="Save changes">
        </div>
    </form>
</section>

<?php include "footer.php"; ?>
