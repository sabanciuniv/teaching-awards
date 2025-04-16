<!-- Navbar -->
<nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
    <style>
       
        .navbar-brand img {
            height: 40px;
        }

        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: bold;
            color: white !important;
            margin-left: 10px;
        }

        /* Adjust spacing */
        .back-arrow {
            margin-right: 15px !important; 
            font-size: 1.2rem;
        }

        .navbar-brand {
            margin-left: 0 !important; 
        }

        /* Title styling */
        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }


        @media (max-width: 576px) {
            .navbar-title {
                position: static;
                transform: none;
                text-align: center;
                margin-top: 0.5rem; 
                font-size: 1.25rem; 
            }
          
            .navbar-brand img {
                height: 30px;
            }
            .back-arrow {
                font-size: 1rem; 
                margin-right: 8px !important; 
            }
        }


        @media (max-width: 992px) {
            .navbar-title {
                position: static;
                transform: none;
                text-align: center;
                margin-top: 0.5rem;
            }
        }
    </style>

    <div class="container-fluid d-flex align-items-center position-relative">
        <!-- Back Arrow and Logo -->
        <div class="d-flex align-items-center">
            <a href="<?php echo isset($backLink) ? htmlspecialchars($backLink) : 'index.php'; ?>" 
               class="text-white back-arrow" 
               style="text-decoration:none;">
                <i class="fas fa-arrow-left me-1"></i>
            </a>
            <a href="index.php" class="navbar-brand d-flex align-items-center">
                <img src="https://yabangee.com/wp-content/uploads/sabancı-university-2.jpg" alt="Logo">
            </a>
        </div>

        <!-- Centered Title (absolute on large screens, static on small) -->
        <div class="navbar-title">
            Teaching Awards
        </div>

        <!-- Toggler for Mobile -->
        <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" 
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle text-white" 
                    id="welcomeDropdown" role="button" data-bs-toggle="dropdown" 
                    aria-expanded="false">

                    <strong>
                        Welcome, 
                        <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user']); ?>
                    </strong>

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
                        <li>
                            <a class="dropdown-item" href="index.php">
                                <i class="fas fa-home me-2"></i> Home
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-question-circle me-2"></i> Help
                            </a>
                        </li>
                        <div class="dropdown-divider"></div>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php if (isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true): ?>
    <div style="width: 100%; display: flex; justify-content: center; margin-top: 8rem;">
        <div style="
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 20px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            font-weight: 500;
            max-width: 600px;
            text-align: center;
        ">
            You're impersonating: <strong><?php echo htmlspecialchars($_SESSION['impersonated_user']); ?></strong>
            <a href="revertImpersonation.php" class="btn btn-sm btn-danger" style="margin-left: 10px">Exit Impersonation</a>
        </div>
    </div>
<?php endif; ?>

