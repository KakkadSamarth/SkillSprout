<?php
// ============================================================
// FILE: user/profile.php
// PURPOSE: User profile page supporting both View Mode and Edit
//          Mode, plus peer reviews & ratings system.
//          - Default: Clean View Mode with an "Edit Profile" button.
//          - Click "Edit Profile": Toggles form to edit name, bio, skills.
//          - Viewing other users: Can view details & submit reviews.
// ============================================================

include '../includes/header2.php';

$success = "";
$error   = "";
$review_success = "";
$review_error   = "";

// --- Determine which profile is being viewed ---
$profile_user_id = isset($_GET["id"]) ? intval($_GET["id"]) : $session_user_id;
if ($profile_user_id <= 0) {
    $profile_user_id = $session_user_id;
}
$is_own_profile = ($profile_user_id === $session_user_id);

// --- Process Profile Update (Own Profile Only) ---
$show_edit_form = isset($_GET["edit"]) && $_GET["edit"] == "1";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"]) && $is_own_profile) {
    $new_name   = trim($_POST["name"] ?? "");
    $new_bio    = trim($_POST["bio"] ?? "");
    $new_skills = trim($_POST["skills"] ?? "");

    if (empty($new_name) || strlen($new_name) < 3) {
        $error = "Name must be at least 3 characters.";
        $show_edit_form = true;
    } else {
        $update_sql = "UPDATE users SET name = ?, bio = ?, skills = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssi", $new_name, $new_bio, $new_skills, $session_user_id);

        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION["name"] = $new_name;
            $success = "Profile updated successfully!";
            $show_edit_form = false;
        } else {
            $error = "Failed to update profile. Please try again.";
            $show_edit_form = true;
        }
    }
}

// --- Process Review Submission (Only when reviewing another user) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_review"]) && !$is_own_profile) {
    $rating   = isset($_POST["rating"]) ? intval($_POST["rating"]) : 0;
    $comment  = isset($_POST["comment"]) ? trim($_POST["comment"]) : "";
    $task_id  = (!empty($_POST["task_id"]) && intval($_POST["task_id"]) > 0) ? intval($_POST["task_id"]) : null;

    if ($rating < 1 || $rating > 5) {
        $review_error = "Please select a star rating between 1 and 5.";
    } elseif (strlen($comment) < 5) {
        $review_error = "Please provide feedback of at least 5 characters.";
    } else {
        // Check if an existing review exists between reviewer and reviewed user
        $check_sql = "SELECT review_id FROM reviews WHERE reviewer_id = ? AND reviewed_user_id = ? " . ($task_id ? "AND task_id = ?" : "AND (task_id = ? OR task_id IS NULL)");
        $check_stmt = mysqli_prepare($conn, $check_sql);
        $task_param = $task_id ? $task_id : null;
        mysqli_stmt_bind_param($check_stmt, "iii", $session_user_id, $profile_user_id, $task_param);
        mysqli_stmt_execute($check_stmt);
        $existing_rev = mysqli_fetch_assoc(mysqli_stmt_get_result($check_stmt));

        if ($existing_rev) {
            // Update existing review
            $update_rev_sql = "UPDATE reviews SET rating = ?, comment = ?, task_id = ?, updated_at = NOW() WHERE review_id = ?";
            $update_rev_stmt = mysqli_prepare($conn, $update_rev_sql);
            mysqli_stmt_bind_param($update_rev_stmt, "isii", $rating, $comment, $task_id, $existing_rev["review_id"]);
            if (mysqli_stmt_execute($update_rev_stmt)) {
                $review_success = "Your review has been updated successfully!";
            } else {
                $review_error = "Failed to update review. Please try again.";
            }
        } else {
            // Insert new review
            $insert_rev_sql = "INSERT INTO reviews (reviewer_id, reviewed_user_id, task_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
            $insert_rev_stmt = mysqli_prepare($conn, $insert_rev_sql);
            mysqli_stmt_bind_param($insert_rev_stmt, "iiiis", $session_user_id, $profile_user_id, $task_id, $rating, $comment);
            if (mysqli_stmt_execute($insert_rev_stmt)) {
                $review_success = "Review submitted successfully! Thank you for rating this user.";
            } else {
                $review_error = "Failed to submit review. Please try again.";
            }
        }
    }
}

