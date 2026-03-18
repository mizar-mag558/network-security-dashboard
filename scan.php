<?php
require 'db.php';

function runScan($ip) {
    global $pdo;
    
    // Run nmap scan
    $output = shell_exec("timeout 60 nmap -T4 -sn " . escapeshellarg($ip) . " 2>&1");
    
    // Save scan to database
    $stmt = $pdo->prepare("INSERT INTO scans (ip_address, scan_date, raw_output) VALUES (?, NOW(), ?)");
    $stmt->execute([$ip, $output]);
    $scan_id = $pdo->lastInsertId();
    
    // Parse open ports from nmap output
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        if (preg_match('/^(\d+)\/(tcp|udp)\s+(\w+)\s+(.*)$/', $line, $matches)) {
            $stmt = $pdo->prepare("INSERT INTO ports (scan_id, port_number, protocol, service, state) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$scan_id, $matches[1], $matches[2], $matches[4], $matches[3]]);
        }
    }
    
    return $scan_id;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $scan_id = runScan($_POST['ip']);
    header("Location: index.php?scan_id=" . $scan_id);
    exit;
}
?>
