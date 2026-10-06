```php
<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = trim($_POST["password"] ?? "");

    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required.";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must contain 10 digits.";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // Display errors
    if (!empty($errors)) {

        echo "<h2>Registration Failed</h2>";

        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }

        echo "<a href='prac_7.html'>Go Back</a>";

    } else {

        // Sanitize data
        $name = htmlspecialchars($name);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars($phone);

        // CSV file
        $file = "registrations.csv";

        // Open CSV file
        $handle = fopen($file, "a");

        // Add headings if file is empty
        if (filesize($file) == 0) {
            fputcsv($handle, ["Name", "Email", "Phone", "Password"]);
        }

        // Save data
        fputcsv($handle, [$name, $email, $phone, $password]);

        fclose($handle);

        // Success message
        echo "<h2 style='color:green;'>Registration Successful!</h2>";
        echo "<p>Your information has been saved successfully.</p>";
        echo "<a href='prac_7.html'>Register Another User</a>";
    }

} else {
    echo "Invalid request.";
}

?>
```