// --- Fetch User Data ---
$user_sql  = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $profile_user_id);
mysqli_stmt_execute($user_stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));

if (!$user) {
    echo '<section class="section"><div class="container" style="max-width: 600px; text-align: center; padding: 3rem 0;">';
    echo '<div class="alert alert-danger"><i class="fas fa-user-slash"></i> User profile not found.</div>';
    echo '<a href="' . BASE_URL . '/user/dashboard.php" class="btn btn-outline" style="margin-top: 1rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>';
    echo '</div></section>';
    include '../includes/footer2.php';
    exit();
}

// --- Fetch User Task & Activity Stats ---
// Tasks completed as assigned worker
$completed_sql = "SELECT COUNT(*) as count FROM tasks WHERE assigned_user_id = ? AND status = 'COMPLETED'";
$completed_stmt = mysqli_prepare($conn, $completed_sql);
mysqli_stmt_bind_param($completed_stmt, "i", $profile_user_id);
mysqli_stmt_execute($completed_stmt);
$tasks_completed = mysqli_fetch_assoc(mysqli_stmt_get_result($completed_stmt))["count"];

// Tasks posted as creator
$posted_sql = "SELECT COUNT(*) as count FROM tasks WHERE creator_id = ?";
$posted_stmt = mysqli_prepare($conn, $posted_sql);
mysqli_stmt_bind_param($posted_stmt, "i", $profile_user_id);
mysqli_stmt_execute($posted_stmt);
$tasks_posted = mysqli_fetch_assoc(mysqli_stmt_get_result($posted_stmt))["count"];

// --- Fetch Reviews & Ratings Stats ---
$stats_sql = "SELECT COUNT(*) as total_reviews, COALESCE(AVG(rating), 0) as avg_rating FROM reviews WHERE reviewed_user_id = ?";
$stats_stmt = mysqli_prepare($conn, $stats_sql);
mysqli_stmt_bind_param($stats_stmt, "i", $profile_user_id);
mysqli_stmt_execute($stats_stmt);
$rating_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));
$total_reviews = intval($rating_stats["total_reviews"]);
$avg_rating    = round(floatval($rating_stats["avg_rating"]), 1);

// Star breakdown counts
$star_counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if ($total_reviews > 0) {
    $breakdown_sql = "SELECT rating, COUNT(*) as count FROM reviews WHERE reviewed_user_id = ? GROUP BY rating";
    $breakdown_stmt = mysqli_prepare($conn, $breakdown_sql);
    mysqli_stmt_bind_param($breakdown_stmt, "i", $profile_user_id);
    mysqli_stmt_execute($breakdown_stmt);
    $b_res = mysqli_stmt_get_result($breakdown_stmt);
    while ($b_row = mysqli_fetch_assoc($b_res)) {
        $r = intval($b_row["rating"]);
        if (isset($star_counts[$r])) {
            $star_counts[$r] = intval($b_row["count"]);
        }
    }
}

// Fetch all reviews for this user
$reviews_sql = "SELECT r.*, u.name as reviewer_name, t.title as task_title 
                FROM reviews r 
                JOIN users u ON r.reviewer_id = u.user_id 
                LEFT JOIN tasks t ON r.task_id = t.task_id 
                WHERE r.reviewed_user_id = ? 
                ORDER BY r.created_at DESC";
$reviews_stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($reviews_stmt, "i", $profile_user_id);
mysqli_stmt_execute($reviews_stmt);
$all_reviews = mysqli_stmt_get_result($reviews_stmt);

