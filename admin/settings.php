<?php
// ============================================================
// FILE: admin/settings.php
// PURPOSE: Global platform settings page (Super Admin only).
//          Manage registration bonus, min task reward,
//          maintenance mode, announcements, and skill categories.
// ============================================================

include '../includes/header3.php';

// Only super admin can access settings
if ($admin_role !== 'admin') {
    echo '<div class="container"><div class="alert alert-danger">Only Super Administrators can access settings.</div></div>';
    include '../includes/footer3.php';
    exit();
}

$message = "";

// --- Process Settings Update ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_settings"])) {
    $settings = [
        'registration_bonus' => intval($_POST["registration_bonus"]),
        'min_task_reward'    => intval($_POST["min_task_reward"]),
        'maintenance_mode'   => $_POST["maintenance_mode"],
        'announcement'       => trim($_POST["announcement"]),
        'site_name'          => trim($_POST["site_name"]),
    ];

    foreach ($settings as $key => $value) {
        $update_sql = "UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        $val_str = strval($value);
        mysqli_stmt_bind_param($update_stmt, "ss", $val_str, $key);
        mysqli_stmt_execute($update_stmt);
    }

    // Log the action
    $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'Updated platform settings', ?)";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    $ip = $_SERVER["REMOTE_ADDR"];
    mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $ip);
    mysqli_stmt_execute($log_stmt);

    $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully.</div>';
}

// --- Process New Category ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {
    $cat_name = trim($_POST["category_name"]);
    $cat_desc = trim($_POST["category_description"]);

    if (!empty($cat_name)) {
        $cat_sql = "INSERT INTO skill_categories (name, description) VALUES (?, ?)";
        $cat_stmt = mysqli_prepare($conn, $cat_sql);
        mysqli_stmt_bind_param($cat_stmt, "ss", $cat_name, $cat_desc);
        if (mysqli_stmt_execute($cat_stmt)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Category added.</div>';
        }
    }
}

// Fetch current settings
$settings_result = mysqli_query($conn, "SELECT * FROM platform_settings");
$settings = [];
while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[$row["setting_key"]] = $row["setting_value"];
}

// Fetch categories
$categories = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY name");
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Platform Settings</h1>

        <?php echo $message; ?>

        <!-- Settings Form -->
        <div class="admin-panel">
            <h3><i class="fas fa-cog"></i> General Settings</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings["site_name"] ?? "Skill Sprout"); ?>">
                    </div>
                    <div class="form-group">
                        <label>Registration Bonus (WP)</label>
                        <input type="number" name="registration_bonus" value="<?php echo $settings["registration_bonus"] ?? 100; ?>" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Task Reward (WP)</label>
                        <input type="number" name="min_task_reward" value="<?php echo $settings["min_task_reward"] ?? 5; ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label>Maintenance Mode</label>
                        <select name="maintenance_mode">
                            <option value="off" <?php echo ($settings["maintenance_mode"] ?? "off") === "off" ? "selected" : ""; ?>>Off</option>
                            <option value="on" <?php echo ($settings["maintenance_mode"] ?? "off") === "on" ? "selected" : ""; ?>>On</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Announcement Banner</label>
                    <textarea name="announcement" rows="2" placeholder="Leave empty for no announcement"><?php echo htmlspecialchars($settings["announcement"] ?? ""); ?></textarea>
                </div>
                <button type="submit" name="save_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>
        </div>

        <!-- Skill Categories Management -->
        <div class="admin-panel" style="margin-top: 2rem;">
            <h3><i class="fas fa-tags"></i> Skill Categories</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Description</th></tr></thead>
                    <tbody>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat["name"]); ?></td>
                                <td><?php echo htmlspecialchars($cat["description"] ?? "—"); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <form method="POST" style="margin-top: 1rem;">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="category_name" placeholder="New category name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="category_description" placeholder="Description (optional)">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
