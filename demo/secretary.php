<?php
session_start();

// Ελέγχουμε αν υπάρχει το session και αν ο χρήστης είναι γραμματεία
if (!isset($_SESSION['userid']) || $_SESSION['role'] != 'admin') {
    // Αν δεν είναι γραμματεία ή δεν είναι συνδεδεμένος, ανακατευθύνουμε στην κεντρική σελίδα ή σελίδα login
    header("Location: register.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Πίνακας Ελέγχου Γραμματείας</title>
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

    <h1>Καλωσήρθατε, Γραμματεία</h1>

    <div class="container">

        <!-- 1. Προβολή Διπλωματικών Εργασιών -->
        <div class="card">
            <h2>1. Προβολή Διπλωματικών Εργασιών</h2>
            <p>Δείτε όλες τις διπλωματικές εργασίες που είναι σε κατάσταση «Ενεργή» και «Υπό Εξέταση». Επιλέγοντας μια διπλωματική, εμφανίζονται οι λεπτομέρειες της, καθώς και η τρέχουσα κατάσταση και τα μέλη της τριμελούς επιτροπής (αν υπάρχουν).</p>
            <a class="btn" href="view_theses.php">Προβολή Διπλωματικών Εργασιών</a>
        </div>

        <!-- 2. Εισαγωγή Δεδομένων -->
        <div class="card">
            <h2>2. Εισαγωγή Δεδομένων</h2>
            <p>Εισάγετε ένα αρχείο JSON που περιλαμβάνει τις προσωπικές πληροφορίες των φοιτητών και διδασκόντων.</p>
            <a class="btn" href="upload_data.php">Εισαγωγή Αρχείου JSON</a>
        </div>

        <!-- 3. Διαχείριση Διπλωματικής Εργασίας -->
        <div class="card">
            <h2>3. Διαχείριση Διπλωματικής Εργασίας</h2>
            <p>Ανάλογα με την κατάσταση μιας διπλωματικής, η Γραμματεία μπορεί να κάνει διάφορες ενέργειες:</p>
            
            <a class="btn" href="manage_active_thesis.php">Διαχείριση Διπλωματικών</a>
        </div>

    </div>

</body>
</html>
