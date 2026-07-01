<?php
include "config/database.php";
include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Available Events</h2>

    <div class="row">
        <?php
        $sql = "SELECT * FROM events ORDER BY event_date ASC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="mb-1"><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?php echo htmlspecialchars($row['event_date']); ?></p>
                        <p class="mb-3"><strong>Price:</strong> $<?php echo htmlspecialchars($row['price']); ?></p>
                        <a href="register.php?event_id=<?php echo (int) $row['id']; ?>" class="btn btn-primary mt-auto">
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