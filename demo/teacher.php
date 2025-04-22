<?php
session_start();

// Ελέγχουμε αν υπάρχει το session και αν ο χρήστης είναι φοιτητής
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'teacher') {
    // Αν δεν είναι φοιτητής ή δεν είναι συνδεδεμένος, ανακατευθύνουμε στην κεντρική σελίδα ή σελίδα login
    header("Location: register.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πίνακας Ελέγχου Διδάσκοντα</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h1 {
            margin: 30px 0;
        }

        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: left;
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #003366;
        }

        .card p {
            font-size: 15px;
            color: #333;
        }

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 15px;
            background-color: #0057b7;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background-color: #004099;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <h1>Καλωσήρθατε, Διδάσκοντα</h1>

    <div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 10px; align-items: center;">
    <a href="logout.php" style="
        background-color: #dc3545;
        color: white;
        padding: 6px 12px;
        font-size: 14px;
        border: none;
        border-radius: 4px;
        text-decoration: none;
    ">Αποσύνδεση</a>
    </div>

    <div class="container">

        <!-- 1 -->
        <div class="card">
            <h2>1. Προβολή / Δημιουργία Θεμάτων</h2>
            <p>Δημιουργήστε νέα θέματα διπλωματικής, επισυνάψτε αρχεία PDF και επεξεργαστείτε όσα έχετε ήδη καταχωρήσει.</p>
            <a class="btn" href="create_or_edit_topics.php">Μετάβαση</a>
        </div>

        <!-- 2 -->
        <div class="card">
            <h2>2. Ανάθεση Θέματος σε Φοιτητή</h2>
            <p>Αναθέστε προσωρινά ένα ελεύθερο θέμα σε φοιτητή με βάση το ΑΜ ή το ονοματεπώνυμο. Η ανάθεση ισχύει μέχρι να εγκριθεί από την τριμελή.</p>
            <a class="btn" href="assign_topic.php">Μετάβαση</a>
        </div>

        <!-- 3 -->
        <div class="card">
            <h2>3. Προβολή Λίστας Διπλωματικών</h2>
            <p>Δείτε όλες τις διπλωματικές στις οποίες συμμετέχετε ως επιβλέπων ή μέλος. Φιλτράρετε με βάση την κατάσταση ή το ρόλο σας και εξάγετε τη λίστα σε CSV/JSON.</p>
            <a class="btn" href="thesis_list.php">Προβολή Λίστας</a>
        </div>

        <!-- 4 -->
        <div class="card">
            <h2>4. Προσκλήσεις για Τριμελή Επιτροπή</h2>
            <p>Δείτε και απαντήστε σε ενεργές προσκλήσεις συμμετοχής σε τριμελείς επιτροπές. Επιλέξτε να αποδεχθείτε ή να απορρίψετε.</p>
            <a class="btn" href="committee_invitations.php">Διαχείριση Προσκλήσεων</a>
        </div>

        <!-- Νέα Κάρτα: Στατιστικά Διπλωματικών -->
        <div class="card">
            <h2>5. Στατιστικά Διπλωματικών</h2>
            <p>Δείτε τα στατιστικά για τις διπλωματικές που έχετε επιβλέψει ή στις οποίες συμμετέχετε ως μέλος τριμελούς επιτροπής.</p>
            <a class="btn" href="view_thesis_statistics.php">Προβολή Στατιστικών</a>
        </div>

        <div class="card">
            <h2>6. Διαχείριση Διπλωματικών Εργασιών</h2>
            <p>Διαχειριστείτε υπο ανάθεση/ενεργές/υπο εξέταση διπλωματικές εργασίες .</p>
            <a class="btn" href="manage_diplomatikes.php"> Μετάβαση </a>
        </div>

    </div>

    

</body>
</html>
