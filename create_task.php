<?php
require_once __DIR__ . "/config/database.php";
header("Location: " . BASE_URL . "tasks/create_task.php", true, 301);
exit();