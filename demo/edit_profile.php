<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["userid"]) || $_SESSION["role"] != 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["userid"];
$success = "";
$error = "";

// Ενημέρωση στοιχείων
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $address = $_POST["address"];
    $phone_mobile = $_POST["phone_mobile"];
    $phone_home = $_POST["phone_home"];
    $contact_email = $_POST["contact_email"];

    $sql = "UPDATE users SET address=?, phone_mobile=?, phone_home=?, contact_email=? WHERE id=?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssssi", $address, $phone_mobile, $phone_home, $contact_email, $user_id);

    if ($stmt->execute()) {
        $success = "Τα στοιχεία ενημερώθηκαν επιτυχώς.";
    } else {
        $error = "Σφάλμα κατά την ενημέρωση.";
    }
}

// Ανάκτηση στοιχείων για προβολή
$sql = "SELECT * FROM users WHERE id=?";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Επεξεργασία Προφίλ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Επεξεργασία Προφίλ</h2>
    <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if ($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="post">
        <div class="form-group">
            <label>Διεύθυνση:</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Email Επικοινωνίας:</label>
            <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($user['contact_email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Κινητό Τηλέφωνο:</label>
            <input type="text" name="phone_mobile" class="form-control" value="<?= htmlspecialchars($user['phone_mobile'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Σταθερό Τηλέφωνο:</label>
            <input type="text" name="phone_home" class="form-control" value="<?= htmlspecialchars($user['phone_home'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Αποθήκευση</button>
    </form>
</body>
</html>
