<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Manager', 'Admin'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "leave_management");
if ($conn->connect_error) { die("Database link failure."); }

$total_requests = $conn->query("SELECT COUNT(*) as total FROM leave_requests")->fetch_assoc()['total'];
$approved_count = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='Approved'")->fetch_assoc()['total'];
$pending_count  = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='Pending'")->fetch_assoc()['total'];
$rejected_count = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='Rejected'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DayAway | Statistics Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="leave_management.css">
    <style>
        .analytics-strip { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; }
        .metric-block { background: var(--bg-surface); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; text-align: center; }
        .metric-value { font-size: 2.5rem; font-weight: 700; color: #fff; margin-top: 0.5rem; }
    </style>
</head>
<body class="<?php echo (in_array($_SESSION['user_role'], ['Manager', 'Admin'])) ? 'manager-layout' : 'employee-layout'; ?>">
<?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
    <aside id="sidebar">
        <div class="brand"><i class="fa-solid fa-layer-group"></i> <span>DayAway</span>
		</div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            
            <?php if (in_array($_SESSION['user_role'], ['Manager', 'Admin'])): ?>
                <li><a href="requests.php"><i class="fa-solid fa-calendar-check"></i> Leave Requests</a></li>
                <li><a href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li class="active"><a href="analytics.php"><i class="fa-solid fa-chart-pie"></i> Analytics</a></li>
            <?php endif; ?>
            
            <li style="margin-top: auto;"><a href="logout.php" style="color: #ef4444;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </aside>
	
	<?php else: ?>
    <nav class="top-navbar">
        <div class="nav-container">
            <div class="brand">
                <i class="fa-solid fa-layer-group"></i>
                <span>DayAway</span>
            </div>
            <div class="top-nav-links">
                <a href="index.php"><i class="fa-solid fa-house"></i> Dashboard</a>
                <a href="logout.php" class="logout-link"><i class="fa-solid fa-power-off"></i> Exit</a>
			</div>
		</div>
    </nav>
	
	
	
<?php endif; ?>


    <main>
        <header>
            <div>
                <h1> Analytics </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Real-time processing distribution summary logs</p>
            </div>
        </header>

        <div class="analytics-strip">
            <div class="metric-block" style="border-top: 3px solid var(--accent-primary);"><div style="color: var(--text-muted);">Total Transactions</div><div class="metric-value"><?php echo $total_requests; ?></div></div>
            <div class="metric-block" style="border-top: 3px solid var(--status-pending);"><div style="color: var(--text-muted);">Awaiting Actions</div><div class="metric-value"><?php echo $pending_count; ?></div></div>
            <div class="metric-block" style="border-top: 3px solid var(--status-approved);"><div style="color: var(--text-muted);">Approved Clearance</div><div class="metric-value"><?php echo $approved_count; ?></div></div>
            <div class="metric-block" style="border-top: 3px solid var(--status-rejected);"><div style="color: var(--text-muted);">Rejected Actions</div><div class="metric-value"><?php echo $rejected_count; ?></div></div>
        </div>
		
		<div class="analytics-grid">
            
            <section class="neon-card">
                <div class="card-title"><i class="fa-solid fa-chart-pie" style="color: var(--accent-primary);"></i> Leave Status Tracking </div>
                <div class="chart-viewport">
                    <canvas id="leaveStatusChart" 
                            data-approved="<?php echo (int)$approved_count; ?>" 
                            data-pending="<?php echo (int)$pending_count; ?>" 
                            data-rejected="<?php echo (int)$rejected_count; ?>">
                    </canvas>
                </div>
            </section>

			<section class="neon-card">
				<div class="card-title"><i class="fa-solid fa-chart-bar" style="color: var(--accent-primary);"></i> Category Load Breakdown</div>
				<div class="table-container">
					<table>
						<thead><tr><th>Category Type</th><th>Recorded Actions Vol</th></tr></thead>
						<tbody>
							<?php
							$dist_res = $conn->query("SELECT leave_type, COUNT(*) as count FROM leave_requests GROUP BY leave_type");
							if ($dist_res && $dist_res->num_rows > 0) {
								while($r = $dist_res->fetch_assoc()) {
									echo "<tr><td>" . htmlspecialchars($r['leave_type']) . "</td><td><strong>" . $r['count'] . " Instances</strong></td></tr>";
								}
							} else {
								echo "<tr><td colspan='2' style='text-align:center; color: var(--text-muted); padding: 2rem;'>No tracking operations recorded yet.</td></tr>";
							}
							?>
						</tbody>
					</table>
				</div>
			</section>
		</div>
    </main>
	
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="analytics.js"></script>
	
</body>
</html>
<?php $conn->close(); ?>