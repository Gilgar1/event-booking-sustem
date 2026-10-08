<?php
// Use absolute path to require files
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Verify admin status
if (!is_admin()) {
    $_SESSION['error_message'] = 'Admin access required';
    header('Location: ../login.php');
    exit();
}

// Get stats
$events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'")->fetchColumn();
$users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            min-height: 100vh;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="list-group list-group-flush">
                    <a href="index.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a href="events/" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-event me-2"></i>Events
                    </a>
                    <a href="bookings/" class="list-group-item list-group-item-action">
                        <i class="bi bi-ticket-perforated me-2"></i>Bookings
                    </a>
                    <a href="users/" class="list-group-item list-group-item-action">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                    <a href="reports/" class="list-group-item list-group-item-action">
                        <i class="bi bi-graph-up me-2"></i>Reports
                    </a>
                    <a href="../" class="list-group-item list-group-item-action">
                        <i class="bi bi-arrow-left-circle me-2"></i>View Site
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
                <hr>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Events</h5>
                                        <p class="card-text display-6"><?= $events ?></p>
                                    </div>
                                    <i class="bi bi-calendar-event display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Bookings</h5>
                                        <p class="card-text display-6"><?= $bookings ?></p>
                                    </div>
                                    <i class="bi bi-ticket-perforated display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Users</h5>
                                        <p class="card-text display-6"><?= $users ?></p>
                                    </div>
                                    <i class="bi bi-people display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card text-white bg-dark">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Revenue</h5>
                                        <p class="card-text display-6">$<?= number_format($revenue, 2) ?></p>
                                    </div>
                                    <i class="bi bi-currency-dollar display-4 opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="bi bi-clock-history me-2"></i> Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Event</th>
                                        <th>User</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT b.id, e.name as event, u.email, b.booking_date, 
                                               b.total_price, b.status
                                        FROM bookings b
                                        JOIN events e ON b.event_id = e.id
                                        JOIN users u ON b.user_id = u.id
                                        ORDER BY b.booking_date DESC
                                        LIMIT 8
                                    ");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><?= $row['event'] ?></td>
                                        <td><?= $row['email'] ?></td>
                                        <td><?= date('M j, Y h:i A', strtotime($row['booking_date'])) ?></td>
                                        <td>$<?= number_format($row['total_price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $row['status'] == 'confirmed' ? 'success' : 
                                                ($row['status'] == 'pending' ? 'warning' : 'danger') 
                                            ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>