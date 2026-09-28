# SkillSprout - Page by Page Documentation

This document explains the work, role, inputs, workflows, database queries, and outputs of every single page in the **SkillSprout** application.

---

## Table of Contents
1. [Root Pages](#1-root-pages)
   - [index.php](#indexphp)
   - [about.php](#aboutphp)
   - [create_task.php](#create_taskphp-root)
2. [Authentication Module (`auth/`)](#2-authentication-module-auth)
   - [auth/login.php](#authloginphp)
   - [auth/register.php](#authregisterphp)
   - [auth/logout.php](#authlogoutphp)
3. [User Module (`user/`)](#3-user-module-user)
   - [user/dashboard.php](#userdashboardphp)
   - [user/my_work.php](#usermy_workphp)
   - [user/wallet.php](#userwalletphp)
   - [user/purchase_wallet.php](#userpurchase_walletphp)
   - [user/profile.php](#userprofilephp)
4. [Tasks Module (`tasks/`)](#4-tasks-module-tasks)
   - [tasks/tasks.php](#taskstasksphp)
   - [tasks/create_task.php](#taskscreate_taskphp)
   - [tasks/task_details.php](#taskstask_detailsphp)
   - [tasks/apply_task.php](#tasksapply_taskphp)
   - [tasks/manage_applications.php](#tasksmanage_applicationsphp)
   - [tasks/submit_work.php](#taskssubmit_workphp)
   - [tasks/review_submission.php](#tasksreview_submissionphp)
   - [tasks/cancel_task.php](#taskscancel_taskphp)
5. [Shared Includes (`includes/`)](#5-shared-includes-includes)
   - [includes/header1.php](#includesheader1php)
   - [includes/header2.php](#includesheader2php)
   - [includes/footer1.php](#includesfooter1php)
   - [includes/footer2.php](#includesfooter2php)
6. [Configuration & Schema](#6-configuration--schema)
   - [config/database.php](#configdatabasephp)
   - [database/database.sql](#databasedatabasesql)

---

## 1. Root Pages

### `index.php`
* **Purpose**: The public landing page showcasing what SkillSprout is, how the platform operates, popular domains, platform benefits, and registration call-to-actions.
* **Access Level**: Public (accessible to guests and members).
* **Work & Workflow**:
  1. Defines `BASE_URL` if not previously defined.
  2. Loads responsive inline CSS styling and sets hero image from `uploads/office1.jpeg`.
  3. Includes `includes/header1.php` for guest navigation.
  4. Renders the hero header with "Get Started" linking to registration.
  5. Displays a 4-step walkthrough: Create Task $\rightarrow$ Find Task $\rightarrow$ Complete Task $\rightarrow$ Earn Work Points.
  6. Showcases popular skill categories (Web Development, Graphic Design, Content Writing, Data Entry, Programming, Other Skills).
  7. Includes `includes/footer1.php` for links and copyright.

---

### `about.php`
* **Purpose**: Informational page describing the platform's vision, core principles, and community mission.
* **Access Level**: Public.
* **Work & Workflow**:
  1. Loads `config/database.php` for unified `BASE_URL` access.
  2. Includes `includes/header1.php`.
  3. Displays the core mission statement: providing a collaborative ecosystem where individuals learn skills, deliver high-quality work, and earn Work Points.
  4. Includes `includes/footer1.php`.

---

### `create_task.php` (Root)
* **Purpose**: Legacy compatibility redirector.
* **Access Level**: Public / Redirector.
* **Work & Workflow**:
  1. Intercepts any incoming direct requests for `/SkillSprout/create_task.php`.
  2. Issues an HTTP 301 Moved Permanently redirect to `tasks/create_task.php`.
  3. Terminates script execution cleanly.

---

## 2. Authentication Module (`auth/`)

### `auth/login.php`
* **Purpose**: Authenticates registered users with their email and password.
* **Access Level**: Public (if already logged in, redirects to `user/dashboard.php`).
* **Work & Workflow**:
  1. Starts session via `session_start()`.
  2. If `$_SESSION["user_id"]` is already present, immediately forwards to `user/dashboard.php`.
  3. On `POST` submission:
     - Trims email and captures raw password.
     - Prepares SQL query: `SELECT user_id, name, email, password FROM users WHERE email = ?`.
     - Executes query with bound parameters.
     - If matching user found, verifies password using `password_verify($password, $user["password"])`.
     - On password match: saves `user_id`, `name`, and `email` to `$_SESSION`, triggers a JavaScript success alert, and redirects to `user/dashboard.php`.
     - On mismatch: sets error message `"Invalid email or password."`.
  4. Renders login form connected to `assets/js/login.js` for client-side validation.

---

### `auth/register.php`
* **Purpose**: Onboards new users, encrypts credentials, and credits their starting wallet balance.
* **Access Level**: Public (if logged in, redirects to `user/dashboard.php`).
* **Work & Workflow**:
  1. Starts session. If logged in, redirects to `user/dashboard.php`.
  2. On `POST` submission:
     - Captures `name`, `email`, `password`.
     - Checks if email is already taken via prepared statement: `SELECT user_id FROM users WHERE email = ?`.
     - If email exists, warns `"Email already registered."`.
     - If email is unique, hashes password using `password_hash($password, PASSWORD_DEFAULT)` (secure Bcrypt).
     - Inserts record into `users` (`name`, `email`, `password`). The database schema automatically assigns the initial default `wp_balance = 100`.
     - On successful insertion, displays alert `"Registration successful! You can now login."` and forwards to `auth/login.php`.
  3. Renders registration form with client-side validation via `assets/js/register.js`.

---

### `auth/logout.php`
* **Purpose**: Securely logs out the user and destroys all active session states.
* **Access Level**: Authenticated users.
* **Work & Workflow**:
  1. Starts session.
  2. Wipes `$_SESSION` array: `$_SESSION = array();`.
  3. Deletes session cookie in browser by setting its expiration to the past.
  4. Destroys server-side session via `session_destroy()`.
  5. Redirects user to `auth/login.php`.

---

## 3. User Module (`user/`)

### `user/dashboard.php`
* **Purpose**: Primary control panel for logged-in members.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Starts session; verifies authentication.
  2. Queries user record (`name`, `email`, `wp_balance`) from `users` table for current `$_SESSION["user_id"]`.
  3. Includes `includes/header2.php`.
  4. Renders personal greeting banner: `Welcome back, [Name]!`.
  5. Provides Quick Action cards:
     - **Find Tasks**: Link to `tasks/tasks.php`.
     - **Create Task**: Link to `tasks/create_task.php`.
     - **My Work**: Link to `user/my_work.php`.
  6. Displays overview metrics including live Work Points balance.

---

### `user/my_work.php`
* **Purpose**: Centralized dashboard to track tasks created by the user, applications submitted by the user, and active assigned tasks.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Queries 3 separate datasets:
     - **Created Tasks**: `SELECT ... FROM tasks WHERE creator_id = ?` with `COUNT(applications)` joined.
     - **My Applications**: `SELECT ... FROM applications INNER JOIN tasks WHERE applications.user_id = ? AND status = 'PENDING'`.
     - **My Active Work**: `SELECT ... FROM tasks WHERE assigned_user_id = ? AND status = 'ASSIGNED'`.
  2. Displays Created Tasks table with dynamic contextual actions:
     - If `OPEN`: Displays "Manage Applications" link and "Cancel & Refund" button.
     - If `SUBMITTED`: Displays "Review Submission" link.
     - If other status: Displays "View Task" link.
  3. Displays My Applications table with pending application details and task links.
  4. Displays Active Work table with "Submit Work" link for tasks assigned to the user.
  5. Shows dynamic success notification if a task was recently cancelled and refunded.

---

### `user/wallet.php`
* **Purpose**: Dedicated balance management page showing available Work Points, instant top-up link, and transaction history.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Automatically verifies that the `transactions` table exists.
  2. Queries latest `wp_balance` for current user from `users` table.
  3. Queries recent top-up records from `transactions` table ordered by `created_at DESC`.
  4. If redirected from a purchase (`success=purchased`), displays a green success confirmation banner with the added points.
  5. Displays wallet card with large formatted points display: `X WP`.
  6. Provides primary action button: **"+ Purchase Work Points"** linking directly to `user/purchase_wallet.php`.
  7. Provides secondary shortcut buttons: "Find Tasks" and "Create Task".
  8. Renders the "Purchase & Top-up History" table showing date, WP added, price paid, payment method, and completion status.

---

### `user/purchase_wallet.php`
* **Purpose**: Checkout and package selection page allowing users to buy Work Points and top-up their wallet.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Queries user identity and live balance.
  2. Exchange rate: **₹1 Rupee = 1 WorkPoint (1:1 ratio)**.
  3. Presents interactive Work Points packages:
     - **Starter Pack**: 100 WP (₹100.00)
     - **Popular Pack**: 250 WP (₹250.00)
     - **Standard Pack**: 500 WP (₹500.00)
     - **Pro Pack**: 1,000 WP (₹1,000.00)
     - **Business Pack**: 2,500 WP (₹2,500.00)
     - **Custom Amount**: User-defined WP input (minimum 10 WP = ₹10, up to 50,000 WP = ₹50,000).
  4. Provides dynamic option-specific payment panels:
     - **UPI**: Requires UPI ID / VPA (`name@bank`) and UPI app selection.
     - **Card**: Requires Cardholder Name, 16-digit Card Number, MM/YY Expiry, 3-digit CVV.
     - **Net Banking**: Requires Bank selection from major Indian banks and Customer/User ID.
     - **Mobile Wallet**: Requires Wallet provider selection and 10-digit Indian mobile number.
  5. JavaScript dynamically toggles field visibility and enables `required` / `disabled` attributes strictly for the active payment option.
  6. On form submission (`POST`):
     - Validates package or custom amount ($10 \le \text{WP} \le 50,000$) where $\text{price} = \text{WP amount}$.
     - Validates required inputs according to selected payment method (`upi`, `card`, `netbanking`, `wallet`).
     - Begins database transaction (`mysqli_begin_transaction`).
     - Atomically credits wallet: `UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?`.
     - Logs transaction into `transactions` table with specific payment details (`UPI (id via app)`, `Card (name - last4)`, `Net Banking (bank - id)`, `Wallet (provider - mobile)`).
     - Commits transaction (`mysqli_commit`).
     - Redirects back to `user/wallet.php?success=purchased&wp=[amount_wp]&rupees=[price_paid]`.

---

### `user/profile.php`
* **Purpose**: Displays user account information, membership registration timestamp, and account options.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Fetches user details: `name`, `email`, `wp_balance`, `created_at`.
  2. Includes `includes/header2.php`.
  3. Presents clean read-only table with account details.
  4. Provides logout action button.

---

## 4. Tasks Module (`tasks/`)

### `tasks/tasks.php`
* **Purpose**: The public task directory listing all tasks currently accepting applications.
* **Access Level**: Authenticated only (unauthenticated users redirected to login).
* **Work & Workflow**:
  1. Queries `SELECT task_id, title, description, domain, required_skills, reward_wp, deadline, created_at FROM tasks WHERE status = 'OPEN' ORDER BY created_at DESC`.
  2. Includes `includes/header2.php`.
  3. Renders available tasks table displaying title, category domain, reward points, and completion deadline.
  4. Provides "View Details" link for each task linking to `tasks/task_details.php?id=[task_id]`.
  5. Displays fallback notice if no tasks are currently open.

---

### `tasks/create_task.php`
* **Purpose**: Form to post a new task with strict input validation and atomic escrow balance deduction.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Queries creator's live balance: `SELECT wp_balance FROM users WHERE user_id = ?`.
  2. On `POST` form submission:
     - Sanitizes `title`, `description`, `domain`, `required_skills`, `reward_wp`, and `deadline`.
     - Validates all fields are populated and `deadline > today`.
     - Validates `$reward_wp > 0` and `$reward_wp <= $current_balance`.
     - Initiates database transaction: `mysqli_begin_transaction($conn)`.
     - **Atomic Deduction**:
       ```sql
       UPDATE users SET wp_balance = wp_balance - ? WHERE user_id = ? AND wp_balance >= ?;
       ```
     - Checks `affected_rows === 1`. If balance check fails, rolls back immediately.
     - **Task Creation**: Inserts task record into `tasks` with `status = 'OPEN'`.
     - If insert succeeds: Commits transaction (`mysqli_commit`), updates in-memory balance, and sets success message with amount deducted.
     - If insert fails: Rolls back (`mysqli_rollback`), restoring balance.
  3. Renders task creation form with:
     - Live available balance badge: `Your Available Balance: X WP`.
     - Input restrictions (`max="<?= $current_balance ?>"`, `min="1"`).
     - Client-side validation alerting user if they exceed available balance.
     - Form disabling if balance is `0 WP` with link to find tasks.

---

### `tasks/task_details.php`
* **Purpose**: Detailed inspection page for an individual task.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Reads `$_GET["id"]`; validates integer.
  2. Queries task details joined with creator name: `INNER JOIN users ON tasks.creator_id = users.user_id`.
  3. If task not found, redirects to `tasks/tasks.php`.
  4. Dynamically evaluates viewer relationship:
     - **If Creator**:
       - If `OPEN`: Displays "Manage Applications" button and "Cancel Task (Refund X WP)" button.
       - If not `OPEN`: Displays task lifecycle status notification.
     - **If Worker / Other User**:
       - If `OPEN`: Displays "Apply for Task" button.
       - If `ASSIGNED`, `SUBMITTED`, or `COMPLETED`: Displays informational badge indicating current progress.

---

### `tasks/apply_task.php`
* **Purpose**: Allows a worker to apply for an open task by submitting an application proposal.
* **Access Level**: Authenticated only.
* **Work & Workflow**:
  1. Reads `$_GET["id"]` and validates existence.
  2. Security guards:
     - Verifies task status is `OPEN`.
     - Prevents creators from applying to their own tasks (`creator_id != user_id`).
     - Prevents duplicate applications (`SELECT application_id FROM applications WHERE task_id = ? AND user_id = ?`).
  3. On `POST`:
     - Sanitizes message input.
     - Inserts record into `applications` (`task_id`, `user_id`, `message`, `status = 'PENDING'`).
     - Redirects back to `tasks/task_details.php?id=[task_id]` with confirmation.
  4. Renders application proposal form.

---

### `tasks/manage_applications.php`
* **Purpose**: Creator control center to review applicants and assign the task to one worker.
* **Access Level**: Authenticated task creator only.
* **Work & Workflow**:
  1. Verifies current user is the author of the task (`creator_id == user_id`).
  2. On `POST` with `action == "accept"`:
     - Starts transaction.
     - Sets chosen application status to `ACCEPTED`.
     - Sets all other pending applications for this task to `REJECTED`.
     - Sets task status to `ASSIGNED` and records `assigned_user_id`.
     - Commits transaction.
  3. On `POST` with `action == "reject"`:
     - Sets chosen application status to `REJECTED`.
  4. Queries and renders all applications submitted for this task with applicant names and messages.

---

### `tasks/submit_work.php`
* **Purpose**: Deliverable submission form for the assigned worker.
* **Access Level**: Assigned worker only.
* **Work & Workflow**:
  1. Validates that task exists, is assigned to the current user (`assigned_user_id == user_id`), and is in `ASSIGNED` status.
  2. On `POST`:
     - Sanitizes `submission_text`.
     - Inserts record into `submissions` (`task_id`, `user_id`, `submission_text`, `status = 'SUBMITTED'`).
     - Updates task status: `UPDATE tasks SET status = 'SUBMITTED' WHERE task_id = ? AND assigned_user_id = ?`.
     - Redirects worker to `user/my_work.php`.
  3. Renders delivery submission textarea and guidelines.

---

### `tasks/review_submission.php`
* **Purpose**: Task creator's review portal to inspect deliverables, disburse reward points, or request revisions.
* **Access Level**: Task creator only.
* **Work & Workflow**:
  1. Queries task and submission record where `creator_id == user_id` and `submissions.status = 'SUBMITTED'`.
  2. On `POST` with `action == "approve"`:
     - Starts transaction.
     - Verifies submission is currently `SUBMITTED` and updates status to `APPROVED` (prevents double payout).
     - **Disburses reward points to worker**:
       ```sql
       UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?;
       ```
     - Marks task as `COMPLETED`:
       ```sql
       UPDATE tasks SET status = 'COMPLETED' WHERE task_id = ?;
       ```
     - Commits transaction and redirects to `user/my_work.php`.
  3. On `POST` with `action == "reject"`:
     - Starts transaction.
     - Marks submission as `REJECTED`.
     - Reverts task status to `ASSIGNED` so worker can revise and resubmit.
     - Commits transaction and redirects to `user/my_work.php`.
  4. Displays submitted text, worker identity, and action buttons ("Approve Work & Pay X WP" and "Reject Submission").

---

### `tasks/cancel_task.php`
* **Purpose**: Safely cancels an unassigned open task and refunds the creator's points.
* **Access Level**: Task creator only.
* **Work & Workflow**:
  1. Accepts `POST` with `task_id`.
  2. Verifies task belongs to current user and is currently `OPEN`.
  3. Starts transaction:
     - Updates task: `UPDATE tasks SET status = 'CANCELLED' WHERE task_id = ? AND creator_id = ? AND status = 'OPEN'`.
     - Rejects any pending applications: `UPDATE applications SET status = 'REJECTED' WHERE task_id = ? AND status = 'PENDING'`.
     - **Refunds escrowed points**:
       ```sql
       UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?;
       ```
     - Commits transaction.
  4. Redirects to `user/my_work.php?cancelled=1&refund=[amount]`.

---

## 5. Shared Includes (`includes/`)

### `includes/header1.php`
* **Purpose**: Global public navigation header for guests.
* **Work & Workflow**:
  - Ensures `BASE_URL` is defined as `/SkillSprout/`.
  - Links to `assets/css/style.css`.
  - Displays website logo and links: Home, Tasks, About, Login, Register.

---

### `includes/header2.php`
* **Purpose**: Authenticated user navigation bar and authorization firewall.
* **Work & Workflow**:
  - Ensures `BASE_URL` is defined as `/SkillSprout/`.
  - Checks if session is started; starts it if none exists.
  - **Authorization Gate**: Checks `isset($_SESSION["user_id"])`. If missing, immediately redirects to `auth/login.php` and exits.
  - Links to `assets/css/style.css`.
  - Displays authenticated user navigation: Home (Dashboard), Tasks, My Work, Wallet, Profile, Logout.

---

### `includes/footer1.php`
* **Purpose**: Public footer component with links, contact info, and copyright.

---

### `includes/footer2.php`
* **Purpose**: Authenticated user footer with dashboard shortcuts and contact information.

---

## 6. Configuration & Schema

### `config/database.php`
* **Purpose**: Centralized database connection, SSL configuration, and environment-aware `BASE_URL`.
* **Work & Workflow**:
  - Dynamically extracts credentials from `DATABASE_URL`, `MYSQL_URL`, or individual `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_PORT` variables.
  - Automatically falls back to local XAMPP configuration (`localhost:3306`, `root`, `""`, `workpoint`).
  - Supports SSL encryption (`MYSQLI_CLIENT_SSL`) for cloud providers like TiDB Cloud, Aiven, or PlanetScale.
  - Loads and registers the serverless session handler (`config/session.php`).
  - Automatically resolves `BASE_URL` to `/` for Vercel and `/SkillSprout/` for local XAMPP.

---

### `config/session.php`
* **Purpose**: Database-backed session save handler (`SkillSproutSessionHandler`) implementing PHP's `SessionHandlerInterface`.
* **Work & Workflow**:
  - Automatically creates the `sessions` table in MySQL (`CREATE TABLE IF NOT EXISTS sessions ...`).
  - Intercepts `session_start()`, `session_write_close()`, and `session_destroy()`.
  - Serializes and deserializes session state directly to/from MySQL, allowing seamless logins across stateless, ephemeral Vercel serverless containers.

---

### `api/index.php`
* **Purpose**: Vercel Serverless Function entry point and front controller.
* **Work & Workflow**:
  - Captures incoming `REQUEST_URI` and normalizes the target path.
  - Resolves clean extensionless URLs (e.g. `/login`, `/dashboard`, `/tasks`, `/create-task`).
  - Provides a static file fallback handler with MIME type headers for assets.
  - Boots database and session handlers before handing control over to the target PHP page script.

---

### `database/database.sql`
* **Purpose**: DDL setup script defining database tables and relational constraints:
  - `users`: User identity and `wp_balance` (DEFAULT 100).
  - `tasks`: Task attributes, reward amount, deadline, status enum, foreign keys to `users`.
  - `applications`: Application messages, status enum (`PENDING`, `ACCEPTED`, `REJECTED`), foreign keys to `tasks` and `users`.
  - `submissions`: Worker deliverable text, status enum (`SUBMITTED`, `APPROVED`, `REJECTED`), foreign keys to `tasks` and `users`.
  - `sessions`: Serverless session persistence (`id`, `data`, `last_activity`).

