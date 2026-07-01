<?php
include "config/database.php";
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Available Events</h2>
        <p class="text-muted mb-0">Choose an event and continue to secure payment</p>
    </div>

    <div class="row">
        <?php
        $sql = "SELECT * FROM events ORDER BY event_date ASC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary">Featured</span>
                            <span class="text-muted small">$<?php echo htmlspecialchars($row['price']); ?></span>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="mb-1"><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo htmlspecialchars($row['event_date']); ?></p>
                        <a href="register.php?event_id=<?php echo (int) $row['id']; ?>" class="btn btn-primary mt-auto rounded-3">
                            Register
                        </a>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
            echo '<div class="col-12"><p class="text-muted">No events available.</p></div>';
        }
        ?>
    </div>
</div>

<?php include "includes/footer.php"; ?>