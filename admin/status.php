<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// Get status statistics
$stmt = $db->conn->query("
    SELECT 
        status,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM applications), 2) as percentage
    FROM applications 
    GROUP BY status
    ORDER BY FIELD(status, 'Pending', 'Reviewed', 'Interview', 'Selected', 'Rejected')
");
$status_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get applications by date (last 30 days)
$stmt = $db->conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'Selected' THEN 1 ELSE 0 END) as selected_count
    FROM applications 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total counts
$stmt = $db->conn->query("SELECT COUNT(*) as total FROM applications");
$total_applications = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->conn->query("SELECT COUNT(*) as selected FROM applications WHERE status = 'Selected'");
$total_selected = $stmt->fetch(PDO::FETCH_ASSOC)['selected'];

// Get average processing time
$stmt = $db->conn->query("
    SELECT 
        AVG(DATEDIFF(updated_at, created_at)) as avg_days
    FROM applications 
    WHERE status IN ('Selected', 'Rejected')
");
$avg_processing = $stmt->fetch(PDO::FETCH_ASSOC)['avg_days'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Overview - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-overview-header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .status-chart-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .chart-bar {
            height: 30px;
            background: #e9ecef;
            border-radius: 15px;
            margin-bottom: 1rem;
            overflow: hidden;
            position: relative;
        }
        
        .chart-fill {
            height: 100%;
            border-radius: 15px;
            transition: width 1s ease;
            position: relative;
        }
        
        .chart-fill.pending { background: #ffc107; }
        .chart-fill.reviewed { background: #17a2b8; }
        .chart-fill.interview { background: #007bff; }
        .chart-fill.selected { background: #28a745; }
        .chart-fill.rejected { background: #dc3545; }
        
        .chart-label {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #333;
            z-index: 2;
        }
        
        .chart-percentage {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 600;
            color: #333;
            z-index: 2;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .daily-chart {
            height: 300px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .day-bar {
            flex: 1;
            background: #e9ecef;
            border-radius: 5px 5px 0 0;
            position: relative;
            transition: height 0.3s ease;
        }
        
        .day-bar:hover {
            background: var(--primary-color);
        }
        
        .day-label {
            position: absolute;
            bottom: -25px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.75rem;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content fade-in">
            <!-- Header -->
            <div class="status-overview-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">Application Status Overview</h1>
                        <p class="mb-0">Track and analyze application status across all submissions</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="badge bg-light text-dark p-2">
                            <i class="bi bi-calendar me-1"></i> Last 30 Days
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="metric-card">
                        <div class="metric-value text-primary"><?php echo $total_applications; ?></div>
                        <div class="metric-label">Total Applications</div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> +12% from last month
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="metric-card">
                        <div class="metric-value text-success"><?php echo $total_selected; ?></div>
                        <div class="metric-label">Selected Candidates</div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> +5% from last month
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="metric-card">
                        <div class="metric-value text-warning"><?php echo round($avg_processing, 1); ?> days</div>
                        <div class="metric-label">Avg. Processing Time</div>
                        <div class="mt-2">
                            <small class="text-danger">
                                <i class="bi bi-arrow-down"></i> -2 days from last month
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="metric-card">
                        <div class="metric-value text-info">
                            <?php echo $total_applications > 0 ? round(($total_selected / $total_applications) * 100, 1) : 0; ?>%
                        </div>
                        <div class="metric-label">Selection Rate</div>
                        <div class="mt-2">
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> +1.5% from last month
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="status-chart-container">
                <h4 class="mb-4">Status Distribution</h4>
                
                <?php foreach ($status_stats as $stat): 
                    $status_class = strtolower($stat['status']);
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <div>
                            <span class="badge badge-<?php echo $status_class; ?> me-2"><?php echo $stat['status']; ?></span>
                            <span><?php echo $stat['count']; ?> applications</span>
                        </div>
                        <div><?php echo $stat['percentage']; ?>%</div>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-fill <?php echo $status_class; ?>" style="width: <?php echo $stat['percentage']; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Daily Activity Chart -->
            <div class="status-chart-container">
                <h4 class="mb-4">Daily Applications (Last 30 Days)</h4>
                
                <div class="daily-chart">
                    <?php 
                    $max_count = 0;
                    foreach ($daily_stats as $day) {
                        if ($day['count'] > $max_count) {
                            $max_count = $day['count'];
                        }
                    }
                    
                    foreach ($daily_stats as $day):
                        $height = $max_count > 0 ? ($day['count'] / $max_count) * 100 : 0;
                        $selected_height = $max_count > 0 ? ($day['selected_count'] / $max_count) * 100 : 0;
                        $date = new DateTime($day['date']);
                    ?>
                    <div class="day-bar-container" style="height: 100%; position: relative;">
                        <div class="day-bar" style="height: <?php echo $height; ?>%; background: #e9ecef;">
                            <div class="day-bar" style="height: <?php echo $selected_height; ?>%; background: #28a745; position: absolute; bottom: 0; left: 0; right: 0;"></div>
                        </div>
                        <div class="day-label">
                            <?php echo $date->format('d'); ?><br>
                            <?php echo $date->format('M'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div style="width: 20px; height: 20px; background: #e9ecef; margin-right: 10px;"></div>
                            <span>Total Applications</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div style="width: 20px; height: 20px; background: #28a745; margin-right: 10px;"></div>
                            <span>Selected Candidates</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Status Changes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Status Updates</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $db->conn->query("
                        SELECT a.id, a.first_name, a.last_name, a.status, a.updated_at, 
                               al.old_status, al.new_status, al.created_at as change_date
                        FROM applications a
                        LEFT JOIN application_logs al ON a.id = al.application_id
                        WHERE al.id IS NOT NULL
                        ORDER BY al.created_at DESC
                        LIMIT 10
                    ");
                    $recent_changes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($recent_changes)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p class="text-muted mt-2">No status changes recorded yet</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Applicant</th>
                                        <th>Previous Status</th>
                                        <th>New Status</th>
                                        <th>Changed On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_changes as $change): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">
                                                    <?php echo strtoupper(substr($change['first_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo $change['first_name'] . ' ' . $change['last_name']; ?></div>
                                                    <small class="text-muted">ID: #APP-<?php echo str_pad($change['id'], 6, '0', STR_PAD_LEFT); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($change['old_status']); ?>">
                                                <?php echo $change['old_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($change['new_status']); ?>">
                                                <?php echo $change['new_status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($change['change_date'])); ?></td>
                                        <td>
                                            <a href="applicant_detail.php?id=<?php echo $change['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Export Options -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6><i class="bi bi-download me-2"></i>Export Reports</h6>
                            <p class="text-muted mb-0">Download status reports for analysis</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary">PDF</button>
                                <button type="button" class="btn btn-outline-success">Excel</button>
                                <button type="button" class="btn btn-outline-info">CSV</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate chart bars on load
            const chartBars = document.querySelectorAll('.chart-fill');
            chartBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
            
            // Animate daily chart bars
            const dayBars = document.querySelectorAll('.day-bar-container .day-bar');
            dayBars.forEach(bar => {
                const height = bar.style.height;
                bar.style.height = '0';
                setTimeout(() => {
                    bar.style.height = height;
                }, 500);
            });
            
            // Tooltips for daily chart
            const dayBarContainers = document.querySelectorAll('.day-bar-container');
            dayBarContainers.forEach(container => {
                const bar = container.querySelector('.day-bar');
                const height = bar.style.height;
                const selectedBar = container.querySelector('.day-bar:nth-child(2)');
                const selectedHeight = selectedBar ? selectedBar.style.height : '0%';
                
                container.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip';
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #333;
                        color: white;
                        padding: 5px 10px;
                        border-radius: 5px;
                        font-size: 12px;
                        z-index: 1000;
                        white-space: nowrap;
                        top: -40px;
                        left: 50%;
                        transform: translateX(-50%);
                    `;
                    tooltip.innerHTML = `
                        Total: ${height}<br>
                        Selected: ${selectedHeight}
                    `;
                    this.appendChild(tooltip);
                });
                
                container.addEventListener('mouseleave', function() {
                    const tooltip = this.querySelector('.tooltip');
                    if (tooltip) {
                        tooltip.remove();
                    }
                });
            });
        });
    </script>
</body>
</html>