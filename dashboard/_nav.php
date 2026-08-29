<nav class="topbar">
  <a href="/dashboard/index.php">📅 BookAppointment.me</a>
  <div class="links">
    <a href="/dashboard/index.php">Bookings</a>
    <a href="/dashboard/services.php">Services</a>
    <a href="/dashboard/availability.php">Availability</a>
    <a href="/dashboard/profile.php">Profile</a>
    <a href="<?= htmlspecialchars(booking_path($provider)) ?>" target="_blank">View my page ↗</a>
    <a href="/dashboard/logout.php">Log out</a>
  </div>
</nav>
