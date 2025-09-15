<?php
require_once "../config.php"; 
session_start();

// Only secretary allowed
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: ../login_page.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
    $role = $_POST['role']; // student or teacher
    $fileTmp = $_FILES['jsonFile']['tmp_name'];

    if ($_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
        $message = "File upload error.";
    } else {
        $json = file_get_contents($fileTmp);
        $data = json_decode($json, true);

        if ($data === null) {
            $message = "Invalid JSON format.";
        } else {
            $inserted = 0;
            $stmt = $db->prepare("INSERT INTO users 
                (am, name, surname, password, email, role, mobile_telephone, landline_telephone, contact_email, father_name, street, number, city, postcode) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($data as $user) {
                $am = $user['am'] ?? 0;
                $name = $user['name'] ?? '';
                $surname = $user['surname'] ?? '';
                $email = $user['email'] ?? '';
                $mobile = $user['mobile_telephone'] ?? 0;
                $landline = $user['landline_telephone'] ?? 0;
                $contact_email = $user['contact_email'] ?? '';
                $father = $user['father_name'] ?? '';
                $street = $user['street'] ?? '';
                $number = $user['number'] ?? 0;
                $city = $user['city'] ?? '';
                $postcode = $user['postcode'] ?? 0;

                // auto-generate password
                $plainPassword = $surname . rand(100,999);
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

                $stmt->bind_param(
                    "isssssissssisi",
                    $am, $name, $surname, $hashedPassword, $email, $role,
                    $mobile, $landline, $contact_email, $father, $street, $number, $city, $postcode
                );

                if ($stmt->execute()) {
                    $inserted++;
                }
            }
            $message = "Imported $inserted $role(s) successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Import Results</title>
</head>
<body>
  <h1><?php echo htmlspecialchars($message); ?></h1>
  <a href="SecretaryDataInput.php">Επιστροφή</a>
</body>
</html>
