<?php
// ============================================================
// FILE: admin/settings.php
// PURPOSE: Admin skill categories management.
// ============================================================

include '../includes/header3.php';

// Only super admin can access settings
if ($admin_role !== 'admin') {
    echo '<div class="container"><div class="alert alert-danger">Only Super Administrators can access this page.</div></div>';
    include '../includes/footer3.php';
    exit();
}

$message = "";

// --- Process New Category ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {
    $cat_name = trim($_POST["category_name"]);
    $cat_desc = trim($_POST["category_description"]);

    if (!empty($cat_name)) {
        $cat_sql = "INSERT INTO skill_categories (name, description) VALUES (?, ?)";
        $cat_stmt = mysqli_prepare($conn, $cat_sql);
        mysqli_stmt_bind_param($cat_stmt, "ss", $cat_name, $cat_desc);
        if (mysqli_stmt_execute($cat_stmt)) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Category added successfully.</div>';
        }
    }
}

// Fetch categories
$categories = mysqli_query($conn, "SELECT * FROM skill_categories ORDER BY name");
?>

<section class="section">
    <div class="container">
        <h1 class="page-title">Skill Categories</h1>

        <?php echo $message; ?>

        <!-- Skill Categories Management -->
        <div class="admin-panel">
            <h3><i class="fas fa-tags"></i> Platform Skill Categories</h3>
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

            <form method="POST" style="margin-top: 1.5rem;">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="category_name" placeholder="New category name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="category_description" placeholder="Description (optional)">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include '../includes/footer3.php'; ?>
