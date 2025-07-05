<?php
session_start();
// Include your database connection file
// Make sure db_connect.php is in the same directory or provide the correct path
require_once '../db_connect.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'admin') {
    header("location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: #000 !important;
            /* Add padding to the top of the body to account for the fixed header */
            padding-top: 5rem; /* Adjusted padding for header with tabs */
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #000 !important;
            margin-bottom: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            overflow: hidden; /* Ensures rounded corners apply to table content */
        }
        /* Adjusted table cell styling for better fitting */
        th, td {
            padding: 0.5rem 0.75rem; /* Reduced padding */
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.875rem; /* Smaller font size for cells */
            /* word-break: break-word; /* Allow long words to break and wrap */
            white-space: nowrap; /* Keep text in one line */
        }
        th {
            background-color: #4c51bf;
            color: #000 !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem; /* Smaller font for headers */
            white-space: nowrap; /* Prevent text from wrapping */
        }
        tr:nth-child(even) {
            background-color: #f7fafc;
        }
        tr:hover {
            background-color: #ebf4ff;
        }
        .section-title {
            border-bottom: 2px solid #4c51bf;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background-color: #f0f4f8;
            padding: 1.5rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .stat-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #000 !important;
            margin-bottom: 0.5rem;
        }
        .stat-card p {
            font-size: 2.25rem;
            font-weight: 700;
            color: #000 !important;
        }
        .chart-container {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 400px; /* Added to fix stretching */
        }
        /* Glass effect for header */
        .glass-header {
            background-color: #000 !important; /* Pure black */
            border-bottom: 1px solid rgba(76, 81, 191, 0.15); /* Subtle border */
            /* No blur or backdrop-filter */
        }
        /* Tab styling */
        .tab-button {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #000000; /* Changed to black */
        }
        .tab-button:hover {
            /* No hover effect for tab buttons */
        }
        .tab-button.active {
            position: relative;
            background: #fff;
            color: #000 !important;
            border-radius: 0 0 0 0; /* Remove rounded corners */
            z-index: 2;
            transition: background 0.3s, border-radius 0.3s;
            /* Increase vertical padding for selected button */
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -20px;
            height: 20px;
            background: #fff;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            z-index: 1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: border-radius 0.3s;
        }

        .tab-nav-item.tab-button {
            border-bottom: 3px solid transparent;
            border-radius: 0.5rem 0.5rem 0.5rem 0.5rem;
            transition: border-radius 0.3s, background 0.3s, border-bottom 0.3s;
        }
        .tab-nav-item.tab-button.active {
            border-bottom: 3px solid #4c51bf;
            border-radius: 0 0 0 0;
        }
        /* Styling for the back button */
        .back-button {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #000000;
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none; /* Remove underline from link */
            display: inline-flex; /* Use flex to align icon and text if any */
            align-items: center; /* Vertically align icon */
            gap: 0.5rem; /* Space between icon and text */
        }
        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        .back-button svg {
            width: 1.25rem; /* Adjust icon size */
            height: 1.25rem;
            fill: currentColor; /* Make SVG color inherit from parent */
        }
        /* Uniform nav/tab styling */
        .tab-nav-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.125rem 1rem; /* py-0.5 px-4 */
            font-size: 1.125rem; /* text-lg */
            font-weight: bold;
            color: #000 !important;
            background: rgba(255,255,255,0.5); /* bg-white bg-opacity-50 */
            border: none;
            border-radius: 9999px; /* rounded-full */
            box-shadow: none;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, border 0.2s;
            text-decoration: none;
            cursor: pointer;
            outline: none;
            letter-spacing: 0.05em; /* tracking-wide */
        }
        .tab-nav-item svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: currentColor;
        }
        .tab-nav-item:hover, .tab-nav-item:focus {
            /* No hover/focus effect for tab buttons */
        }
        .tab-nav-item.active {
            /* No background or text color change for active tab */
            /* background: #000 !important; */
            /* color: #fff !important; */
            /* border: none; */
            /* box-shadow: 0 4px 16px rgba(0,0,0,0.13); */
        }
        .tab-nav-item.home-nav {
            background: rgba(255,255,255,0.5);
            color: #000 !important;
            border: none;
        }
        .tab-nav-item.home-nav:hover, .tab-nav-item.home-nav:focus {
            /* No hover/focus effect for home tab */
        }
        .tab-nav-item.home-nav.active {
            background: #000 !important;
            color: #fff !important;
            border: none;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
        }
    </style>
