<?php
// Reset job to pending
require_once __DIR__ . '/../config/database.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) die('No ID');

$db = getDB();
$db->exec("UPDATE transcription_jobs SET status='pending', started_at=NULL WHERE id=$id");
echo "Job $id reset to pending";
