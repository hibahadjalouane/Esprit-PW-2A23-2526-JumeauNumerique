<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/ConsultationController.php';

$controller = new ConsultationController($conn);

// get id from URL
$id = $_GET['id'] ?? null;

if (!$id) {
    die("❌ No consultation ID provided");
}

// get consultation data
$sql = "SELECT * FROM consultation WHERE id_consultation = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultation) {
    die("❌ Consultation not found");
}

// update logic
$message = "";

if (isset($_POST['update'])) {

    $data = [
        "id_consultation" => $id,
        "date_consultation" => $_POST['date_consultation'],
        "motif" => $_POST['motif'],
        "diagnostic" => $_POST['diagnostic'],
        "notes" => $_POST['notes']
    ];

    $result = $controller->update($data);

    if ($result) {
        $message = "✅ Consultation updated successfully";
    } else {
        $message = "❌ Update failed";
    }
}

?>

<h2>Edit Consultation</h2>

<p style="color:green;">
    <?= $message ?>
</p>

<form method="POST">

    <label>Date</label><br>
    <input type="date" name="date_consultation"
           value="<?= $consultation['date_consultation'] ?>" required><br><br>

    <label>Motif</label><br>
    <input type="text" name="motif"
           value="<?= $consultation['motif'] ?>" required><br><br>

    <label>Diagnostic</label><br>
    <input type="text" name="diagnostic"
           value="<?= $consultation['diagnostic'] ?>" required><br><br>

    <label>Notes</label><br>
    <input type="text" name="notes"
           value="<?= $consultation['notes'] ?>"><br><br>

    <button type="submit" name="update">Update Consultation</button>

</form>