<?php
require_once "config.php"; 
session_start();

// Only secretary allowed
if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'secretary') {
    header("Location: login_page.php");
    exit();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['jsonFile'])) {
    $role = $_POST['role']; // student or teacher
    $fileTmp = $_FILES['jsonFile']['tmp_name'];

    if ($_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
        $message = "Σφάλμα στο ανέβασμα αρχείου.";
    } else {
        $json = file_get_contents($fileTmp);
        $data = json_decode($json, true);

        if ($data === null) {
            $message = "Λανθασμένη μορφή JSON.";
        } else {
            // Αν το JSON είναι ένα object, το βάζουμε σε array
            if (isset($data['am'])) {
                $data = [$data];
            }

            $inserted = 0;
            $skipped = 0;

            $stmt = $db->prepare("INSERT INTO users 
                (am, name, surname, password, email, role, mobile_telephone, landline_telephone, contact_email, father_name, street, number, city, postcode) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($data as $user) {
                $am            = $user['am'] ?? 0;
                $name          = $user['name'] ?? '';
                $surname       = $user['surname'] ?? '';
                $email         = $user['email'] ?? '';
                $mobile        = $user['mobile_telephone'] ?? '';
                $landline      = $user['landline_telephone'] ?? '';
                $contact_email = $user['contact_email'] ?? '';
                $father        = $user['father_name'] ?? '';
                $street        = $user['street'] ?? '';
                $number        = $user['number'] ?? '';
                $city          = $user['city'] ?? '';
                $postcode      = $user['postcode'] ?? '';

                // Αν δεν έχει email, skip
                if (empty($email)) {
                    $skipped++;
                    continue;
                }

                // auto-generate password
                $plainPassword  = $surname . rand(100, 999);
                $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

                $stmt->bind_param(
                    "isssssisssssss",
                    $am, $name, $surname, $hashedPassword, $email, $role,
                    $mobile, $landline, $contact_email, $father, $street, $number, $city, $postcode
                );

                try {
                    if ($stmt->execute()) {
                        $inserted++;
                    }
                } catch (mysqli_sql_exception $e) {
                    // Αν υπάρχει διπλό email -> skip
                    if ($e->getCode() == 1062) { 
                        $skipped++;
                    } else {
                        throw $e;
                    }
                }
            }

            $message = "Επιτυχής εισαγωγή $inserted $role(s). Παραλείφθηκαν $skipped εγγραφές.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <title>Αποτέλεσμα Εισαγωγής</title>
</head>
<body>
  <h1><?php echo htmlspecialchars($message); ?></h1>
  <a href="SecretaryDataInput.html">Επιστροφή</a>
</body>
</html>
