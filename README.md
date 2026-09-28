# SkillSprout - Collaborative Task & Skill Exchange Platform

> **Turn your skills into currency, one task at a time.**

SkillSprout is a dynamic, full-stack PHP/MySQL web application where users post real-world tasks, offer **Work Points (WP)** as rewards, apply for tasks matching their skill sets, submit completed deliverables, and earn Work Points upon approval. The platform features an atomic escrow balance control engine ensuring reliable, non-inflationary token flow across task lifecycles.

---

## 1. System Overview & Architecture

SkillSprout is structured around modular domain folders, shared includes, and a centralized configuration:

```
SkillSprout/
├── .htaccess                   # Apache URL rewriting, clean URLs, and legacy redirects
├── index.php                   # Public landing page with platform overview & call to action
├── about.php                   # Mission statement and platform details
├── create_task.php             # Legacy root redirector to tasks/create_task.php
├── README.md                   # Complete system and function documentation
├── PAGES.md                    # In-depth guide detailing every page one-by-one
├── FUNCTIONS_AND_BLOCKS.md     # Detailed breakdown of inbuilt & custom functions and code blocks
├── config/
│   └── database.php            # MySQLi connection handle & dynamic BASE_URL definition
├── database/
│   └── database.sql            # Database schema, tables, foreign keys, and default balances
├── auth/
│   ├── login.php               # User login with secure password verification & redirect
│   ├── register.php            # New user registration with password hashing & 100 WP bonus
│   └── logout.php              # Session invalidation, cookie clearing & safe redirect
├── user/
│   ├── dashboard.php           # User hub with quick action cards & balance summary
│   ├── my_work.php             # Consolidated dashboard for created tasks, applications & assigned work
│   ├── wallet.php              # Dedicated Work Points balance display, history & top-up shortcuts
│   ├── purchase_wallet.php     # Work Points package purchase checkout & balance top-up
│   └── profile.php             # User profile details and account management
├── tasks/
│   ├── tasks.php               # Browse all OPEN tasks available across domains
│   ├── create_task.php         # Task creation form with atomic escrow balance deduction
│   ├── task_details.php        # Comprehensive task view with context-aware action buttons
│   ├── apply_task.php          # Worker application submission with pitch message
│   ├── manage_applications.php # Task creator portal to accept worker or reject applications
│   ├── submit_work.php         # Assigned worker deliverable submission interface
│   ├── review_submission.php   # Creator submission evaluation with payout or revision reject
│   └── cancel_task.php         # Immediate task cancellation with full escrow refund
├── includes/
│   ├── header1.php             # Public header for unauthenticated guests
│   ├── header2.php             # Authenticated header with user nav & session guard
│   ├── footer1.php             # Public footer with quick links and contact info
│   └── footer2.php             # Authenticated user footer with dashboard navigation
└── assets/
    ├── css/style.css           # Global unified responsive stylesheet
    ├── js/login.js             # Client-side validation for login credentials
    ├── js/register.js          # Client-side validation for user registration
    ├── images/office1.jpeg     # Hero banner background asset
    └── uploads/office1.jpeg    # Uploaded imagery directory
```

---

## 2. Core Functional Modules

### A. Authentication & Session Security
* **User Registration (`auth/register.php`)**: Validates name length ($\ge 3$), email format, password matching, and length ($\ge 6$). Hashes passwords using `password_hash($password, PASSWORD_DEFAULT)` (Bcrypt) and grants an initial bonus of `100 WP`.
* **User Login (`auth/login.php`)**: Prepared statements prevent SQL injection. Passwords verified using `password_verify()`. On success, sessions initialize `$_SESSION["user_id"]`, `$_SESSION["name"]`, and `$_SESSION["email"]`.
* **Session Guard (`includes/header2.php`)**: Automatically verifies `isset($_SESSION["user_id"])`. Unauthorized requests are redirected to `auth/login.php`.
* **User Logout (`auth/logout.php`)**: Clears `$_SESSION`, destroys the session cookie via `setcookie()`, invokes `session_destroy()`, and routes to `auth/login.php`.

### B. Atomic Work Points Balance Engine (Escrow System)
The platform uses strict transactional integrity (`mysqli_begin_transaction`, `mysqli_commit`, `mysqli_rollback`) to ensure Work Points cannot be duplicated or lost:
1. **Creation Escrow (`tasks/create_task.php`)**: When creating a task, the reward is verified against `wp_balance`. Points are atomically deducted using:
   ```sql
   UPDATE users SET wp_balance = wp_balance - ? WHERE user_id = ? AND wp_balance >= ?;
   ```
   If the user has insufficient points, `mysqli_stmt_affected_rows()` returns `0`, rolling back the transaction.
