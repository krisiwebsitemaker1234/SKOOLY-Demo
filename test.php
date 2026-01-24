<?php
// DEBUG FILE - Use this to test your login
require_once 'config/database.php';

echo "<h2>Login Debug Tool</h2>";
echo "<hr>";

// Check database connection
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
}
echo "✅ Database connected successfully<br><br>";

// Check if superadmin exists
$result = $conn->query("SELECT * FROM users WHERE role = 'superadmin'");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<h3>Super Admin Account Found:</h3>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Role: " . $user['role'] . "<br>";
    echo "Status: " . $user['status'] . "<br>";
    echo "Password Hash: " . substr($user['password'], 0, 20) . "...<br><br>";
    
    // Test password verification
    $test_password = 'password';
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Password 'password' is CORRECT<br>";
    } else {
        echo "❌ Password 'password' does NOT match<br>";
        echo "<br><strong>Let's create a new password hash:</strong><br>";
        $new_hash = password_hash('password', PASSWORD_BCRYPT);
        echo "New hash: " . $new_hash . "<br><br>";
        echo "Run this SQL to update:<br>";
        echo "<code>UPDATE users SET password = '$new_hash' WHERE username = 'superadmin';</code>";
    }
} else {
    echo "❌ No superadmin account found in database!<br>";
    echo "<br><strong>Run this SQL to create one:</strong><br>";
    $hash = password_hash('password', PASSWORD_BCRYPT);
    echo "<code>INSERT INTO users (username, email, password, role, status) VALUES ('superadmin', 'admin@school.com', '$hash', 'superadmin', 'active');</code>";
}

echo "<hr>";
echo "<h3>Test Login Form</h3>";
echo "<form method='POST'>";
echo "Username: <input type='text' name='test_user' value='superadmin'><br><br>";
echo "Email: <input type='email' name='test_email' value='admin@school.com'><br><br>";
echo "Password: <input type='password' name='test_pass' value='password'><br><br>";
echo "<button type='submit' name='test_login'>Test Login</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    echo "<hr><h3>Login Test Results:</h3>";
    $username = $_POST['test_user'];
    $email = $_POST['test_email'];
    $password = $_POST['test_pass'];
    
    echo "Attempting login with:<br>";
    echo "Username: $username<br>";
    echo "Email: $email<br>";
    echo "Password: $password<br><br>";
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND username = ? AND role = 'superadmin' AND status = 'active'");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        echo "✅ User found in database<br>";
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            echo "✅ Password verified successfully!<br>";
            echo "<strong style='color:green;'>LOGIN WOULD BE SUCCESSFUL</strong>";
        } else {
            echo "❌ Password verification failed<br>";
            echo "<strong style='color:red;'>LOGIN WOULD FAIL - Password mismatch</strong>";
        }
    } else {
        echo "❌ User not found in database<br>";
        echo "<strong style='color:red;'>LOGIN WOULD FAIL - User not found</strong>";
    }
}
?>