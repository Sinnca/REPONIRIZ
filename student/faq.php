<?php
/**
 * FAQ Page
 * Frequently Asked Questions for Lost & Found System
 */

require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require student login
requireStudent();

$userId = getCurrentUserId();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --university-blue: #003366;
            --university-gold: #FFB81C;
            --navy: #002D72;
            --burgundy: #8B1538;
            --forest-green: #1B5E20;
            --slate: #455A64;
            --light-blue: #E3F2FD;
            --light-gold: #FFF8E1;
            --text-dark: #1a1a2e;
            --text-light: #6B7280;
            --white: #FFFFFF;
            --border-color: #E5E7EB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #F5F5F5;
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            font-size: 15px;
            line-height: 1.6;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                    linear-gradient(135deg, rgba(0, 102, 255, 0.02) 0%, transparent 50%),
                    repeating-linear-gradient(
                            0deg,
                            transparent,
                            transparent 2px,
                            rgba(0, 102, 255, 0.01) 2px,
                            rgba(0, 102, 255, 0.01) 4px
                    );
            pointer-events: none;
            z-index: 0;
        }

        main, nav, footer {
            position: relative;
            z-index: 1;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        /* Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 1rem 0;
            border-bottom: 4px solid var(--university-gold);
        }

        .navbar-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--white) !important;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            text-transform: uppercase;
        }

        .navbar-brand i {
            color: var(--university-gold);
            margin-right: 0.6rem;
            font-size: 2rem;
        }

        .navbar-nav .nav-link {
            color: var(--white) !important;
            font-weight: 600;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            padding: 0.6rem 1rem !important;
            border-radius: 4px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255, 184, 28, 0.2);
            color: var(--university-gold) !important;
        }

        .navbar-nav .nav-link.active {
            background: var(--university-gold);
            color: var(--university-blue) !important;
            font-weight: 700;
        }

        .navbar-text {
            color: var(--white) !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .navbar-text strong {
            color: var(--university-gold);
            font-weight: 700;
        }

        .btn-outline-primary {
            border: 2px solid var(--university-gold);
            color: var(--university-gold);
            background: transparent;
            font-weight: 700;
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .btn-outline-primary:hover {
            background: var(--university-gold);
            color: var(--university-blue);
            border-color: var(--university-gold);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            padding: 3rem 0;
            margin-bottom: 3rem;
            border-bottom: 4px solid var(--university-gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }

        .page-header i {
            color: var(--university-gold);
        }

        /* FAQ Categories */
        .faq-categories {
            margin-bottom: 2rem;
        }

        .category-btn {
            background: var(--white);
            border: 2px solid var(--university-blue);
            color: var(--university-blue);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }

        .category-btn:hover,
        .category-btn.active {
            background: var(--university-blue);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3);
        }

        /* Accordion Styles */
        .accordion {
            margin-bottom: 2rem;
        }

        .accordion-item {
            background: var(--white);
            border: 2px solid var(--border-color);
            border-radius: 0;
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .accordion-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .accordion-button {
            background: var(--white);
            color: var(--university-blue);
            font-weight: 700;
            font-size: 1rem;
            padding: 1.5rem;
            border: none;
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        .accordion-button:not(.collapsed) {
            background: var(--university-blue);
            color: var(--white);
            box-shadow: none;
        }

        .accordion-button:focus {
            box-shadow: none;
            border: none;
        }

        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23003366'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }

        .accordion-button:not(.collapsed)::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23FFFFFF'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }

        .accordion-body {
            padding: 1.5rem;
            background: var(--white);
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.7;
            border-top: 2px solid var(--border-color);
        }

        .accordion-body strong {
            color: var(--university-blue);
        }

        .accordion-body ul {
            padding-left: 1.5rem;
            margin-top: 0.5rem;
        }

        .accordion-body li {
            margin-bottom: 0.5rem;
        }

        /* Category Section */
        .category-section {
            margin-bottom: 3rem;
        }

        .category-title {
            background: var(--university-blue);
            color: var(--white);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            border-left: 6px solid var(--university-gold);
        }

        .category-title i {
            color: var(--university-gold);
            margin-right: 0.8rem;
        }

        /* Contact Card */
        .contact-card {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            padding: 2.5rem;
            border-radius: 0;
            border-left: 6px solid var(--university-gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 3rem;
        }

        .contact-card h3 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: var(--white);
        }

        .contact-card h3 i {
            color: var(--university-gold);
        }

        .contact-card p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .contact-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .contact-info i {
            color: var(--university-gold);
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--university-blue) 0%, var(--navy) 100%);
            color: var(--white);
            font-weight: 500;
            padding: 2rem 0;
            margin-top: 3rem;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.2);
            border-top: 4px solid var(--university-gold);
        }

        footer i {
            color: var(--university-gold);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .category-btn {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }

            .accordion-button {
                font-size: 0.9rem;
                padding: 1.2rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .accordion-item {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-box-seam"></i>Lost & Found</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="report_lost.php"><i class="bi bi-exclamation-circle me-1"></i>Report Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="report_found.php"><i class="bi bi-check-circle me-1"></i>Report Found</a></li>
                <li class="nav-item"><a class="nav-link" href="lost_items.php"><i class="bi bi-search me-1"></i>Browse Lost</a></li>
                <li class="nav-item"><a class="nav-link" href="found_items.php"><i class="bi bi-archive me-1"></i>Browse Found</a></li>
                <li class="nav-item"><a class="nav-link" href="my_items.php"><i class="bi bi-person-lines-fill me-1"></i>My Items</a></li>
                <li class="nav-item"><a class="nav-link active" href="faq.php"><i class="bi bi-question-circle me-1"></i>FAQ</a></li>
            </ul>
            <span class="navbar-text me-3"><i class="bi bi-person-circle me-2"></i>Welcome, <strong><?= htmlspecialchars($userName) ?></strong></span>
            <a class="btn btn-outline-primary btn-sm" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-question-circle-fill me-3"></i>Frequently Asked Questions</h1>
        <p>Find answers to common questions about our Lost & Found system</p>
    </div>
</div>

<main class="container" style="max-width: 1200px; padding-bottom: 3rem;">

    <!-- Getting Started -->
    <div class="category-section">
        <h2 class="category-title"><i class="bi bi-play-circle-fill"></i>Getting Started</h2>
        <div class="accordion" id="gettingStarted">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#gs1">
                        How do I report a lost item?
                    </button>
                </h2>
                <div id="gs1" class="accordion-collapse collapse show" data-bs-parent="#gettingStarted">
                    <div class="accordion-body">
                        To report a lost item, follow these steps:
                        <ul>
                            <li>Click on <strong>"Report Lost"</strong> in the navigation menu</li>
                            <li>Fill in all required details about your lost item including name, description and  etc.</li>
                            <li>Upload clear photos of the item if you have them (this helps with identification)</li>
                            <li>Submit the form and you'll receive a confirmation</li>
                            <li>You can track your report status from your dashboard</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#gs2">
                        How do I report a found item?
                    </button>
                </h2>
                <div id="gs2" class="accordion-collapse collapse" data-bs-parent="#gettingStarted">
                    <div class="accordion-body">
                        If you've found someone's item:
                        <ul>
                            <li>Click on <strong>"Report Found"</strong> in the navigation menu</li>
                            <li>Provide detailed information about the item you found</li>
                            <li>Take clear photos showing identifying features</li>
                            <li>Submit the report and wait for potential matches or claims</li>
                        </ul>
                        You will submit the found item to the office for verifications.
                    </div>
                </div>
            </div>


        </div>
    </div>

    <!-- Claiming Items -->
    <div class="category-section">
        <h2 class="category-title"><i class="bi bi-clipboard-check-fill"></i>Claiming Items</h2>
        <div class="accordion" id="claimingItems">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ci1">
                        How do I claim an item I found on the system?
                    </button>
                </h2>
                <div id="ci1" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        To claim an item:
                        <ul>
                            <li>Browse the <strong>"Found Items"</strong> section and find your item</li>
                            <li>Click on the item to view full details</li>
                            <li>Click the <strong>"Claim This Item"</strong> button</li>
                            <li>Provide proof of ownership (photos, receipts, detailed description of unique features)</li>
                            <li>Submit your claim request and wait for admin approval</li>
                            <li>Once approved, you'll be notified about the pickup schedule</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ci2">
                        What proof of ownership do I need to provide?
                    </button>
                </h2>
                <div id="ci2" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        Acceptable proof of ownership includes:
                        <ul>
                            <li><strong>Photos:</strong> Pictures of you with the item before it was lost</li>
                            <li><strong>Receipts:</strong> Purchase receipts showing serial numbers or unique identifiers</li>
                            <li><strong>Detailed descriptions:</strong> Specific details not visible in the found item photos (scratches, marks, custom modifications)</li>
                            <li><strong>Unique identifiers:</strong> Engravings, customizations, or special features</li>
                        </ul>
                        The more proof you can provide, the faster your claim will be processed.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ci3">
                        How long does the claim approval process take?
                    </button>
                </h2>
                <div id="ci3" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        The claim approval process typically takes <strong>1-3 business days</strong>. The timeline depends on:
                        <ul>
                            <li>Quality and completeness of proof provided</li>
                            <li>Admin verification process</li>
                            <li>Whether additional verification is needed</li>
                        </ul>
                        You'll receive notifications via the system about your claim status. You can also check the status on your dashboard under "My Claim Requests."
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ci4">
                        Where and when can I pick up my claimed item?
                    </button>
                </h2>
                <div id="ci4" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        Once your claim is approved:
                        <ul>
                            <li>You'll receive a notification with the <strong>pickup schedule</strong></li>
                            <li>Pickup location is typically at the <strong>Campus Lost & Found Office</strong></li>
                            <li>Bring your <strong>student ID</strong> for verification</li>
                        </ul>
                        Office hours and location details will be provided in your approval notification.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account & System -->
    <div class="category-section">
        <h2 class="category-title"><i class="bi bi-gear-fill"></i>Account & System</h2>
        <div class="accordion" id="accountSystem">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#as1">
                        How do I update my reported items?
                    </button>
                </h2>
                <div id="as1" class="accordion-collapse collapse" data-bs-parent="#accountSystem">
                    <div class="accordion-body">
                        To update your reported items:
                        <ul>
                            <li>Go to <strong>"My Items"</strong> from the navigation menu</li>
                            <li>Find the item you want to update</li>
                            <li>Click the <strong>"Edit"</strong> button (only available for pending items)</li>
                            <li>Update the necessary information</li>
                            <li>Save your changes</li>
                        </ul>
                        Note: You can only edit items that are still in "pending" status. Approved or claimed items cannot be edited.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#as2">
                        What do the different item statuses mean?
                    </button>
                </h2>
                <div id="as2" class="accordion-collapse collapse" data-bs-parent="#accountSystem">
                    <div class="accordion-body">
                        Item statuses explained:
                        <ul>
                            <li><strong>Pending:</strong> Your report is awaiting admin review</li>
                            <li><strong>Approved:</strong> Your report has been verified and is now visible to others</li>
                            <li><strong>Completed:</strong> The item has been successfully claimed and the process is completed</li>
                            <li><strong>Returned:</strong> The item has been returned to its owner</li>
                            <li><strong>Rejected:</strong> The report did not meet requirements (you'll receive feedback)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#as3">
                        How do notifications work?
                    </button>
                </h2>
                <div id="as3" class="accordion-collapse collapse" data-bs-parent="#accountSystem">
                    <div class="accordion-body">
                        You'll receive notifications for:
                        <ul>
                            <li>Status updates on your reported items</li>
                            <li>Claim request approvals or rejections</li>
                            <li>Potential matches for your lost items</li>
                            <li>Important system announcements</li>
                        </ul>
                        Check your dashboard regularly for the latest notifications. They appear in the "Recent Notifications" section on your main dashboard.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#as4">
                        What if I find my item outside of the system?
                    </button>
                </h2>
                <div id="as4" class="accordion-collapse collapse" data-bs-parent="#accountSystem">
                    <div class="accordion-body">
                        If you find your item through other means:
                        <ul>
                            <li>Go to office and tell them that you already found the item</li>
                        </ul>
                        This helps keep the system accurate and prevents others from wasting time on resolved cases.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Policies & Guidelines -->
    <div class="category-section">
        <h2 class="category-title"><i class="bi bi-shield-fill-check"></i>Policies & Guidelines</h2>
        <div class="accordion" id="policies">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p1">
                        What types of items can be reported?
                    </button>
                </h2>
                <div id="p1" class="accordion-collapse collapse" data-bs-parent="#policies">
                    <div class="accordion-body">
                        You can report most personal items including:
                        <ul>
                            <li>Electronics (phones, tablets, laptops, chargers)</li>
                            <li>Personal accessories (bags, wallets, keys, jewelry)</li>
                            <li>Clothing and wearables</li>
                            <li>Books and educational materials</li>
                            <li>Student IDs and documents</li>
                        </ul>
                        <strong>Items NOT accepted:</strong> Perishable goods, cash (report to security directly), weapons, illegal items, or hazardous materials.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p2">
                        How long are found items kept?
                    </button>
                </h2>
                <div id="p2" class="accordion-collapse collapse" data-bs-parent="#policies">
                    <div class="accordion-body">
                        Found items are typically kept for <strong>90 days</strong>. After this period:
                        <ul>
                            <li>Unclaimed items may be donated, recycled, or disposed of according to university policy</li>
                            <li>Important items like electronics or documents may be kept longer if necessary</li>
                            <li>You will be notified via the system if your item is about to be removed</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#p3">
                        Are there any restrictions on reporting or claiming items?
                    </button>
                </h2>
                <div id="p3" class="accordion-collapse collapse" data-bs-parent="#policies">
                    <div class="accordion-body">
                        Yes, to maintain fairness and security:
                        <ul>
                            <li>Each student can report or claim items only for themselves</li>
                            <li>False reporting or claiming someone else's items may lead to account suspension</li>
                            <li>Always provide accurate information and proof when claiming items</li>
                            <li>To prevent multiple requests, each student can submit a report or claim only <strong>twice per day</strong>.</li>
                            <li>Admin reserves the right to reject invalid or incomplete claims</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ci5">
                        What happens if multiple students claim the same item?
                    </button>
                </h2>
                <div id="ci5" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        If multiple students submit claims for the same item:
                        <ul>
                            <li>All claimants will be scheduled for pickup at the <strong>same time</strong>.</li>
                            <li>This ensures fairness and prevents overlapping or multiple pickups.</li>
                            <li>Admin will verify each claimant's proof of ownership before releasing the item.</li>
                        </ul>
                        This process helps avoid disputes and ensures that the rightful owner can claim the item safely.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ciPostPickup">
                        Is there anything I need to do after receiving my claimed item?
                    </button>
                </h2>
                <div id="ciPostPickup" class="accordion-collapse collapse" data-bs-parent="#claimingItems">
                    <div class="accordion-body">
                        Yes. For safety and accountability:
                        <ul>
                            <li>After receiving your claimed item, you will be asked to fill out a <strong>post-claim confirmation form</strong>.</li>
                            <li>This form confirms you have received the item and acknowledges that it belongs to you.</li>
                            <li>It serves as a record in case of any future disputes or verification needs.</li>
                        </ul>
                        This step ensures the system maintains accurate records and prevents potential issues.
                    </div>
                </div>
            </div>

        </div>
    </div>

</main>

<!-- Footer -->
<footer class="text-center">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Lost & Found System. All rights reserved.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