// Check if current user has already reviewed this user
$my_existing_review = null;
if (!$is_own_profile) {
    $my_rev_sql = "SELECT * FROM reviews WHERE reviewer_id = ? AND reviewed_user_id = ? ORDER BY created_at DESC LIMIT 1";
    $my_rev_stmt = mysqli_prepare($conn, $my_rev_sql);
    mysqli_stmt_bind_param($my_rev_stmt, "ii", $session_user_id, $profile_user_id);
    mysqli_stmt_execute($my_rev_stmt);
    $my_existing_review = mysqli_fetch_assoc(mysqli_stmt_get_result($my_rev_stmt));
}

// Fetch any completed tasks shared between reviewer and profile user
$shared_tasks = [];
if (!$is_own_profile) {
    $shared_sql = "SELECT task_id, title FROM tasks 
                   WHERE status = 'COMPLETED' 
                     AND ((creator_id = ? AND assigned_user_id = ?) 
                          OR (creator_id = ? AND assigned_user_id = ?))";
    $shared_stmt = mysqli_prepare($conn, $shared_sql);
    mysqli_stmt_bind_param($shared_stmt, "iiii", $session_user_id, $profile_user_id, $profile_user_id, $session_user_id);
    mysqli_stmt_execute($shared_stmt);
    $st_res = mysqli_stmt_get_result($shared_stmt);
    while ($st = mysqli_fetch_assoc($st_res)) {
        $shared_tasks[] = $st;
    }
}

// Pre-fill target task if passed in GET parameter
$target_task_id = isset($_GET["task_id"]) ? intval($_GET["task_id"]) : 0;
?>

