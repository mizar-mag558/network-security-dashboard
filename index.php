<?php
require 'db.php';

// Get all scans
$scans = $pdo->query("SELECT * FROM scans ORDER BY scan_date DESC")->fetchAll();

// Get scan details if specific scan selected
$selected_scan = null;
$ports = [];
if (isset($_GET['scan_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM scans WHERE id = ?");
    $stmt->execute([$_GET['scan_id']]);
    $selected_scan = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM ports WHERE scan_id = ?");
    $stmt->execute([$_GET['scan_id']]);
    $ports = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Security Dashboard</title>
    <link rel="stylesheet" href="style.css">
        
</head>
<body>

<div class="header">
    <h1> NETWORK SECURITY DASHBOARD</h1>
    <p>Home Network Monitor — Kali Linux</p>
</div>

<div class="container">
    <div class="sidebar">
        <h2>▶ RUN SCAN</h2>
        <form class="scan-form" action="scan.php" method="POST" onsubmit="showSpinner()">
            <input type="text" name="ip" placeholder="Enter IP (e.g. 192.168.x.x)" required>
            <button type="submit" id="scan-btn"> SCAN NOW</button>
        </form>

        <div id="spinner" style="display:none; text-align:center; margin-top:15px;">
            <div class="spinner"></div>
            <p style="color:#00ff00; font-size:12px; margin-top:10px;">SCANNING... PLEASE WAIT</p>
        </div>

        <h2>▶ SCAN HISTORY</h2>
        <?php if (empty($scans)): ?>
            <p style="color:#444; font-size:12px;">No scans yet. Run your first scan!</p>
        <?php else: ?>
            <?php foreach ($scans as $scan): ?>
                <a href="index.php?scan_id=<?= $scan['id'] ?>" style="text-decoration:none;">
                    <div class="scan-item <?= (isset($_GET['scan_id']) && $_GET['scan_id'] == $scan['id']) ? 'active' : '' ?>">
                        <div style="color:#00ff00;"><?= htmlspecialchars($scan['ip_address']) ?></div>
                        <div style="color:#555; font-size:11px;"><?= $scan['scan_date'] ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="main">
        <?php if ($selected_scan): ?>
            <h2>▶ SCAN RESULTS — <?= htmlspecialchars($selected_scan['ip_address']) ?></h2>
            
            <?php if (empty($ports)): ?>
                <p style="color:#444;">No open ports found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>PORT</th>
                            <th>PROTOCOL</th>
                            <th>SERVICE</th>
                            <th>STATE</th>
                            <th>RISK</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $risky_ports = [21, 23, 80, 8080, 3389, 445, 139];
                        foreach ($ports as $port): 
                            $is_risky = in_array($port['port_number'], $risky_ports);
                        ?>
                        <tr>
                            <td><?= $port['port_number'] ?></td>
                            <td><?= strtoupper($port['protocol']) ?></td>
                            <td><?= htmlspecialchars($port['service']) ?></td>
                            <td><span class="badge badge-open"><?= $port['state'] ?></span></td>
                            <td>
                                <?php if ($is_risky): ?>
                                    <span class="badge badge-risk">⚠ REVIEW</span>
                                <?php else: ?>
                                    <span style="color:#444;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="raw-output"><?= htmlspecialchars($selected_scan['raw_output']) ?></div>

        <?php else: ?>
            <div class="no-scan">
                <p>◈ Select a scan from the sidebar or run a new scan</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
function showSpinner() {
    document.getElementById('spinner').style.display = 'block';
    document.getElementById('scan-btn').disabled = true;
    document.getElementById('scan-btn').innerText = 'SCANNING...';
}
</script>

</body>
</html>