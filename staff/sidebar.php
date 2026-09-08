<?php
if (!isset($conn)) {
    include_once __DIR__ . '/../includes/db_connection.php';
}

$staff_firstname = $_SESSION['staff_firstname'] ?? '';

$today = date('Y-m-d');
$todayBookingCount = 0;
$todayCheck = $conn->prepare("SELECT COUNT(*) as c FROM customer_booking WHERE status = 'Borrowed' AND DATE(borrow_date) = ?");
$todayCheck->bind_param("s", $today);
$todayCheck->execute();
$todayBookingCount = $todayCheck->get_result()->fetch_assoc()['c'];
$todayCheck->close();

$hasTodayBooking = $todayBookingCount > 0;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow d-lg-none">
   <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center" href="#">
         <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center me-2" 
            style="width:40px; height:40px; font-size:18px;">
            <i class="fa-solid fa-user"></i>
         </div>
         <span class="fs-5 fw-semibold">
         <?php echo htmlspecialchars($staff_firstname); ?>
         </span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNavbar" aria-controls="mobileNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mobileNavbar">
         <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fa-solid fa-home me-1"></i> Home</a></li>
            <li class="nav-item"><a class="nav-link" href="bookings.php"><i class="fa-solid fa-calendar-check me-1"></i> Bookings</a></li>
            <li class="nav-item">
               <a class="nav-link position-relative" href="calendar.php">
               <i class="fa-solid fa-calendar me-1"></i> Calendar
               <?php if ($hasTodayBooking): ?>
               <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
                  <?= $todayBookingCount ?>
               </span>
               <?php endif; ?>
               </a>
            </li>
            <li class="nav-item">
               <a class="nav-link text-danger" href="../logout.php">
               <i class="fa-solid fa-sign-out-alt me-1"></i>
               Logout
               </a>
            </li>
         </ul>
      </div>
   </div>
</nav>

<div class="d-flex min-vh-100">
<nav class="d-none d-lg-flex flex-column flex-shrink-0 p-3 bg-white shadow" style="width:220px;">
   <div class="d-flex align-items-center mb-4">
      <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center me-2" 
         style="width:40px; height:40px; font-size:18px;">
         <i class="fa-solid fa-user"></i>
      </div>
      <span class="fs-5 fw-semibold">
      <?php echo htmlspecialchars($staff_firstname); ?>
      </span>
   </div>
   <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item mb-2">
         <a href="dashboard.php" class="nav-link text-dark">
         <i class="fa-solid fa-home me-2"></i> Home
         </a>
      </li>
      <li class="nav-item mb-2">
         <a href="bookings.php" class="nav-link text-dark">
         <i class="fa-solid fa-calendar-check me-2"></i> Bookings
         </a>
      </li>
      <li class="nav-item mb-2">
         <a href="inventory.php" class="nav-link text-dark">
         <i class="fa-solid fa-box me-2"></i> inventory
         </a>
      </li>
      <li class="nav-item mb-2">
         <a href="calendar.php" class="nav-link text-dark position-relative">
         <i class="fa-solid fa-calendar me-2"></i> Calendar
         <?php if ($hasTodayBooking): ?>
         <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;">
            <?= $todayBookingCount ?>
         </span>
         <?php endif; ?>
         </a>
      <li class="nav-item mt-auto">
         <a href="../logout.php" class="nav-link text-danger">
         <i class="fa-solid fa-sign-out-alt me-2"></i>
         Logout
         </a>
      </li>
   </ul>
</nav>