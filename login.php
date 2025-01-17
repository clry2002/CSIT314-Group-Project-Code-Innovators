<?php
session_start(); // Start the session to maintain user data across requests
include 'connectDatabase.php';

// Entity Layer: User class to handle user authentication
class User {
    private $username;
    private $role;
    private $password;

    // Constructor with only essential user attributes
    public function __construct($username, $password, $role) {
        $this->username = $username;
        $this->password = $password;
        $this->role = $role;
    }

    // Authenticate user by verifying the username, role, and password
    public function authenticate($db) {
        $roleMapping = [
            'user admin' => 1,
            'used car agent' => 2,
            'buyer' => 3,
            'seller' => 4
        ];

        if (!array_key_exists($this->role, $roleMapping)) {
            return false;
        }

        $role_id = $roleMapping[$this->role];
        $stmt = $db->prepare("SELECT password FROM users WHERE username = ? AND role_id = ?");
        $stmt->bind_param("si", $this->username, $role_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($stored_password);
            $stmt->fetch();
            return $this->password === $stored_password;
        } else {
            return false;
        }
    }
}

// Control Layer: AuthController class to handle form submission and user authentication
class AuthController {
    private $database;

    public function __construct() {
        $this->database = new Database();
    }

    public function authenticateUser($username, $password, $role) {
        $user = new User($username, $password, $role);
        $dbConnection = $this->database->getConnection();

        if ($user->authenticate($dbConnection)) {
            $_SESSION['username'] = $username;
            return $this->getRedirectLocation($role);
        }
        return "Invalid username, password, or role.";
    }

    private function getRedirectLocation($role) {
        switch($role) {
            case 'user admin':
                return "admin_dashboard.php";
            case 'used car agent':
                return "agent_dashboard.php";
            case 'buyer':
                return "buyer_dashboard.php";
            case 'seller':
                return "seller_dashboard.php";
            default:
                return "Invalid role selected.";
        }
    }

    public function closeDatabaseConnection() {
        $this->database->closeConnection();
    }
}

// Boundary Layer: LoginForm class to generate the login form HTML
class LoginForm {
    public static function display($message = "") {
        ?>
        <!DOCTYPE HTML>
        <html lang="en">
        <head>
            <link rel="stylesheet" href="login.css"/>
            <title>CSIT314-PROJECT</title>
        </head>
        <body>
            <div class="website-title">
                <h1>CSIT314-GROUP PROJECT</h1>
                <h2>Made by: Code Innovators!</h2>
            </div>
            <form action="" method="POST">
                <div class="form-body">
                    <label for="role" class="form-label">Login As:</label>
                    <select id="role" name="role" class="form-label" required>
                        <option value="user admin">User Admin</option>
                        <option value="used car agent">Used Car Agent</option>
                        <option value="buyer">Buyer</option>
                        <option value="seller">Seller</option>
                    </select>
                    <br/><br/>
                    <label for="username" class="form-label">Username </label>
                    <input type="text" id="username" name="username" class="form-label" required/>
                    <br/><br/>
                    <label for="password" class="form-label">Password </label>
                    <input type="password" id="password" name="password" class="form-label" required/>
                    <br/><br/>
                    <button type="submit"  class="form-label" >Submit</button>
                    <br/>    
                </div>
            </form>

            <?php if ($message): ?>
                <p><?php echo $message; ?></p>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
}

// Handle form submission and interaction with the controller
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $controller = new AuthController();
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $role = htmlspecialchars($_POST['role']);

    if ($username && $password && $role) {
        $location = $controller->authenticateUser($username, $password, $role);

        if ($location && strpos($location, '.php') !== false) {
            header("Location: $location");
            exit();
        } else {
            LoginForm::display($location);
        }
    } else {
        LoginForm::display("Please fill in all fields.");
    }
    $controller->closeDatabaseConnection();
} else {
    LoginForm::display();
}
