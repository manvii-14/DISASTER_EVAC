
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
        $msg = "<div class='error'>Cannot delete this driver, he/she is used in a dispatch record.</div>";
    }
}

include "header.php";

$res = $conn->query("SELECT * FROM Driver ORDER BY Driver_ID");
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">TEAM MANAGEMENT</p>
        <h2>Driver records</h2>
        <p>Manage driver details, driving licences and professional experience.</p>
    </div>
    <div class="page-count">
        <span>Total drivers</span>
        <strong><?php echo $res ? $res->num_rows : 0; ?></strong>
    </div>
</section>

<?php echo $msg; ?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Register a driver</h3>
            <p>Add a driver to the emergency transport team.</p>
        </div>
        <span class="panel-step">NEW RECORD</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="driver-name">Full name</label>
                <input id="driver-name" type="text" name="name"
                       placeholder="Enter driver's name" required>
            </div>

            <div class="form-field">
                <label for="driver-license">License number</label>
                <input id="driver-license" type="text" name="license"
                       placeholder="Enter license number" required>
            </div>

            <div class="form-field">
                <label for="driver-exp">Experience (years)</label>
                <input id="driver-exp" type="number" name="exp"
                       min="0" placeholder="Years of experience" required>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="add" value="Register driver">
        </div>
    </form>
</section>

<section class="panel victim-records-panel">
    <div class="panel-heading">
        <div>
            <h3>Registered drivers</h3>
            <p>View driver information and manage existing records.</p>
        </div>
        <span class="record-count">
            <?php echo $res ? $res->num_rows : 0; ?> records
        </span>
    </div>

    <div class="table-scroll">
        <table class="records-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Driver</th>
                    <th>License number</th>
                    <th>Experience</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && $res->num_rows > 0) { ?>
                    <?php while ($row = $res->fetch_assoc()) { ?>
                        <tr>
                            <td class="record-id">
                                <?php echo htmlspecialchars((string)$row['Driver_ID']); ?>
                            </td>
                            <td class="person-name">
                                <?php echo htmlspecialchars($row['Name']); ?>
                            </td>
                            <td>
                                <span class="license-value">
                                    <?php echo htmlspecialchars($row['License_No']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="experience-value">
                                    <?php echo htmlspecialchars((string)$row['Experience_Years']); ?>
                                    <?php echo (int)$row['Experience_Years'] === 1 ? 'year' : 'years'; ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <a class="action-link"
                                   href="edit_driver.php?id=<?php echo urlencode((string)$row['Driver_ID']); ?>">
                                    Edit
                                </a>
                                <span class="action-separator">·</span>
                                <a class="action-link action-delete"
                                   href="drivers.php?delete=<?php echo urlencode((string)$row['Driver_ID']); ?>"
                                   onclick="return confirm('Delete this driver?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" class="empty-table">
                            No drivers registered yet. Add a driver using the form above.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>
