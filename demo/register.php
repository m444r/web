<?php

require_once "config.php";
require_once "session.php";


$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

   // $email = trim($_POST['email']);
    //$password = trim($_POST['password']);

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    //$rolos = $_POST['rolos'];

    // validate if email is empty
    if (empty($email)) {
        $error .= '<p class="error">Please enter email.</p>';
    }

    // validate if password is empty
    if (empty($password)) {
        $error .= '<p class="error">Please enter your password.</p>';
    }

    if (empty($error)) {
        if($query = $db->prepare("SELECT * FROM users WHERE email = ?")) {
        /*    $query->bind_param('s', $email);
            $query->execute();
            $row = $query->fetch();*/
            
// Παίρνουμε τον χρήστη από τη βάση
            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($db, $sql);
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION["userid"] = $row['id'];
                    $_SESSION["user"] = $row;
                    $_SESSION["role"] = $row['role'];
                    //header("location: simple.php");////!!!!!!!!!!!!!!!!!
                    if ($role == $row['role']) {
                        $_SESSION["userid"] = $row['id'];  // Store user ID in session
    
                        // Redirect based on the user's role
                        if ($role == 'student') {
                            header("Location: student.php"); // Redirect to student's page
                        } elseif ($role == 'teacher') {
                            header("Location: teacher.php"); // Redirect to teacher's page
                        } elseif ($role == 'secretary') {
                            header("Location: secretary.php"); // Redirect to admin's page
                        }
                        else {
                            $error .= '<p class="error">Your selected role does not match your account role.</p>';
                        } /// den leitourgei to else ti fasi?

                    } 
         
                } else {
                    $error .= '<p class="error">The password is not valid.</p>';
                }
                
                
            } else {
                $error .= '<p class="error">No User exist with that email address.</p>';
            }
        }
        $query->close();
    }
    // Close connection
    mysqli_close($db);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Login</h2>
                    <p>Please fill in your email and password.</p>
                    <?php echo $error; ?>
                    
                    <form action="" method="post">
                        <div class="form-group">
                            <label>Ρόλος:</label><br>
                            <label><input type="radio" name="role" value="student" required> Φοιτητής</label>
                            <label><input type="radio" name="role" value="teacher"> Διδάσκων</label>
                            <label><input type="radio" name="role" value="secretary"> Γραμματεία</label><br>

                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required />
                            

                        </div>    
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <input type="submit" name="submit" class="btn btn-primary" value="Login">
                        </div>
                    </form>
                </div>
            </div>
        </div>    
    </body>
</html>
