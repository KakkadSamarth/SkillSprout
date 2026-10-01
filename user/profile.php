<?php
// ============================================================
// FILE: user/profile.php
// PURPOSE: User profile page. Shows and allows editing of
//          name, bio, and skills. Also displays account info.
// ============================================================

include '../includes/header2.php';

$success = "";
$error   = "";

// Fetch current user data
$user_sql  = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $session_user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

// --- Process Profile Update ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_name   = trim($_POST["name"]);
    $new_bio    = trim($_POST["bio"]);
    $new_skills = trim($_POST["skills"]);

    if (empty($new_name) || strlen($new_name) < 3) {
        $error = "Name must be at least 3 characters.";
    } else {
        $update_sql = "UPDATE users SET name = ?, bio = ?, skills = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssi", $new_name, $new_bio, $new_skills, $session_user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            // Update session name so the navbar reflects the change
            $_SESSION["name"] = $new_name;
            $success = "Profile updated successfully!";
            // Refresh user data
            $user["name"]   = $new_name;
            $user["bio"]    = $new_bio;
            $user["skills"] = $new_skills;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">My Profile</h1>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- Profile Info Card -->
            <div class="profile-info-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2><?php echo htmlspecialchars($user["name"]); ?></h2>
                <p class="text-muted"><?php echo htmlspecialchars($user["email"]); ?></p>
                <div class="profile-meta">
                    <span><i class="fas fa-wallet"></i> <?php echo number_format($user["wp_balance"]); ?> WP</span>
                    <span><i class="fas fa-calendar"></i> Joined <?php echo date("M Y", strtotime($user["created_at"])); ?></span>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="form-container">
                <h3>Edit Profile</h3>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>" required minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="bio"><i class="fas fa-info-circle"></i> Bio</label>
                        <textarea id="bio" name="bio" rows="4" placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user["bio"] ?? ""); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="skills"><i class="fas fa-tags"></i> Skills (comma separated)</label>
                        <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($user["skills"] ?? ""); ?>" placeholder="PHP, JavaScript, Design...">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer2.php'; ?>