<section class="section">
    <div class="container">
        <!-- Page Title & Navigation -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 class="page-title" style="margin-bottom: 0.25rem;">
                    <?php echo $is_own_profile ? "My Profile" : htmlspecialchars($user["name"]) . "'s Profile"; ?>
                </h1>
                <p class="text-muted" style="margin-bottom: 0;">
                    <?php echo $is_own_profile ? "Manage your public profile and view your peer reputation" : "View member credentials, skills, and peer reviews"; ?>
                </p>
            </div>
            <div>
                <?php if (!$is_own_profile): ?>
                    <a href="<?php echo BASE_URL; ?>/tasks/tasks.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i> Browse Tasks
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback Alerts -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($review_success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $review_success; ?></div>
        <?php endif; ?>
        <?php if (!empty($review_error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $review_error; ?></div>
        <?php endif; ?>

        <!-- Main Profile Layout -->
        <div class="profile-container">
            <!-- Left Column: Identity & Card -->
            <div class="profile-info-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user["name"]); ?></h2>
                
                <?php if ($is_own_profile): ?>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user["email"]); ?></p>
                <?php endif; ?>

                <!-- Rating Pill Badge -->
                <div class="profile-rating-pill">
                    <i class="fas fa-star text-gold"></i>
                    <?php if ($total_reviews > 0): ?>
                        <span><strong><?php echo number_format($avg_rating, 1); ?></strong> / 5.0 (<?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? "review" : "reviews"; ?>)</span>
                    <?php else: ?>
                        <span>New Member (No reviews yet)</span>
                    <?php endif; ?>
                </div>

                <!-- Profile Meta Items -->
                <div class="profile-meta">
                    <?php if ($is_own_profile): ?>
                        <div class="profile-meta-item">
                            <span><i class="fas fa-wallet text-green"></i> Balance</span>
                            <strong style="color: var(--primary);"><?php echo number_format($user["wp_balance"]); ?> WP</strong>
                        </div>
                    <?php endif; ?>

                    <div class="profile-meta-item">
                        <span><i class="fas fa-shield-alt"></i> Role</span>
                        <span class="badge badge-sm badge-<?php echo ($user["role"] === 'admin' ? 'completed' : 'primary'); ?>" style="text-transform: capitalize;">
                            <?php echo htmlspecialchars($user["role"]); ?>
                        </span>
                    </div>

                    <div class="profile-meta-item">
                        <span><i class="fas fa-calendar-alt"></i> Joined</span>
                        <span><?php echo date("M Y", strtotime($user["created_at"])); ?></span>
                    </div>

                    <div class="profile-meta-item">
                        <span><i class="fas fa-check-circle text-green"></i> Tasks Done</span>
                        <span><?php echo $tasks_completed; ?></span>
                    </div>

                    <div class="profile-meta-item">
                        <span><i class="fas fa-list-check"></i> Tasks Posted</span>
                        <span><?php echo $tasks_posted; ?></span>
                    </div>
                </div>

                <!-- Primary Action Button: Edit Profile for Own Profile / Write Review for Other Profile -->
                <?php if ($is_own_profile): ?>
                    <button type="button" id="btnToggleEdit" class="btn btn-outline btn-block" onclick="toggleEditProfile()">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </button>
                <?php else: ?>
                    <a href="#write-review" class="btn btn-primary btn-block">
                        <i class="fas fa-star"></i> <?php echo $my_existing_review ? "Update Your Review" : "Leave a Review"; ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Right Column: Profile Content & Edit Mode -->
            <div class="profile-content-area">
                
                <!-- Mini Stats Bar -->
                <div class="profile-stats-mini">
                    <div class="mini-stat-card">
                        <div class="mini-stat-num" style="color: var(--primary);"><?php echo $tasks_completed; ?></div>
                        <div class="mini-stat-label">Tasks Completed</div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-stat-num"><?php echo $tasks_posted; ?></div>
                        <div class="mini-stat-label">Tasks Posted</div>
                    </div>
                    <div class="mini-stat-card">
                        <div class="mini-stat-num text-gold">
                            <?php echo $total_reviews > 0 ? number_format($avg_rating, 1) . ' ★' : '—'; ?>
                        </div>
                        <div class="mini-stat-label"><?php echo $total_reviews; ?> Reviews</div>
                    </div>
                </div>

                <!-- VIEW MODE: Bio and Skills (Default View) -->
                <div id="profileViewContainer" style="<?php echo $show_edit_form ? 'display: none;' : 'display: flex; flex-direction: column; gap: 1.5rem;'; ?>">
                    
                    <!-- Bio Card -->
                    <div class="profile-view-card">
                        <div class="profile-card-header">
                            <h3><i class="fas fa-quote-left"></i> About & Bio</h3>
                            <?php if ($is_own_profile): ?>
                                <button type="button" class="btn btn-xs btn-outline" onclick="toggleEditProfile()">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($user["bio"])): ?>
                            <div class="profile-bio-text"><?php echo nl2br(htmlspecialchars($user["bio"])); ?></div>
                        <?php else: ?>
                            <div class="profile-bio-text empty-text">
                                <?php echo $is_own_profile ? "You haven't written a bio yet. Click 'Edit Profile' to share your experience, background, and what you specialize in." : "This user hasn't provided a biography yet."; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Skills Card -->
                    <div class="profile-view-card">
                        <div class="profile-card-header">
                            <h3><i class="fas fa-tools"></i> Skills & Expertise</h3>
                            <?php if ($is_own_profile): ?>
                                <button type="button" class="btn btn-xs btn-outline" onclick="toggleEditProfile()">
                                    <i class="fas fa-plus"></i> Edit Skills
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php 
                        $skills_list = !empty($user["skills"]) ? array_filter(array_map('trim', explode(',', $user["skills"]))) : [];
                        ?>
                        <?php if (!empty($skills_list)): ?>
                            <div class="profile-skills-list">
                                <?php foreach ($skills_list as $skill): ?>
                                    <span class="skill-tag">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($skill); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="profile-bio-text empty-text">
                                <?php echo $is_own_profile ? "No skills added yet. Click 'Edit Profile' to add comma-separated skills (e.g. PHP, Design, Writing)." : "No skills listed yet."; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- EDIT MODE: Edit Profile Form (Only for own profile, toggled by Edit button) -->
                <?php if ($is_own_profile): ?>
                    <div id="profileEditContainer" class="profile-edit-container" style="<?php echo $show_edit_form ? 'display: block;' : 'display: none;'; ?>">
                        <div class="edit-form-header">
                            <h3><i class="fas fa-user-edit text-green"></i> Edit Your Profile</h3>
                            <button type="button" class="btn btn-xs btn-outline" onclick="toggleEditProfile()" title="Cancel editing">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>

                        <form action="" method="POST">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="form-group">
                                <label for="profile_name"><i class="fas fa-user"></i> Full Name *</label>
                                <input type="text" id="profile_name" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>" required minlength="3" placeholder="Enter your full name">
                            </div>

                            <div class="form-group">
                                <label for="profile_bio"><i class="fas fa-align-left"></i> About / Biography</label>
                                <textarea id="profile_bio" name="bio" rows="4" placeholder="Tell other members about yourself, your background, and your experience..."><?php echo htmlspecialchars($user["bio"] ?? ""); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="profile_skills"><i class="fas fa-tags"></i> Skills & Expertise (comma-separated)</label>
                                <input type="text" id="profile_skills" name="skills" value="<?php echo htmlspecialchars($user["skills"] ?? ""); ?>" placeholder="e.g. Web Development, Graphic Design, Content Writing, Python, Translation">
                                <small class="text-muted" style="font-size: 0.8rem; margin-top: 4px; display: block;">
                                    Separate each skill with a comma. These skills will display as badges on your profile.
                                </small>
                            </div>

                            <div class="form-actions-inline">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-outline" onclick="toggleEditProfile()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ======================================================== -->
        <!-- REVIEWS & RATINGS SECTION -->
        <!-- ======================================================== -->
        <div class="reviews-section" id="reviews-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 class="section-title" style="margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                        <i class="fas fa-star text-gold"></i> Peer Reviews & Ratings
                    </h2>
                    <p class="text-muted" style="margin-top: 4px; margin-bottom: 0;">
                        <?php echo $is_own_profile ? "Feedback and ratings given to you by clients and peers" : "Authentic community feedback from members who collaborated with " . htmlspecialchars($user["name"]); ?>
                    </p>
                </div>
                <?php if (!$is_own_profile): ?>
                    <a href="#write-review" class="btn btn-sm btn-outline">
                        <i class="fas fa-pen"></i> Write a Review
                    </a>
                <?php endif; ?>
            </div>

            <!-- Reviews Overview & Star Breakdown Grid -->
            <div class="reviews-summary-grid">
                <!-- Big Score Card -->
                <div class="rating-overview-card">
                    <div class="rating-score-large"><?php echo $total_reviews > 0 ? number_format($avg_rating, 1) : "—"; ?></div>
                    <div class="rating-stars-gold">
                        <?php
                        $rounded = round($avg_rating);
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $rounded): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star text-muted"></i>
                            <?php endif;
                        endfor;
                        ?>
                    </div>
                    <div class="rating-count-label">
                        Based on <?php echo $total_reviews; ?> <?php echo $total_reviews === 1 ? "review" : "reviews"; ?>
                    </div>
                </div>

                <!-- Rating Distribution Bars -->
                <div class="rating-bars-card">
                    <?php for ($s = 5; $s >= 1; $s--): 
                        $count = $star_counts[$s];
                        $pct   = $total_reviews > 0 ? round(($count / $total_reviews) * 100) : 0;
                    ?>
                        <div class="rating-bar-row">
                            <span class="rating-bar-label"><?php echo $s; ?> <i class="fas fa-star text-gold" style="font-size: 0.75rem;"></i></span>
                            <div class="rating-bar-track">
                                <div class="rating-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                            <span class="rating-bar-count"><?php echo $count; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Write a Review Form Card (Shown when viewing another user) -->
            <?php if (!$is_own_profile): ?>
                <div class="write-review-card" id="write-review">
                    <h3>
                        <i class="fas fa-pencil-alt text-green"></i> 
                        <?php echo $my_existing_review ? "Update Your Review for " . htmlspecialchars($user["name"]) : "Leave a Review for " . htmlspecialchars($user["name"]); ?>
                    </h3>
                    <p class="text-muted" style="margin-bottom: 1.25rem; font-size: 0.9rem;">
                        Share your feedback on their communication, reliability, and quality of work.
                    </p>

                    <form action="#reviews-section" method="POST" id="reviewForm">
                        <input type="hidden" name="submit_review" value="1">
                        
                        <!-- Interactive Star Rating Input -->
                        <div class="form-group">
                            <label><i class="fas fa-star text-gold"></i> Rating *</label>
                            <div class="interactive-stars-container">
                                <div class="interactive-stars" id="starContainer">
                                    <?php 
                                    $default_rating = $my_existing_review ? intval($my_existing_review["rating"]) : 5;
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <i class="<?php echo ($i <= $default_rating ? 'fas' : 'far'); ?> fa-star star-rating-item <?php echo ($i <= $default_rating ? 'active' : ''); ?>" 
                                           data-val="<?php echo $i; ?>" 
                                           onclick="setRating(<?php echo $i; ?>)"
                                           onmouseover="hoverRating(<?php echo $i; ?>)"
                                           onmouseout="resetRating()"></i>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="selectedRatingInput" value="<?php echo $default_rating; ?>">
                                <span class="rating-text-feedback" id="ratingTextFeedback">5 - Excellent</span>
                            </div>
                        </div>

                        <!-- Optional Associated Task -->
                        <?php if (!empty($shared_tasks) || $target_task_id > 0): ?>
                            <div class="form-group">
                                <label for="review_task_id"><i class="fas fa-clipboard-check"></i> Associated Task (Optional)</label>
                                <select name="task_id" id="review_task_id">
                                    <option value="">— General Peer Collaboration / Freelance Work —</option>
                                    <?php foreach ($shared_tasks as $st): ?>
                                        <option value="<?php echo $st["task_id"]; ?>" <?php echo ($target_task_id == $st["task_id"] || ($my_existing_review && $my_existing_review["task_id"] == $st["task_id"])) ? 'selected' : ''; ?>>
                                            Task #<?php echo $st["task_id"]; ?>: <?php echo htmlspecialchars($st["title"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Comment Feedback -->
                        <div class="form-group">
                            <label for="review_comment"><i class="fas fa-comment-alt"></i> Your Review & Feedback *</label>
                            <textarea id="review_comment" name="comment" rows="3" required minlength="5" placeholder="Describe your experience collaborating with <?php echo htmlspecialchars($user["name"]); ?>. Were deliverables met on time? How was communication?"><?php echo htmlspecialchars($my_existing_review["comment"] ?? ""); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> <?php echo $my_existing_review ? "Update Review" : "Publish Review"; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Reviews Feed List -->
            <div class="reviews-feed-list">
                <?php if (mysqli_num_rows($all_reviews) > 0): ?>
                    <?php while ($rev = mysqli_fetch_assoc($all_reviews)): ?>
                        <div class="review-item-card">
                            <div class="review-item-header">
                                <div class="reviewer-meta">
                                    <div class="reviewer-avatar-circle">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <a href="<?php echo BASE_URL; ?>/user/profile.php?id=<?php echo $rev["reviewer_id"]; ?>" class="reviewer-name">
                                            <?php echo htmlspecialchars($rev["reviewer_name"]); ?>
                                        </a>
                                        <div class="review-date">
                                            <i class="fas fa-clock"></i> <?php echo date("M d, Y", strtotime($rev["created_at"])); ?>
                                            <?php if ($rev["reviewer_id"] == $session_user_id): ?>
                                                <span class="badge badge-sm badge-outline" style="margin-left: 6px;">Your Review</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="rating-stars-gold">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $rev["rating"]): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-muted"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="review-item-comment">
                                <?php echo nl2br(htmlspecialchars($rev["comment"])); ?>
                            </div>

                            <?php if (!empty($rev["task_title"])): ?>
                                <div class="review-task-tag">
                                    <i class="fas fa-check-circle"></i> Completed Task: <strong><?php echo htmlspecialchars($rev["task_title"]); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="profile-view-card" style="text-align: center; padding: 3rem 1.5rem;">
                        <i class="far fa-star" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem; display: inline-block;"></i>
                        <h3 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 0.5rem;">No reviews yet</h3>
                        <p class="text-muted" style="max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.92rem;">
                            <?php if ($is_own_profile): ?>
                                Complete tasks on Skill Sprout and collaborate with others to receive peer reviews and build your reputation.
                            <?php else: ?>
                                Be the first person to leave a review for <?php echo htmlspecialchars($user["name"]); ?>!
                            <?php endif; ?>
                        </p>
                        <?php if (!$is_own_profile): ?>
                            <a href="#write-review" class="btn btn-primary btn-sm">
                                <i class="fas fa-pencil-alt"></i> Write the First Review
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<!-- Client-side Interactive Logic -->
<script>
// Toggle Edit Profile View/Form
function toggleEditProfile() {
    const viewContainer = document.getElementById('profileViewContainer');
    const editContainer = document.getElementById('profileEditContainer');
    const toggleBtn     = document.getElementById('btnToggleEdit');

    if (!editContainer || !viewContainer) return;

    if (editContainer.style.display === 'none' || editContainer.style.display === '') {
        editContainer.style.display = 'block';
        viewContainer.style.display = 'none';
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i> View Profile';
            toggleBtn.classList.add('btn-primary');
            toggleBtn.classList.remove('btn-outline');
        }
        // Focus first field
        const nameInput = document.getElementById('profile_name');
        if (nameInput) nameInput.focus();
    } else {
        editContainer.style.display = 'none';
        viewContainer.style.display = 'flex';
        if (toggleBtn) {
            toggleBtn.innerHTML = '<i class="fas fa-user-edit"></i> Edit Profile';
            toggleBtn.classList.remove('btn-primary');
            toggleBtn.classList.add('btn-outline');
        }
    }
}

