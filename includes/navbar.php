<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Event Booking</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="events.php">Browse Events</a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>

              <li class="nav-item">
                     <a class="nav-link position-relative" href="cart.php">
                      Cart
                 <?php if (count_cart_items() > 0): ?>
                 <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= count_cart_items() ?>
              </span>
             <?php endif; ?>
                    </a>
          </li>
<!--
<li class="nav-item">
    <a class="nav-link position-relative" href="cart.php">
        Cart
        <?php if (count_cart_items() > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?= count_cart_items() ?>
            </span>
        <?php endif; ?>
    </a>
</li>-->

<li class="nav-item">
    <a class="nav-link" href="dashboard.php">My Bookings</a>
</li>


                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>