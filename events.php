<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Pagination settings
$eventsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $eventsPerPage;

// Search and filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 1000;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Base SQL query
$sql = "SELECT * FROM events WHERE 1=1";
$params = [];

// Apply search filters
if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE ? OR venue LIKE ? OR organizer LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($minPrice > 0) {
    $sql .= " AND price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice < 1000) {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
}

if (!empty($startDate)) {
    $sql .= " AND date >= ?";
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $sql .= " AND date <= ?";
    $params[] = $endDate;
}

// Get total count for pagination
$countStmt = $pdo->prepare(str_replace('*', 'COUNT(*)', $sql));
$countStmt->execute($params);
$totalEvents = $countStmt->fetchColumn();

// Add sorting and pagination
$sql .= " ORDER BY date ASC LIMIT ? OFFSET ?";
$params[] = $eventsPerPage;
$params[] = $offset;

// Fetch filtered events
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$totalPages = ceil($totalEvents / $eventsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Booking - Browse Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Upcoming Events</h1>
        
        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="events.php" method="GET">
                    <div class="row g-3">
                        <!-- Search Bar -->
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search events..." 
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                        
                        <!-- Price Range -->
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="min_price" placeholder="Min" 
                                       min="0" step="5" value="<?= $minPrice ?>">
                                <span class="input-group-text">to</span>
                                <input type="number" class="form-control" name="max_price" placeholder="Max" 
                                       min="0" step="5" value="<?= $maxPrice ?>">
                            </div>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="date" class="form-control" name="start_date" 
                                       value="<?= htmlspecialchars($startDate) ?>">
                                <span class="input-group-text">to</span>
                                <input type="date" class="form-control" name="end_date" 
                                       value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                        </div>
                        
                        <!-- Submit/Reset -->
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Events Grid -->
        <div class="row">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <div class="alert alert-info">No events found matching your criteria.</div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="assets/images/<?= htmlspecialchars($event['image']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($event['name']) ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($event['name']) ?></h5>
                                <p class="card-text">
                                    <strong>Date:</strong> <?= date('M j, Y', strtotime($event['date'])) ?><br>
                                    <strong>Price:</strong> <span class="text-success">$<?= number_format($event['price'], 2) ?></span>
                                </p>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="event_details.php?id=<?= $event['id'] ?>" class="btn btn-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>">
                                Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" 
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" 
                               href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>">
                                Next
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>