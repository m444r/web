<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Αν είναι αλλαγή προθεσμίας
    if (isset($_POST['topic_id'], $_POST['deadline'])) {
        $topic_id = intval($_POST['topic_id']);
        $deadline = $_POST['deadline'];

        $stmt = $db->prepare("UPDATE topics SET deadline=? WHERE id=?");
        $stmt->bind_param("si", $deadline, $topic_id);

        if ($stmt->execute()) {
            header("Location: TeacherThesisList.php?update=success");
            exit;
        } else {
            die('Σφάλμα ενημέρωσης: ' . $stmt->error);
        }
    }

}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_topic_id'])) {
    $topic_id = intval($_POST['cancel_topic_id']);
    $teacher_id = $_SESSION['userid']; 

    // Έλεγχος αν το θέμα ανήκει στον καθηγητή
    $stmt = $db->prepare("SELECT id FROM topics WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $topic_id, $teacher_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $cancel_message = urlencode("Δεν έχεις δικαίωμα να ακυρώσεις αυτή τη διπλωματική.");
        header("Location: TeacherThesisList.php?");
    exit;
    } else {
        // Κλείνουμε το SELECT statement
        $stmt->close();

        // Ακύρωση της διπλωματικής
        $update = $db->prepare("UPDATE topics SET status = 'cancelled' WHERE id = ?");
        $update->bind_param("i", $topic_id);
        $update->execute();
        $update->close();

        // Διαγραφή τυχόν αιτήσεων για την τριμελή
        $del = $db->prepare("DELETE FROM committee_requests WHERE topic_id = ?");
        $del->bind_param("i", $topic_id);
        $del->execute();
        $del->close();

        // Προαιρετικά ανακατεύθυνση ή μήνυμα επιτυχίας
        header("Location: TeacherThesisList.php?cancel=success");
        exit;
    }

    $stmt->close();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_topic_delay_id'], $_POST['assembly_number'], $_POST['assembly_year'])) {
    $topic_id = intval($_POST['cancel_topic_delay_id']);
    $teacher_id = $_SESSION['userid'];
    $assembly_number = trim($_POST['assembly_number']);
    $assembly_year = trim($_POST['assembly_year']);

    $stmt = $db->prepare("SELECT confirmed_time FROM topics WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $topic_id, $teacher_id);
    $stmt->execute();
    $stmt->bind_result($confirmed_time);


    
    if ($stmt->fetch()) {
        $stmt->close();
        $assigned_date = new DateTime($confirmed_time);
        $now = new DateTime();
        $interval = $assigned_date->diff($now);

        if ($interval->y >= 2) {
            $reason = 'από Διδάσκοντα';
            $cancel_info = "Γ.Σ. {$assembly_number}/{$assembly_year}";

            
            $update = $db->prepare("UPDATE topics SET status = 'cancelled', cancel_reason = ?, cancel_info = ? WHERE id = ?");
            $update->bind_param("ssi", $reason, $cancel_info, $topic_id);
            $update->execute();
            $update->close();
            header("Location: TeacherThesisList.php");
            exit;


        } else {
            
            header("Location: TeacherThesisList.php");
        }
    } else {
            header("Location: TeacherThesisList.php");       
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topic_id'])) {
    $topic_id   = intval($_POST['topic_id']);
    $teacher_id = $_SESSION['userid']; // ο logged-in επιβλέπων

    if (isset($_POST['set_for_examination'])) {
        // Μόνο αν ο teacher_id είναι ο επιβλέπων
        $stmt = $db->prepare("UPDATE topics SET status='for examination' 
                              WHERE id=? AND teacher_id=?");
        $stmt->bind_param("ii", $topic_id, $teacher_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // ενημερώθηκε επιτυχώς
            $_SESSION['success_msg'] = "Η διπλωματική τέθηκε υπό εξέταση.";
        } else {
            // δεν ενημερώθηκε (δεν είναι επιβλέπων ή λάθος id)
            $_SESSION['error_msg'] = "Δεν έχετε δικαίωμα να θέσετε αυτή τη διπλωματική υπό εξέταση.";
        }

        $stmt->close();
    }

    header("Location: TeacherThesisList.php?ypoeksetasi");
    exit;
}


?>
