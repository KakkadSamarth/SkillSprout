<?php

if (!isset($conn) || !$conn instanceof mysqli) {
    $db_url = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');

    if ($db_url) {
        $parsed_url = parse_url($db_url);
        $host = $parsed_url['host'] ?? 'localhost';
        $port = isset($parsed_url['port']) ? (int)$parsed_url['port'] : 3306;
        $username = isset($parsed_url['user']) ? rawurldecode($parsed_url['user']) : 'root';
        $password = isset($parsed_url['pass']) ? rawurldecode($parsed_url['pass']) : '';
        $raw_db = isset($parsed_url['path']) ? ltrim($parsed_url['path'], '/') : 'workpoint';
        $database = rawurldecode(explode('?', $raw_db)[0]);
    } else {
        $host = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost');
        $username = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
        $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : '');
        $database = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'workpoint');
        $port = (int)(getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: 3306));
    }

    $conn = mysqli_init();

    $is_remote = ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== '');
    $use_ssl = getenv('DB_SSL') === 'true' || getenv('MYSQL_SSL') === 'true' || $is_remote;

    if ($use_ssl && defined('MYSQLI_CLIENT_SSL')) {
        mysqli_ssl_set($conn, NULL, NULL, getenv('DB_SSL_CA') ?: NULL, NULL, NULL);
        $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port, NULL, MYSQLI_CLIENT_SSL);
        if (!$connected) {
            $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port);
        }
    } else {
        $connected = @mysqli_real_connect($conn, $host, $username, $password, $database, $port);
    }

    if (!$connected) {
        $conn_err = mysqli_connect_error();
        error_log("Database connection failed: " . $conn_err);
        if (getenv('VERCEL')) {
            die("Database connection failed (" . htmlspecialchars($conn_err) . "). Please configure your MySQL database credentials in Vercel Project Settings (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT or DATABASE_URL).");
        } else {
            die("Database connection failed: " . htmlspecialchars($conn_err) . ". Please check your database configuration.");
        }
    }

    // 1. Ensure users table and columns
    $tbl_check = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if ($tbl_check && mysqli_num_rows($tbl_check) > 0) {
        $cols = [];
        $col_res = @mysqli_query($conn, "SHOW COLUMNS FROM users");
        if ($col_res) {
            while ($c = mysqli_fetch_assoc($col_res)) {
                $cols[$c['Field']] = true;
            }
        }
        if (!isset($cols['name'])) {
            $has_uname = isset($cols['username']);
            $has_fname = isset($cols['full_name']);
            if ($has_uname) {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = `username` WHERE `name` = '' OR `name` IS NULL");
            } elseif ($has_fname) {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = `full_name` WHERE `name` = '' OR `name` IS NULL");
            } else {
                @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `user_id`");
                @mysqli_query($conn, "UPDATE users SET `name` = SUBSTRING_INDEX(`email`, '@', 1) WHERE `name` = '' OR `name` IS NULL");
            }
        }
        if (!isset($cols['wp_balance'])) {
            @mysqli_query($conn, "ALTER TABLE users ADD COLUMN `wp_balance` INT NOT NULL DEFAULT 100 AFTER `password`");
        }
    }

    // 2. Ensure tasks table and columns
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tasks` (
        `task_id` INT AUTO_INCREMENT PRIMARY KEY,
        `creator_id` INT NOT NULL,
        `assigned_user_id` INT NULL DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `domain` VARCHAR(100) NOT NULL DEFAULT 'General',
        `required_skills` TEXT NULL,
        `reward_wp` INT NOT NULL DEFAULT 10,
        `deadline` DATE NOT NULL,
        `status` ENUM('OPEN', 'ASSIGNED', 'SUBMITTED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'OPEN',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $t_check = @mysqli_query($conn, "SHOW TABLES LIKE 'tasks'");
    if ($t_check && mysqli_num_rows($t_check) > 0) {
        $t_cols = [];
        $tcol_res = @mysqli_query($conn, "SHOW COLUMNS FROM tasks");
        if ($tcol_res) {
            while ($c = mysqli_fetch_assoc($tcol_res)) {
                $t_cols[$c['Field']] = true;
            }
        }

        if (!isset($t_cols['creator_id'])) {
            if (isset($t_cols['user_id'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `creator_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
                @mysqli_query($conn, "UPDATE tasks SET `creator_id` = `user_id`");
            } else {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `creator_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
            }
        }
        if (isset($t_cols['user_id'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `user_id` INT NULL DEFAULT NULL");
        }

        if (!isset($t_cols['assigned_user_id'])) {
            @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `assigned_user_id` INT NULL DEFAULT NULL AFTER `creator_id`");
        }

        if (!isset($t_cols['domain'])) {
            if (isset($t_cols['category'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `domain` VARCHAR(100) NOT NULL DEFAULT 'General' AFTER `description`");
                @mysqli_query($conn, "UPDATE tasks SET `domain` = `category` WHERE `domain` = 'General' OR `domain` = ''");
            } else {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `domain` VARCHAR(100) NOT NULL DEFAULT 'General' AFTER `description`");
            }
        }
        if (isset($t_cols['category'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `category` VARCHAR(100) NULL DEFAULT 'General'");
        }

        if (!isset($t_cols['required_skills'])) {
            if (isset($t_cols['skills'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `required_skills` TEXT NULL AFTER `domain`");
                @mysqli_query($conn, "UPDATE tasks SET `required_skills` = `skills`");
            } else {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `required_skills` TEXT NULL AFTER `domain`");
            }
        }
        if (isset($t_cols['skills'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `skills` TEXT NULL");
        }

        if (!isset($t_cols['reward_wp'])) {
            if (isset($t_cols['reward'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `reward_wp` INT NOT NULL DEFAULT 10 AFTER `required_skills`");
                @mysqli_query($conn, "UPDATE tasks SET `reward_wp` = `reward`");
            } elseif (isset($t_cols['points'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `reward_wp` INT NOT NULL DEFAULT 10 AFTER `required_skills`");
                @mysqli_query($conn, "UPDATE tasks SET `reward_wp` = `points`");
            } elseif (isset($t_cols['work_points'])) {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `reward_wp` INT NOT NULL DEFAULT 10 AFTER `required_skills`");
                @mysqli_query($conn, "UPDATE tasks SET `reward_wp` = `work_points`");
            } else {
                @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `reward_wp` INT NOT NULL DEFAULT 10 AFTER `required_skills`");
            }
        }
        if (isset($t_cols['reward'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `reward` INT NULL DEFAULT 10");
        }
        if (isset($t_cols['points'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `points` INT NULL DEFAULT 10");
        }
        if (isset($t_cols['work_points'])) {
            @mysqli_query($conn, "ALTER TABLE tasks MODIFY COLUMN `work_points` INT NULL DEFAULT 10");
        }

        if (!isset($t_cols['deadline'])) {
            @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `deadline` DATE NULL AFTER `reward_wp`");
            @mysqli_query($conn, "UPDATE tasks SET `deadline` = DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY) WHERE `deadline` IS NULL");
        }

        if (!isset($t_cols['status'])) {
            @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'OPEN' AFTER `deadline`");
        }

        if (!isset($t_cols['created_at'])) {
            @mysqli_query($conn, "ALTER TABLE tasks ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    }

    // 3. Ensure applications table and columns
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `applications` (
        `application_id` INT AUTO_INCREMENT PRIMARY KEY,
        `task_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `message` TEXT NULL,
        `status` ENUM('PENDING', 'ACCEPTED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $app_check = @mysqli_query($conn, "SHOW TABLES LIKE 'applications'");
    if ($app_check && mysqli_num_rows($app_check) > 0) {
        $app_cols = [];
        $app_col_res = @mysqli_query($conn, "SHOW COLUMNS FROM applications");
        if ($app_col_res) {
            while ($ac = mysqli_fetch_assoc($app_col_res)) {
                $app_cols[$ac['Field']] = true;
            }
        }

        if (!isset($app_cols['application_id'])) {
            if (isset($app_cols['id'])) {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `application_id` INT NOT NULL DEFAULT 0 FIRST");
                @mysqli_query($conn, "UPDATE applications SET `application_id` = `id`");
            } else {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `application_id` INT AUTO_INCREMENT PRIMARY KEY FIRST");
            }
        }

        if (!isset($app_cols['task_id'])) {
            @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `task_id` INT NOT NULL DEFAULT 1");
        }

        if (!isset($app_cols['user_id'])) {
            $user_src = null;
            if (isset($app_cols['applicant_id'])) $user_src = 'applicant_id';
            elseif (isset($app_cols['worker_id'])) $user_src = 'worker_id';
            elseif (isset($app_cols['candidate_id'])) $user_src = 'candidate_id';
            elseif (isset($app_cols['student_id'])) $user_src = 'student_id';

            if ($user_src) {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
                @mysqli_query($conn, "UPDATE applications SET `user_id` = `$user_src`");
            } else {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
            }
        }

        if (!isset($app_cols['message'])) {
            $msg_src = null;
            if (isset($app_cols['proposal'])) $msg_src = 'proposal';
            elseif (isset($app_cols['cover_letter'])) $msg_src = 'cover_letter';
            elseif (isset($app_cols['description'])) $msg_src = 'description';

            if ($msg_src) {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `message` TEXT NULL AFTER `user_id`");
                @mysqli_query($conn, "UPDATE applications SET `message` = `$msg_src`");
            } else {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `message` TEXT NULL AFTER `user_id`");
            }
        }

        if (!isset($app_cols['status'])) {
            @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'PENDING' AFTER `message`");
        }

        if (!isset($app_cols['created_at'])) {
            if (isset($app_cols['applied_at'])) {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                @mysqli_query($conn, "UPDATE applications SET `created_at` = `applied_at`");
            } else {
                @mysqli_query($conn, "ALTER TABLE applications ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            }
        }
    }

    // 4. Ensure submissions table and columns
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `submissions` (
        `submission_id` INT AUTO_INCREMENT PRIMARY KEY,
        `task_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `submission_text` TEXT NOT NULL,
        `status` ENUM('SUBMITTED', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'SUBMITTED',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sub_check = @mysqli_query($conn, "SHOW TABLES LIKE 'submissions'");
    if ($sub_check && mysqli_num_rows($sub_check) > 0) {
        $sub_cols = [];
        $sub_col_res = @mysqli_query($conn, "SHOW COLUMNS FROM submissions");
        if ($sub_col_res) {
            while ($sc = mysqli_fetch_assoc($sub_col_res)) {
                $sub_cols[$sc['Field']] = true;
            }
        }

        if (!isset($sub_cols['submission_id'])) {
            if (isset($sub_cols['id'])) {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `submission_id` INT NOT NULL DEFAULT 0 FIRST");
                @mysqli_query($conn, "UPDATE submissions SET `submission_id` = `id`");
            } else {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `submission_id` INT AUTO_INCREMENT PRIMARY KEY FIRST");
            }
        }

        if (!isset($sub_cols['task_id'])) {
            @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `task_id` INT NOT NULL DEFAULT 1");
        }

        if (!isset($sub_cols['user_id'])) {
            $user_src = null;
            if (isset($sub_cols['worker_id'])) $user_src = 'worker_id';
            elseif (isset($sub_cols['submitter_id'])) $user_src = 'submitter_id';
            elseif (isset($sub_cols['applicant_id'])) $user_src = 'applicant_id';

            if ($user_src) {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
                @mysqli_query($conn, "UPDATE submissions SET `user_id` = `$user_src`");
            } else {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `task_id`");
            }
        }

        if (!isset($sub_cols['submission_text'])) {
            $txt_src = null;
            if (isset($sub_cols['text'])) $txt_src = 'text';
            elseif (isset($sub_cols['content'])) $txt_src = 'content';
            elseif (isset($sub_cols['description'])) $txt_src = 'description';
            elseif (isset($sub_cols['message'])) $txt_src = 'message';

            if ($txt_src) {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `submission_text` TEXT NOT NULL");
                @mysqli_query($conn, "UPDATE submissions SET `submission_text` = `$txt_src`");
            } else {
                @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `submission_text` TEXT NOT NULL");
            }
        }

        if (!isset($sub_cols['status'])) {
            @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'SUBMITTED'");
        }

        if (!isset($sub_cols['created_at'])) {
            @mysqli_query($conn, "ALTER TABLE submissions ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    }

    // 5. Ensure transactions table and columns
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `transactions` (
        `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `amount_wp` INT NOT NULL,
        `price_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `payment_method` VARCHAR(150) NOT NULL DEFAULT 'Mock Card / Test Payment',
        `status` ENUM('COMPLETED', 'PENDING', 'FAILED') NOT NULL DEFAULT 'COMPLETED',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $txn_check = @mysqli_query($conn, "SHOW TABLES LIKE 'transactions'");
    if ($txn_check && mysqli_num_rows($txn_check) > 0) {
        $txn_cols = [];
        $txn_col_res = @mysqli_query($conn, "SHOW COLUMNS FROM transactions");
        if ($txn_col_res) {
            while ($tc = mysqli_fetch_assoc($txn_col_res)) {
                $txn_cols[$tc['Field']] = true;
            }
        }

        if (!isset($txn_cols['transaction_id'])) {
            if (isset($txn_cols['id'])) {
                @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `transaction_id` INT NOT NULL DEFAULT 0 FIRST");
                @mysqli_query($conn, "UPDATE transactions SET `transaction_id` = `id`");
            } else {
                @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `transaction_id` INT AUTO_INCREMENT PRIMARY KEY FIRST");
            }
        }

        if (!isset($txn_cols['user_id'])) {
            @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `user_id` INT NOT NULL DEFAULT 1");
        }

        if (!isset($txn_cols['amount_wp'])) {
            if (isset($txn_cols['amount'])) {
                @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `amount_wp` INT NOT NULL DEFAULT 0");
                @mysqli_query($conn, "UPDATE transactions SET `amount_wp` = `amount`");
            } elseif (isset($txn_cols['points'])) {
                @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `amount_wp` INT NOT NULL DEFAULT 0");
                @mysqli_query($conn, "UPDATE transactions SET `amount_wp` = `points`");
            } else {
                @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `amount_wp` INT NOT NULL DEFAULT 0");
            }
        }

        if (!isset($txn_cols['price_paid'])) {
            @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `price_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
        }

        if (!isset($txn_cols['payment_method'])) {
            @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `payment_method` VARCHAR(150) NOT NULL DEFAULT 'Mock Card / Test Payment'");
        }

        if (!isset($txn_cols['status'])) {
            @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'COMPLETED'");
        }

        if (!isset($txn_cols['created_at'])) {
            @mysqli_query($conn, "ALTER TABLE transactions ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        }
    }

    require_once __DIR__ . "/session.php";
    init_skillsprout_session($conn);
}

if (!defined('BASE_URL')) {
    $rawBase = getenv('BASE_URL') ?: getenv('APP_URL');
    $validBase = null;
    if ($rawBase !== false && $rawBase !== '') {
        $rawBase = trim($rawBase);
        $isDbScheme = preg_match('#^(mysql|mysqli|postgres|postgresql|sqlite|mongodb|redis)://#i', $rawBase);
        $hasAuth = strpos($rawBase, '@') !== false;
        $isHttpOrPath = preg_match('#^(https?://|/)#i', $rawBase);
        if (!$isDbScheme && !$hasAuth && $isHttpOrPath) {
            $validBase = rtrim($rawBase, '/') . '/';
        }
    }
    if ($validBase !== null) {
        define('BASE_URL', $validBase);
    } else {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (preg_match('#^/SkillSprout(/|$)#i', $uri) || preg_match('#^/SkillSprout(/|$)#i', $scriptName)) {
            define('BASE_URL', '/SkillSprout/');
        } else {
            define('BASE_URL', '/');
        }
    }
}
?>