# SkillSprout - Inbuilt Functions & Defined Code Blocks Reference

This guide provides a comprehensive, function-by-function and block-by-block breakdown of **how each function works**, **where it is used**, and **what each architectural block does** throughout SkillSprout, with an in-depth focus on files like `create_task.php`.

---

## Table of Contents
1. [Inbuilt PHP Functions: Reference & Usage](#1-inbuilt-php-functions-reference--usage)
   - [Database & MySQLi Functions](#a-database--mysqli-functions)
   - [Session & Cookie Management](#b-session--cookie-management)
   - [Security & Hashing Functions](#c-security--hashing-functions)
   - [String, Array & Utility Functions](#d-string-array--utility-functions)
   - [HTTP & Execution Flow Functions](#e-http--execution-flow-functions)
2. [File-by-File Breakdown: Inbuilt & Defined Code Blocks](#2-file-by-file-breakdown-inbuilt--defined-code-blocks)
   - [Deep Dive: tasks/create_task.php](#deep-dive-taskscreate_taskphp)
   - [Deep Dive: tasks/review_submission.php](#deep-dive-tasksreview_submissionphp)
   - [Deep Dive: tasks/cancel_task.php](#deep-dive-taskscancel_taskphp)
   - [Deep Dive: tasks/manage_applications.php](#deep-dive-tasksmanage_applicationsphp)
   - [Deep Dive: tasks/apply_task.php](#deep-dive-tasksapply_taskphp)
   - [Deep Dive: tasks/submit_work.php](#deep-dive-taskssubmit_workphp)
   - [Deep Dive: auth/login.php](#deep-dive-authloginphp)
   - [Deep Dive: auth/register.php](#deep-dive-authregisterphp)
   - [Deep Dive: auth/logout.php](#deep-dive-authlogoutphp)
   - [Deep Dive: user/dashboard.php](#deep-dive-userdashboardphp)
   - [Deep Dive: user/my_work.php](#deep-dive-usermy_workphp)
   - [Deep Dive: user/wallet.php & user/profile.php](#deep-dive-userwalletphp--userprofilephp)
   - [Deep Dive: config/database.php](#deep-dive-configdatabasephp)
   - [Deep Dive: includes/header1.php & includes/header2.php](#deep-dive-includesheader1php--includesheader2php)
3. [Client-Side JavaScript Validation Blocks](#3-client-side-javascript-validation-blocks)

---

## 1. Inbuilt PHP Functions: Reference & Usage

### A. Database & MySQLi Functions

#### `mysqli_connect($host, $user, $password, $database, $port)`
* **How it Works**: Opens a new TCP socket or named pipe connection to the MySQL database server and returns a connection handle object on success or `false` on failure.
* **Where Used**: `config/database.php`
* **Role**: Establishes the centralized `$conn` resource used across all database queries.

#### `mysqli_connect_error()`
* **How it Works**: Returns a human-readable string describing why the last connection attempt failed.
* **Where Used**: `config/database.php`
* **Role**: Prints failure reasons if the database server is unreachable or credentials fail.

#### `mysqli_prepare($conn, $sql)`
* **How it Works**: Compiles an SQL query template on the database engine with `?` parameter placeholders. Prevents SQL injection attacks by separating the query structure from untrusted user data.
* **Where Used**: `auth/login.php`, `auth/register.php`, `tasks/create_task.php`, `tasks/task_details.php`, `tasks/apply_task.php`, `tasks/manage_applications.php`, `tasks/submit_work.php`, `tasks/review_submission.php`, `tasks/cancel_task.php`, `user/dashboard.php`, `user/my_work.php`, `user/wallet.php`, `user/profile.php`.

#### `mysqli_stmt_bind_param($stmt, $types, &...$vars)`
* **How it Works**: Binds PHP variables to SQL statement parameter placeholders by reference. The `$types` string specifies types: `'s'` (string), `'i'` (integer), `'d'` (double), `'b'` (blob).
* **Where Used**: All files utilizing prepared statements.

#### `mysqli_stmt_execute($stmt)`
* **How it Works**: Transmits the bound values to MySQL and executes the compiled prepared query. Returns `true` on success, `false` on failure.
* **Where Used**: All endpoints executing prepared statements.

#### `mysqli_stmt_get_result($stmt)`
* **How it Works**: Fetches a buffered result set object (`mysqli_result`) from a prepared SELECT query.
* **Where Used**: `auth/login.php`, `tasks/create_task.php`, `tasks/task_details.php`, `user/dashboard.php`, `user/profile.php`, `user/wallet.php`, etc.

#### `mysqli_fetch_assoc($result)`
* **How it Works**: Reads the next row from a `mysqli_result` and returns an associative array where keys correspond to table column names, or `null` if no more rows exist.
* **Where Used**: Used everywhere SELECT query row data is unpacked.

#### `mysqli_num_rows($result)`
* **How it Works**: Returns the total number of rows present in a buffered query result set.
* **Where Used**: `auth/login.php` (verifies matching user), `tasks/tasks.php` (checks if tasks exist), `user/my_work.php` (checks list counts).

#### `mysqli_stmt_affected_rows($stmt)`
* **How it Works**: Returns the number of rows changed, deleted, or inserted by the executed statement.
* **Where Used**: `tasks/create_task.php` (verifies atomic balance deduction), `tasks/cancel_task.php` (verifies task status change), `tasks/review_submission.php` (ensures single approval execution).

#### `mysqli_stmt_close($stmt)`
* **How it Works**: Frees statement resources on both the PHP client and MySQL server.
* **Where Used**: Called after every prepared statement completes.

#### `mysqli_insert_id($conn)`
* **How it Works**: Returns the auto-generated `AUTO_INCREMENT` ID produced by the most recent `INSERT` query.
* **Where Used**: `tasks/create_task.php` (captures newly created task ID).

#### `mysqli_begin_transaction($conn)`
* **How it Works**: Disables auto-commit mode and begins a logical transactional block in MySQL (ACID compliance).
* **Where Used**: `tasks/create_task.php`, `tasks/cancel_task.php`, `tasks/manage_applications.php`, `tasks/review_submission.php`.

#### `mysqli_commit($conn)`
* **How it Works**: Permanently writes all transactional modifications to the database and re-enables auto-commit mode.
* **Where Used**: `tasks/create_task.php`, `tasks/cancel_task.php`, `tasks/manage_applications.php`, `tasks/review_submission.php`.

#### `mysqli_rollback($conn)`
* **How it Works**: Undoes all operations performed within the current transaction and re-enables auto-commit mode.
* **Where Used**: Error/catch blocks in `tasks/create_task.php`, `tasks/cancel_task.php`, `tasks/manage_applications.php`, `tasks/review_submission.php`.

---

### B. Session & Cookie Management

#### `session_start()`
* **How it Works**: Initializes session data or resumes the current session based on a session identifier passed via a cookie (`PHPSESSID`). Loads data into `$_SESSION`.
* **Where Used**: All endpoints requiring authentication state.

#### `session_status()`
* **How it Works**: Returns the current session state: `PHP_SESSION_DISABLED`, `PHP_SESSION_NONE`, or `PHP_SESSION_ACTIVE`.
* **Where Used**: `includes/header2.php` (ensures session is active before checking `$_SESSION["user_id"]`).

#### `session_destroy()`
* **How it Works**: Destroys all data registered to a session on the server file system/storage.
* **Where Used**: `auth/logout.php`.

#### `session_get_cookie_params()` & `setcookie(...)`
* **How it Works**: `session_get_cookie_params()` retrieves the current session cookie configuration (path, domain, secure flag, httponly). `setcookie()` overwrites the cookie with an expired timestamp (`time() - 42000`) to force browser deletion.
* **Where Used**: `auth/logout.php`.

---

### C. Security & Hashing Functions

#### `password_hash($password, PASSWORD_DEFAULT)`
* **How it Works**: Generates a cryptographically strong, one-way Bcrypt/Argon2 hash with an automatically generated secure random salt.
* **Where Used**: `auth/register.php`.

#### `password_verify($password, $hash)`
* **How it Works**: Securely verifies that a plain-text password matches a stored cryptographic hash in constant time, preventing timing attacks.
* **Where Used**: `auth/login.php`.

#### `htmlspecialchars($string, $flags = ENT_QUOTES)`
* **How it Works**: Converts special HTML characters (`<`, `>`, `&`, `"`, `'`) to their corresponding HTML entities (`&lt;`, `&gt;`, etc.), preventing Cross-Site Scripting (XSS).
* **Where Used**: All views rendering dynamic user-submitted strings (`title`, `description`, `name`, `status`, etc.).

#### `nl2br($string)`
* **How it Works**: Inserts HTML `<br>` tags before all newline characters (`\n` or `\r\n`) in a string so line breaks render in HTML.
* **Where Used**: `tasks/review_submission.php`, `tasks/task_details.php`.

---

### D. String, Array & Utility Functions

#### `trim($string)`
* **How it Works**: Strips whitespace, tabs, and newlines from the beginning and end of a string.
* **Where Used**: Input validation in all forms (`login.php`, `register.php`, `create_task.php`, `submit_work.php`, etc.).

#### `is_numeric($value)`
* **How it Works**: Determines whether a variable is a number or numeric string.
* **Where Used**: Parameter validation in `tasks/task_details.php`, `tasks/apply_task.php`, `tasks/manage_applications.php`, `tasks/submit_work.php`, `tasks/review_submission.php`.

#### `date($format, $timestamp = time())`
* **How it Works**: Formats a local date and time according to a given pattern string (e.g. `'Y-m-d'`).
* **Where Used**: `tasks/create_task.php` to calculate today's date for deadline validation.

#### `strtotime($time_string)`
* **How it Works**: Parses English textual datetime descriptions into Unix timestamps.
* **Where Used**: `tasks/create_task.php` (`strtotime('+1 day')`) to enforce that task deadlines are strictly in the future.

---

### E. HTTP & Execution Flow Functions

#### `header("Location: " . $url)`
* **How it Works**: Sends an HTTP `302 Found` or `301 Moved Permanently` header response instructing the client browser to navigate to a new URL.
* **Where Used**: Redirects across all authenticated workflows.

#### `exit()` / `die($message)`
* **How it Works**: Immediately terminates script execution. Ensures code beneath redirection headers does not execute.
* **Where Used**: Follows every `header("Location: ...")` redirect and fatal connection error.

#### `defined($name)` & `define($name, $value)`
* **How it Works**: `defined()` checks if a named global constant exists; `define()` registers a new global constant.
* **Where Used**: `config/database.php`, `includes/header1.php`, `includes/header2.php`, `includes/footer1.php`, `includes/footer2.php` to configure `BASE_URL = '/SkillSprout/'`.

---

## 2. File-by-File Breakdown: Inbuilt & Defined Code Blocks

### Deep Dive: `tasks/create_task.php`

`tasks/create_task.php` is the most important financial workflow in SkillSprout. Here is how its defined architectural blocks work:

```
[Block 1: Session & Login Check]
             ↓
[Block 2: Balance Fetch]
             ↓
[Block 3: POST Detection & Input Sanitization]
             ↓
[Block 4: Input & Deadline Validation]
             ↓
[Block 5: Balance Sufficiency Check]
             ↓
[Block 6: Transactional Escrow & Atomic Deduction]
             ↓
[Block 7: Task Insertion]
             ↓
[Block 8: Commit / Rollback & Feedback]
             ↓
[Block 9: UI Rendering with Dynamic Balance Badge & Restraints]
             ↓
[Block 10: Client-Side JS Overdraft Prevention]
```

#### Block 1: Session Initiation & Login Guard
```php
session_start();
include __DIR__ . "/../config/database.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];
```
* **Inbuilt Functions**: `session_start()`, `isset()`, `header()`, `exit()`.
* **Defined Work**: Boots session, imports database config, and prevents unauthenticated guests from accessing the form.

#### Block 2: Live Balance Retrieval
```php
$sql = "SELECT wp_balance FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$current_balance = $user ? (int) $user["wp_balance"] : 0;
```
* **Inbuilt Functions**: `mysqli_prepare()`, `mysqli_stmt_bind_param()`, `mysqli_stmt_execute()`, `mysqli_stmt_get_result()`, `mysqli_fetch_assoc()`, `mysqli_stmt_close()`.
* **Defined Work**: Queries the creator's live `wp_balance` so validation and UI badges reflect the accurate wallet state.

#### Block 3: Form Submission Detection & Input Normalization
```php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $domain = trim($_POST["domain"] ?? "");
    $required_skills = trim($_POST["required_skills"] ?? "");
    $reward_wp = isset($_POST["reward_wp"]) ? (int) $_POST["reward_wp"] : 0;
    $deadline = trim($_POST["deadline"] ?? "");
```
* **Inbuilt Functions**: `trim()`, `isset()`.
* **Defined Work**: Sanitizes inputs and casts `$reward_wp` to integer to prevent malformed or negative payloads.

#### Block 4 & 5: Validation & Balance Sufficiency Checks
```php
    $today = date("Y-m-d");
    if ($title === "" || $description === "" || $domain === "" || $reward_wp <= 0 || $deadline === "") {
        $message = "Please fill all required fields.";
    } elseif ($deadline <= $today) {
        $message = "The deadline must be selected after today only.";
    } elseif ($reward_wp > $current_balance) {
        $message = "Insufficient Work Points. You have " . $current_balance . " WP available, but tried to offer " . $reward_wp . " WP.";
    }
```
* **Inbuilt Functions**: `date()`.
* **Defined Work**: Ensures no fields are blank, validates `$reward_wp >= 1`, rejects deadlines on or before today, and prevents overdraft before contacting the database.

#### Block 6, 7 & 8: Transactional Escrow, Atomic Deduction & Task Insertion
```php
    mysqli_begin_transaction($conn);
    try {
        $deduct_sql = "UPDATE users SET wp_balance = wp_balance - ? WHERE user_id = ? AND wp_balance >= ?";
        $deduct_stmt = mysqli_prepare($conn, $deduct_sql);
        mysqli_stmt_bind_param($deduct_stmt, "iii", $reward_wp, $user_id, $reward_wp);
        mysqli_stmt_execute($deduct_stmt);

        if (mysqli_stmt_affected_rows($deduct_stmt) !== 1) {
            mysqli_stmt_close($deduct_stmt);
            mysqli_rollback($conn);
            $message = "Failed to deduct Work Points. You do not have enough balance.";
        } else {
            mysqli_stmt_close($deduct_stmt);
            $task_sql = "INSERT INTO tasks (creator_id, title, description, domain, required_skills, reward_wp, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'OPEN')";
            $task_stmt = mysqli_prepare($conn, $task_sql);
            mysqli_stmt_bind_param($task_stmt, "issssis", $user_id, $title, $description, $domain, $required_skills, $reward_wp, $deadline);
            if (mysqli_stmt_execute($task_stmt)) {
                mysqli_stmt_close($task_stmt);
                mysqli_commit($conn);
                $current_balance -= $reward_wp;
                $message = "Task created successfully! " . $reward_wp . " Work Points deducted from your balance.";
            } else {
                mysqli_stmt_close($task_stmt);
                mysqli_rollback($conn);
                $message = "Failed to create task. Points were not deducted.";
            }
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $message = "An error occurred while creating task. Points were not deducted.";
    }
```
* **Inbuilt Functions**: `mysqli_begin_transaction()`, `mysqli_stmt_affected_rows()`, `mysqli_commit()`, `mysqli_rollback()`.
* **Defined Work**: Executes the deduction atomically with `AND wp_balance >= ?`. If the user balance was modified concurrently, it aborts. If deduction succeeds, inserts the task and commits. On any error, it rolls back, keeping balance intact.

#### Block 9 & 10: Dynamic Balance Badge & Restraints UI
```html
<div class="balance-badge">
    Your Available Balance: <strong><?php echo (int) $current_balance; ?> WP</strong>
</div>
<input type="number" id="reward_wp" name="reward_wp" min="1" max="<?php echo (int) $current_balance; ?>" required>
```
* **Defined Work**: Visual badge showing real-time available balance and capping the number input with HTML5 `max` attribute.

#### Block 11: Client-Side JavaScript Validation
```javascript
function validateTaskForm() {
    var rewardInput = document.getElementById("reward_wp");
    var reward = parseInt(rewardInput.value, 10);
    var maxBalance = <?php echo (int) $current_balance; ?>;
    if (isNaN(reward) || reward <= 0) {
        alert("Please enter a valid reward amount (at least 1 WP).");
        return false;
    }
    if (reward > maxBalance) {
        alert("You do not have enough Work Points! You have " + maxBalance + " WP, but entered " + reward + " WP.");
        return false;
    }
    return confirm("Are you sure you want to post this task? " + reward + " WP will be deducted from your balance.");
}
```
* **Defined Work**: Traps accidental overdraft inputs before form submission and asks for user confirmation.

---

### Deep Dive: `tasks/review_submission.php`

#### Block 1: Submission Query & Guard
* **Defined Work**: Fetches submitted work joined with worker information where `tasks.task_id = ? AND tasks.creator_id = ? AND submissions.status = 'SUBMITTED'`. Prevents non-creators or unsubmitted tasks from opening.

#### Block 2: Dual Approval / Rejection Handler
* **Inbuilt Functions**: `mysqli_begin_transaction()`, `mysqli_stmt_affected_rows()`, `mysqli_commit()`, `mysqli_rollback()`.
* **Defined Work**:
  * **Approve Action**: Verifies `status = 'SUBMITTED'`, updates submission to `APPROVED`, adds reward points to worker (`wp_balance = wp_balance + ?`), and sets task to `COMPLETED`.
  * **Reject Action**: Updates submission to `REJECTED` and resets task status to `ASSIGNED` so worker can resubmit.

---

### Deep Dive: `tasks/cancel_task.php`

#### Block 1: Ownership & OPEN Status Guard
* **Defined Work**: Queries task where `task_id = ? AND creator_id = ?`. Verifies task status is `OPEN` (cannot cancel tasks already assigned to workers).

#### Block 2: Transactional Cancellation & Refund
* **Inbuilt Functions**: `mysqli_begin_transaction()`, `mysqli_commit()`, `mysqli_rollback()`.
* **Defined Work**:
  1. Sets task status to `CANCELLED`.
  2. Rejects any pending applications.
  3. **Refunds full escrowed reward** back to creator:
     ```sql
     UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?;
     ```
  4. Commits transaction and redirects to `user/my_work.php` with refund banner.

---

### Deep Dive: `tasks/manage_applications.php`

#### Block 1: Application Decision Block
* **Defined Work**:
  * **Accept**: In a transaction, updates selected application to `ACCEPTED`, bulk-updates all other pending applications for that task to `REJECTED`, and sets `tasks.status = 'ASSIGNED'` with `assigned_user_id`.
  * **Reject**: Marks selected application `REJECTED`.

---

### Deep Dive: `tasks/apply_task.php`

#### Block 1: Eligibility & Conflict Check Block
* **Defined Work**:
  - Checks if task status is `OPEN`.
  - Blocks self-applications (`creator_id == user_id`).
  - Checks if user has already applied via `SELECT application_id FROM applications WHERE task_id = ? AND user_id = ?`.

#### Block 2: Application Insertion Block
* **Defined Work**: Inserts applicant's message into `applications` with initial status `PENDING`.

---

### Deep Dive: `tasks/submit_work.php`

#### Block 1: Worker Authorization Block
* **Defined Work**: Verifies `assigned_user_id == user_id` and `status == 'ASSIGNED'`.

#### Block 2: Work Submission Block
* **Defined Work**: Inserts deliverable text into `submissions` (`status = 'SUBMITTED'`) and transitions `tasks.status` to `SUBMITTED`.

---

### Deep Dive: `auth/login.php`

#### Block 1: Guest Guard Block
* **Defined Work**: Checks `if (isset($_SESSION["user_id"]))`, redirecting already-logged-in users directly to dashboard.

#### Block 2: Credential Verification Block
* **Inbuilt Functions**: `password_verify()`.
* **Defined Work**: Queries user by email, verifies password against Bcrypt hash, stores user ID and name in session, and initiates client redirect.

---

### Deep Dive: `auth/register.php`

#### Block 1: Email Uniqueness & Password Hashing Block
* **Inbuilt Functions**: `password_hash()`.
* **Defined Work**: Ensures email is not already registered, hashes password using `PASSWORD_DEFAULT`, and inserts user with default `100 WP`.

---

### Deep Dive: `auth/logout.php`

#### Block 1: Session Wipe & Invalidation Block
* **Inbuilt Functions**: `setcookie()`, `session_destroy()`.
* **Defined Work**: Resets `$_SESSION = array()`, deletes session cookie, terminates session, and routes to login.

---

### Deep Dive: `user/dashboard.php`

#### Block 1: Metric Aggregation Block
* **Defined Work**: Loads user profile and Work Points balance, displaying personalized welcome message and direct quick actions.

---

### Deep Dive: `user/my_work.php`

#### Block 1: Three-Stream Query Aggregator
* **Defined Work**: Runs 3 separate SELECT queries in parallel: Created Tasks (with application counts), Submitted Applications, and Assigned Active Work.

#### Block 2: Dynamic Action Dispatcher
* **Defined Work**: Evaluates task status on each row to render contextual links ("Manage Applications", "Cancel & Refund", "Review Submission", "Submit Work").

---

### Deep Dive: `user/wallet.php` & `user/profile.php`

#### Block 1: Live Account Data Query Block
* **Defined Work**: Reads fresh balance and user registration timestamp for presentation.

---

### Deep Dive: `config/database.php`

#### Block 1: Centralized Database Connection
* **Inbuilt Functions**: `mysqli_connect()`, `mysqli_connect_error()`, `define()`, `defined()`.
* **Defined Work**: Exposes `$conn` and sets `BASE_URL` to `/SkillSprout/`.

---

### Deep Dive: `includes/header1.php` & `includes/header2.php`

#### Block 1: Unified Styling & Navigation
* **Defined Work**: `header1.php` renders guest links; `header2.php` acts as an authorization gate verifying `isset($_SESSION["user_id"])` and rendering user links.

---

## 3. Client-Side JavaScript Validation Blocks

### `assets/js/login.js`
* **Function `validateLoginForm()`**:
  * Trims email and tests with regex `/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/`.
  * Verifies password length $\ge 6$.
  * Displays user alerts and blocks submission if invalid.

### `assets/js/register.js`
* **Function `validateRegisterForm()`**:
  * Validates full name length $\ge 3$.
  * Validates email format with regex.
  * Validates password length $\ge 6$.
  * Verifies `password === confirmPassword`.
  * Alerts user and halts submission on mismatch.