// Interactive Star Rating Logic
const starFeedbackTexts = {
    1: '1 - Poor',
    2: '2 - Fair',
    3: '3 - Good',
    4: '4 - Very Good',
    5: '5 - Excellent'
};

let currentSelectedRating = parseInt(document.getElementById('selectedRatingInput')?.value || '5');

function updateStarDisplay(val) {
    const stars = document.querySelectorAll('#starContainer .star-rating-item');
    stars.forEach(star => {
        const starVal = parseInt(star.getAttribute('data-val'));
        if (starVal <= val) {
            star.classList.remove('far');
            star.classList.add('fas', 'active');
        } else {
            star.classList.remove('fas', 'active');
            star.classList.add('far');
        }
    });
    const feedback = document.getElementById('ratingTextFeedback');
    if (feedback && starFeedbackTexts[val]) {
        feedback.textContent = starFeedbackTexts[val];
    }
}

function setRating(val) {
    currentSelectedRating = val;
    const input = document.getElementById('selectedRatingInput');
    if (input) input.value = val;
    updateStarDisplay(val);
}

function hoverRating(val) {
    updateStarDisplay(val);
}

function resetRating() {
    updateStarDisplay(currentSelectedRating);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('selectedRatingInput')) {
        updateStarDisplay(currentSelectedRating);
    }
});
</script>

<?php include '../includes/footer2.php'; ?>
