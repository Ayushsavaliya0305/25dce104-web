<?php
// Practical 14 : User Management - ADMIN ONLY (list + create + delete, all audited)
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/menu.php";
require_admin();

$msg = isset($_GET["msg"]) ? $_GET["msg"] : "";
$type = isset($_GET["type"]) ? $_GET["type"] : "";
$error = "";

// --- Create user ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = trim($_POST["role"] ?? "staff");

    if ($username === "" || $password === "") {
        $error = "Failure: Username and password are required.";
    } elseif (!preg_match("/^[A-Za-z0-9_]{3,50}$/", $username)) {
        $error = "Failure: Username must be 3-50 chars (letters, digits, underscore).";
    } elseif (strlen($password) < 6) {
        $error = "Failure: Password must be at least 6 characters.";
    } elseif (!in_array($role, ["admin", "staff"], true)) {
        $error = "Failure: Invalid role.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $role);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            audit("user_create", "Created user '$username' with role '$role'");
            header("Location: users.php?msg=" . urlencode("Success: User '$username' created.") . "&type=success");
            exit;
        } else {
            $error = (mysqli_errno($conn) == 1062)
                ? "Failure: Username already exists."
                : "Failure: Could not create user.";
            mysqli_stmt_close($stmt);
        }
    }
}

// --- Delete user (?delete=id) ---
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $me = $_SESSION["username"];
    $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $target = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$target) {
        header("Location: users.php?msg=" . urlencode("Failure: User not found.") . "&type=error");
        exit;
    } elseif ($target["username"] === $me) {
        header("Location: users.php?msg=" . urlencode("Failure: You cannot delete your own account.") . "&type=error");
        exit;
    } else {
        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($del, "i", $id);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        audit("user_delete", "Deleted user '{$target["username"]}' (id $id)");
        header("Location: users.php?msg=" . urlencode("Success: User deleted.") . "&type=success");
        exit;
    }
}

$users = [];
$res = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY id ASC");
if ($res) { $users = mysqli_fetch_all($res, MYSQLI_ASSOC); }
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Users - Practical 14</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card wide">
    <h1>User Management</h1>
    <?php render_menu("Users"); ?>

    <?php if ($msg !== ""): ?><div class="<?php echo ($type === "error") ? "msg-error" : "msg-success"; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="grid2">
        <div>
            <h2>All Users (<?php echo count($users); ?>)</h2>
            <table>
                <tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Action</th></tr>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo (int)$u["id"]; ?></td>
                    <td><?php echo htmlspecialchars($u["username"]); ?></td>
                    <td><span class="pill"><?php echo htmlspecialchars($u["role"]); ?></span></td>
                    <td><?php echo htmlspecialchars($u["created_at"]); ?></td>
                    <td>
                        <?php if ($u["username"] !== $_SESSION["username"]): ?>
                        <a class="btn btn-delete" href="users.php?delete=<?php echo (int)$u["id"]; ?>"
                           onclick="return confirm('Delete user <?php echo htmlspecialchars($u["username"]); ?>?');">Delete</a>
                        <?php else: ?><span class="muted">(you)</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div>
            <h2>Add User</h2>
            <form method="POST" action="users.php" class="stacked">
                <label>Username</label>
                <input type="text" name="username" required pattern="[A-Za-z0-9_]{3,50}">
                <label>Password (min 6 chars)</label>
                <input type="password" name="password" required minlength="6">
                <label>Role</label>
                <select name="role">
                    <option value="staff">staff</option>
                    <option value="admin">admin</option>
                </select>
                <div class="form-row"><button type="submit" class="btn btn-save">Create User</button></div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