2. **Approval Payout (`tasks/review_submission.php`)**: When the creator approves work, the worker receives the reward:
   ```sql
   UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?;
   ```
   The submission is marked `APPROVED`, and the task status transitions to `COMPLETED`. Double payouts are blocked via `WHERE status = 'SUBMITTED'`.
3. **Cancellation Refund (`tasks/cancel_task.php`)**: If a creator cancels an `OPEN` task, the task is marked `CANCELLED`, pending applications are set to `REJECTED`, and the escrowed reward is refunded back to the creator:
   ```sql
   UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?;
   ```

### C. Task Management & Collaboration Lifecycle
* **Task Exploration (`tasks/tasks.php`)**: Displays all tasks with `status = 'OPEN'`, showcasing domain, reward, deadline, and required skills.
* **Task Details (`tasks/task_details.php`)**: Dynamically alters visible actions based on whether the logged-in viewer is the creator or a worker.
* **Application System (`tasks/apply_task.php`)**: Workers submit an application pitch. Guards ensure users cannot apply for their own tasks or apply twice.
* **Applicant Selection (`tasks/manage_applications.php`)**: Creator accepts one applicant, which atomically assigns the worker (`status = 'ASSIGNED'`) and rejects all remaining pending applications.
* **Work Delivery (`tasks/submit_work.php`)**: Assigned worker submits their work notes or links. The task status updates to `SUBMITTED`.
* **Work Review (`tasks/review_submission.php`)**: The creator reviews submitted work with two choices:
  * **Approve & Pay**: Transfers WP to worker and completes task.
  * **Reject Submission**: Marks submission `REJECTED` and resets task to `ASSIGNED` so the worker can revise.

---

## 3. Database Schema Overview

The database uses InnoDB tables with foreign key constraints:

| Table | Primary Key | Description |
|---|---|---|
| `users` | `user_id` | Stores user credentials, hashed passwords, and `wp_balance` (default 100). |
| `tasks` | `task_id` | Tasks with foreign keys to `creator_id` and `assigned_user_id`, reward amount, status enum (`OPEN`, `ASSIGNED`, `SUBMITTED`, `COMPLETED`, `CANCELLED`). |
| `applications` | `application_id` | Worker applications linked to `task_id` and `user_id`, with status enum (`PENDING`, `ACCEPTED`, `REJECTED`). |
| `submissions` | `submission_id` | Deliverables submitted by workers with status enum (`SUBMITTED`, `APPROVED`, `REJECTED`). |
| `transactions` | `transaction_id` | Top-up and purchase logs with `user_id`, `amount_wp`, `price_paid`, and `payment_method`. |
| `sessions` | `id` | Serverless persistent session storage for Vercel deployment. |

---

## 4. URL Mapping & Routing Configuration (`.htaccess`)

The `.htaccess` configuration running under Apache mod_rewrite provides:
* **Directory Index Protection**: `Options -Indexes` disables open directory browsing.
* **Feature Directory Defaults**: Visiting `/auth/`, `/user/`, or `/tasks/` automatically 301-redirects to `login.php`, `dashboard.php`, and `tasks.php`.
* **Legacy Root File 301 Redirects**: Redirects legacy root paths (`/login.php`, `/register.php`, `/dashboard.php`, `/tasks.php`, etc.) to organized subdirectories while preserving query strings (`QSA`).
* **Clean Extensionless URLs**: Routes `/login`, `/register`, `/dashboard`, `/my-work`, `/tasks`, `/about`, `/home` cleanly without `.php` extensions.

---

## 5. Getting Started & Local Installation

1. Place the project folder into `C:/xampp/htdocs/SkillSprout`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Import `database/database.sql` into MySQL via phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI.
4. Access the application in your browser:
   ```
   http://localhost/SkillSprout/
   ```

---

## 6. Vercel Deployment

SkillSprout is fully configured for serverless deployment on **Vercel** with:
* Native PHP serverless execution via `vercel-php@0.9.0` configured in `vercel.json`.
* Unified serverless front controller at `api/index.php`.
* Cloud database connection support with SSL encryption (TiDB Cloud, Aiven, Railway, AWS RDS).
* Serverless database-backed session handler maintaining state across ephemeral containers.
* Dynamic base URL auto-resolution for both local XAMPP and production domains.

For a complete step-by-step walkthrough, see [VERCEL_DEPLOYMENT.md](file:///c:/xampp/htdocs/SkillSprout/VERCEL_DEPLOYMENT.md).

