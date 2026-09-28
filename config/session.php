<?php

if (!class_exists('SkillSproutSessionHandler')) {
    class SkillSproutSessionHandler implements SessionHandlerInterface {
        private $conn;

        public function __construct($conn) {
            $this->conn = $conn;
        }

        public function open($path, $name): bool {
            return true;
        }

        public function close(): bool {
            return true;
        }

        public function read($id): string {
            $stmt = mysqli_prepare($this->conn, "SELECT data FROM sessions WHERE id = ? AND last_activity > ?");
            if (!$stmt) {
                return '';
            }
            $max_age = time() - (86400 * 14);
            mysqli_stmt_bind_param($stmt, "si", $id, $max_age);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                mysqli_stmt_close($stmt);
                return (string) $row['data'];
            }
            mysqli_stmt_close($stmt);
            return '';
        }

        public function write($id, $data): bool {
            $now = time();
            $stmt = mysqli_prepare($this->conn, "REPLACE INTO sessions (id, data, last_activity) VALUES (?, ?, ?)");
            if (!$stmt) {
                return false;
            }
            mysqli_stmt_bind_param($stmt, "ssi", $id, $data, $now);
            $res = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }

        public function destroy($id): bool {
            $stmt = mysqli_prepare($this->conn, "DELETE FROM sessions WHERE id = ?");
            if (!$stmt) {
                return false;
            }
            mysqli_stmt_bind_param($stmt, "s", $id);
            $res = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }

        public function gc($max_lifetime): int|false {
            $old = time() - $max_lifetime;
            $stmt = mysqli_prepare($this->conn, "DELETE FROM sessions WHERE last_activity < ?");
            if (!$stmt) {
                return false;
            }
            mysqli_stmt_bind_param($stmt, "i", $old);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected;
        }
    }
}

function init_skillsprout_session($conn) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (getenv('VERCEL') || getenv('SESSION_DRIVER') === 'database') {
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `sessions` (
            `id` VARCHAR(128) NOT NULL PRIMARY KEY,
            `data` MEDIUMTEXT NOT NULL,
            `last_activity` INT UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $handler = new SkillSproutSessionHandler($conn);
        session_set_save_handler($handler, true);
    }
}
