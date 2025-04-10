<?php
define('DBSERVER', 'localhost'); // Database server
define('DBUSERNAME', 'root'); // Database username
define('DBPASSWORD', 'password'); // Database password
define('DBNAME', 'demo'); // Database name
 
/* connect to MySQL database */
$db = mysqli_connect(DBSERVER, DBUSERNAME, DBPASSWORD, DBNAME);



// Check db connection
if($db === false){
    die("Error: connection error. " . mysqli_connect_error());
}
/*echo "Σύνδεση με τη βάση πέτυχε!";

$sql = "SELECT * FROM users";
$result = mysqli_query($db, $sql);

// Έλεγχος αν υπάρχουν αποτελέσματα
if (mysqli_num_rows($result) > 0) {
    echo "<h2>Λογαριασμοί στη βάση:</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Email</th><th>Password (hash)</th></tr>";

    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['password'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "⚠️ Δεν βρέθηκαν λογαριασμοί στη βάση.";
}
*/


//$password = 'markar'; // Βάλε εδώ τον αρχικό κωδικό
//$hash = password_hash($password, PASSWORD_DEFAULT);

//echo $hash;

// Κλείσιμο σύνδεσης
//mysqli_close($db);