</head>
<body class="p-4">
    <header class="fixed top-0 left-0 w-full text-white p-3 shadow-lg z-50 glass-header">
        <div class="flex justify-between items-center max-w-7xl mx-auto">
            <h1 class="text-base font-semibold text-left" style="color:#fff !important;">Control Center</h1>
            <div class="flex space-x-4">
                <a href="../admin/dashboard.php" class="tab-nav-item home-nav flex items-center gap-2" style="font-size:0.95rem; padding:0.1rem 0.7rem; gap:0.35rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="inline-block">
                        <path d="M10.8284 12.0007L15.7782 16.9504L14.364 18.3646L8 12.0007L14.364 5.63672L15.7782 7.05093L10.8284 12.0007Z"></path>
                    </svg>
                    <span style="font-size:0.95rem;">Home</span>
                </a>
                <button id="statsTab" class="tab-nav-item tab-button active" style="font-size:0.95rem; padding:0.1rem 0.7rem; gap:0.35rem;">Stats</button>
                <button id="tablesTab" class="tab-nav-item tab-button" style="font-size:0.95rem; padding:0.1rem 0.7rem; gap:0.35rem;">Tables</button>
            </div>
        </div>
    </header>

    <div class="container">
        <?php
        // Check if connection was successful
        if ($conn->connect_error) {
            die("<div class='text-red-600 text-center text-lg'>Connection failed: " . $conn->connect_error . "</div>");
        }

        // --- Fetch Data for Statistics and Visualizations ---

        // Total Counts
        $total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
        $total_projects = $conn->query("SELECT COUNT(*) AS count FROM projects")->fetch_assoc()['count'];
        $total_certificates = $conn->query("SELECT COUNT(*) AS count FROM certificates")->fetch_assoc()['count'];

        // Users by Department
        $sql_users_by_department = "SELECT department, COUNT(*) AS count FROM users WHERE department IS NOT NULL GROUP BY department ORDER BY count DESC";
        $result_users_by_department = $conn->query($sql_users_by_department);
        $users_by_department_data = [];
        while ($row = $result_users_by_department->fetch_assoc()) {
            $users_by_department_data[] = $row;
        }

        // Users by Role
        $sql_users_by_role = "SELECT role, COUNT(*) AS count FROM users GROUP BY role ORDER BY count DESC";
        $result_users_by_role = $conn->query($sql_users_by_role);
        $users_by_role_data = [];
        while ($row = $result_users_by_role->fetch_assoc()) {
            $users_by_role_data[] = $row;
        }

        // Users by Status
        $sql_users_by_status = "SELECT status, COUNT(*) AS count FROM users GROUP BY status ORDER BY count DESC";
        $result_users_by_status = $conn->query($sql_users_by_status);
        $users_by_status_data = [];
        while ($row = $result_users_by_status->fetch_assoc()) {
            $users_by_status_data[] = $row;
        }

        // Projects by Status
        $sql_projects_by_status = "SELECT status, COUNT(*) AS count FROM projects GROUP BY status ORDER BY count DESC";
        $result_projects_by_status = $conn->query($sql_projects_by_status);
        $projects_by_status_data = [];
        while ($row = $result_projects_by_status->fetch_assoc()) {
            $projects_by_status_data[] = $row;
        }
        ?>

        <div id="statsContent" class="tab-content">
            <h2 class="text-2xl font-semibold section-title mt-8">Key Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card rounded-lg">
                    <h3>Total Users</h3>
                    <p><?php echo $total_users; ?></p>
                </div>
                <div class="stat-card rounded-lg">
                    <h3>Total Projects</h3>
                    <p><?php echo $total_projects; ?></p>
                </div>
                <div class="stat-card rounded-lg">
                    <h3>Total Certificates</h3>
                    <p><?php echo $total_certificates; ?></p>
                </div>
            </div>

            <h2 class="text-2xl font-semibold section-title mt-8">Visualizations</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="chart-container rounded-lg">
                    <h3 class="text-xl font-semibold mb-4 text-center">Users by Department</h3>
                    <canvas id="usersByDepartmentChart"></canvas>
                </div>

                <div class="chart-container rounded-lg">
                    <h3 class="text-xl font-semibold mb-4 text-center">Users by Role</h3>
                    <canvas id="usersByRoleChart"></canvas>
                </div>

                <div class="chart-container rounded-lg">
                    <h3 class="text-xl font-semibold mb-4 text-center">Users by Status</h3>
                    <canvas id="usersByStatusChart"></canvas>
                </div>

                <div class="chart-container rounded-lg">
                    <h3 class="text-xl font-semibold mb-4 text-center">Projects by Status</h3>
                    <canvas id="projectsByStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div id="tablesContent" class="tab-content hidden">
            <h2 class="text-2xl font-semibold section-title mt-8">Users Details</h2>
            <?php
            // Modified SQL query to include contact_info, noc_path, and referral_path
            $sql_users = "SELECT name, email, roll_no, department, batch, contact_info, college, program, semester, duration, noc_path, referral_type, referral_path, role, status, created_at FROM users";
            $result_users = $conn->query($sql_users);

            if ($result_users->num_rows > 0) {
                echo "<div class='overflow-x-auto rounded-lg shadow-md'>";
                echo "<table class='min-w-full'>";
                echo "<thead><tr>";
                // Dynamically get column names for users table
                while ($fieldinfo = $result_users->fetch_field()) {
                    echo "<th class='px-4 py-2'>" . htmlspecialchars(str_replace('_', ' ', ucfirst($fieldinfo->name))) . "</th>";
                }
                echo "</tr></thead>";
                echo "<tbody>";
                while($row = $result_users->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        // Apply new classes for table data cells
                        echo "<td class='px-3 py-2 text-sm'>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<p class='text-gray-600'>No users found.</p>";
            }
            ?>

            <h2 class="text-2xl font-semibold section-title mt-8">Projects Details</h2>
            <?php
            $sql_projects = "SELECT p.id, u.name AS user_name, p.project_name, p.file_path, p.report_path, p.status, p.submission_date FROM projects p JOIN users u ON p.user_id = u.id";
            $result_projects = $conn->query($sql_projects);

            if ($result_projects->num_rows > 0) {
                echo "<div class='overflow-x-auto rounded-lg shadow-md'>";
                echo "<table class='min-w-full'>";
                echo "<thead><tr>";
                // Dynamically get column names for projects table
                while ($fieldinfo = $result_projects->fetch_field()) {
                    echo "<th class='px-4 py-2'>" . htmlspecialchars(str_replace('_', ' ', ucfirst($fieldinfo->name))) . "</th>";
                }
                echo "</tr></thead>";
                echo "<tbody>";
                while($row = $result_projects->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td class='px-3 py-2 text-sm'>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<p class='text-gray-600'>No projects found.</p>";
            }
            ?>

            <h2 class="text-2xl font-semibold section-title mt-8">Certificates Details</h2>
            <?php
            $sql_certificates = "SELECT c.id, u.name AS user_name, c.certificate_path, c.qr_code_path, c.issue_date FROM certificates c JOIN users u ON c.user_id = u.id";
            $result_certificates = $conn->query($sql_certificates);

            if ($result_certificates->num_rows > 0) {
                echo "<div class='overflow-x-auto rounded-lg shadow-md'>";
                echo "<table class='min-w-full'>";
                echo "<thead><tr>";
                // Dynamically get column names for certificates table
                while ($fieldinfo = $result_certificates->fetch_field()) {
                    echo "<th class='px-4 py-2'>" . htmlspecialchars(str_replace('_', ' ', ucfirst($fieldinfo->name))) . "</th>";
                }
                echo "</tr></thead>";
                echo "<tbody>";
                while($row = $result_certificates->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $key => $value) {
                        echo "<td class='px-3 py-2 text-sm'>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<p class='text-gray-600'>No certificates found.</p>";
            }

            // Close database connection
            $conn->close();
            ?>
        </div>
    </div>

    <script>
        // Data passed from PHP to JavaScript
        const usersByDepartmentData = <?php echo json_encode($users_by_department_data); ?>;
        const usersByRoleData = <?php echo json_encode($users_by_role_data); ?>;
        const usersByStatusData = <?php echo json_encode($users_by_status_data); ?>;
        const projectsByStatusData = <?php echo json_encode($projects_by_status_data); ?>;

        // Function to generate random colors for charts
        function generateRandomColors(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const r = Math.floor(Math.random() * 200);
                const g = Math.floor(Math.random() * 200);
                const b = Math.floor(Math.random() * 200);
                colors.push(`rgba(${r}, ${g}, ${b}, 0.6)`);
            }
            return colors;
        }

        // Chart for Users by Department
        if (usersByDepartmentData.length > 0) {
            const departmentLabels = usersByDepartmentData.map(item => item.department);
            const departmentCounts = usersByDepartmentData.map(item => item.count);
            const departmentColors = generateRandomColors(departmentLabels.length);

            new Chart(document.getElementById('usersByDepartmentChart'), {
                type: 'bar',
                data: {
                    labels: departmentLabels,
                    datasets: [{
                        label: 'Number of Users',
                        data: departmentCounts,
                        backgroundColor: departmentColors,
                        borderColor: departmentColors.map(color => color.replace('0.6', '1')), // Darker border
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Department'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Chart for Users by Role
        if (usersByRoleData.length > 0) {
            const roleLabels = usersByRoleData.map(item => item.role);
            const roleCounts = usersByRoleData.map(item => item.count);
            const roleColors = generateRandomColors(roleLabels.length);

            new Chart(document.getElementById('usersByRoleChart'), {
                type: 'bar',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Number of Users',
                        data: roleCounts,
                        backgroundColor: roleColors,
                        borderColor: roleColors.map(color => color.replace('0.6', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Role'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Chart for Users by Status
        if (usersByStatusData.length > 0) {
            const statusLabels = usersByStatusData.map(item => item.status);
            const statusCounts = usersByStatusData.map(item => item.count);
            const statusColors = generateRandomColors(statusLabels.length);

            new Chart(document.getElementById('usersByStatusChart'), {
                type: 'bar',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        label: 'Number of Users',
                        data: statusCounts,
                        backgroundColor: statusColors,
                        borderColor: statusColors.map(color => color.replace('0.6', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Status'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Chart for Projects by Status
        if (projectsByStatusData.length > 0) {
            const projectStatusLabels = projectsByStatusData.map(item => item.status);
            const projectStatusCounts = projectsByStatusData.map(item => item.count);
            const projectStatusColors = generateRandomColors(projectStatusLabels.length);

            new Chart(document.getElementById('projectsByStatusChart'), {
                type: 'bar',
                data: {
                    labels: projectStatusLabels,
                    datasets: [{
                        label: 'Number of Projects',
                        data: projectStatusCounts,
                        backgroundColor: projectStatusColors,
                        borderColor: projectStatusColors.map(color => color.replace('0.6', '1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Projects'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Project Status'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Tab functionality
        const statsTab = document.getElementById('statsTab');
        const tablesTab = document.getElementById('tablesTab');
        const statsContent = document.getElementById('statsContent');
        const tablesContent = document.getElementById('tablesContent');

        function showTab(tabToShow) {
            // Hide all content sections
            statsContent.classList.add('hidden');
            tablesContent.classList.add('hidden');

            // Deactivate all tab buttons
            statsTab.classList.remove('active');
            tablesTab.classList.remove('active');

            // Show the selected content and activate the corresponding tab
            if (tabToShow === 'stats') {
                statsContent.classList.remove('hidden');
                statsTab.classList.add('active');
            } else if (tabToShow === 'tables') {
                tablesContent.classList.remove('hidden');
                tablesTab.classList.add('active');
            }
        }

        // Event listeners for tab buttons
        statsTab.addEventListener('click', () => showTab('stats'));
        tablesTab.addEventListener('click', () => showTab('tables'));

        // Ensure the correct tab is shown on initial load (default to stats)
        document.addEventListener('DOMContentLoaded', () => {
            showTab('stats');
        });
    </script>
</body>
</html>