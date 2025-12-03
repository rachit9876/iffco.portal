<?php
session_start();
require_once '../db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];


// Get roll_no
$stmt = $conn->prepare("SELECT roll_no FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($roll_no);
$stmt->fetch();
$stmt->close();

$target_dir = "uploads/" . $roll_no . "/";
$report_path = $target_dir . "report.pdf";
$project_path = $target_dir . "project.zip";

// Handle delete request
if (isset($_POST['delete_all'])) {
    if (file_exists($report_path)) unlink($report_path);
    if (file_exists($project_path) && strpos($project_path, 'http') !== 0) unlink($project_path);
    
    $stmt = $conn->prepare("DELETE FROM projects WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: projects.php?msg=Project and report deleted successfully");
    exit;
}

// Upload Project and Report
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_project'])) {
    $project_name = trim($_POST['project_name']);
    $github_url = trim($_POST['github_url'] ?? '');
    $has_file = $_FILES["project_file"]["error"] !== UPLOAD_ERR_NO_FILE;
    $has_github = !empty($github_url);
    $error_msg = '';
    
    if (empty($project_name)) {
        $error_msg = "Project title is required.";
    } elseif (!$has_file && !$has_github) {
        $error_msg = "Either upload a project file or provide a GitHub URL.";
    } elseif ($has_file && $has_github) {
        $error_msg = "Please choose either file upload or GitHub URL, not both.";
    } elseif ($has_github) {
        // Validate GitHub repo name format: iffco-yourname
        $student_name = strtolower(str_replace(' ', '-', $_SESSION['name']));
        $expected_repo = 'iffco-' . $student_name;
        
        if (preg_match('/github\.com\/([^\/]+)\/([^\/\?]+)/', $github_url, $matches)) {
            $username = $matches[1];
            $repo_name = strtolower(trim($matches[2], '/'));
            if ($repo_name !== $expected_repo) {
                $error_msg = "Repository must be named: " . $expected_repo;
            } else {
                $api_url = "https://api.github.com/repos/{$username}/{$repo_name}/git/trees/main?recursive=1";
                $context = stream_context_create(['http' => ['user_agent' => 'PHP']]);
                $response = @file_get_contents($api_url, false, $context);
                if ($response === false) {
                    $response = @file_get_contents("https://api.github.com/repos/{$username}/{$repo_name}/git/trees/master?recursive=1", false, $context);
                }
                if ($response === false) {
                    $error_msg = "Unable to verify GitHub repository. Please check the URL.";
                } else {
                    $data = json_decode($response, true);
                    if (isset($data['message'])) {
                        if ($data['message'] === 'Not Found') {
                            $error_msg = "Repository not found or not public. Make sure the repository exists and is public.";
                        } elseif ($data['message'] === 'Git Repository is empty.') {
                            $error_msg = "Repository is empty. Please add files to your repository before submitting.";
                        } else {
                            $error_msg = "Repository validation failed: " . $data['message'];
                        }
                    } elseif (isset($data['tree'])) {
                        $valid_files = 0;
                        $file_list = [];
                        foreach ($data['tree'] as $item) {
                            if ($item['type'] === 'blob') {
                                $file_list[] = $item['path'];
                                $filename = strtolower(basename($item['path']));
                                if ($filename !== '.gitattributes' && $filename !== '.gitignore' && $filename !== 'readme.md' && $filename !== 'license') {
                                    $valid_files++;
                                }
                            }
                        }
                        if ($valid_files < 1) {
                            $error_msg = "Repository must contain at least one project file (excluding .gitattributes, .gitignore, README.md, and LICENSE).";
                        }
                    }
                }
            }
        } else {
            $error_msg = "Invalid GitHub URL format.";
        }
    }
    
    if (empty($error_msg) && $_FILES["report_file"]["error"] === UPLOAD_ERR_NO_FILE) {
        $error_msg = "Report file is required.";
    }
    
    if (empty($error_msg)) {
        $report_ext = strtolower(pathinfo($_FILES["report_file"]["name"], PATHINFO_EXTENSION));
        
        if ($_FILES["report_file"]["size"] > 30 * 1024 * 1024) {
            $error_msg = "Report file too large. Max 30MB.";
        } elseif ($report_ext !== "pdf") {
            $error_msg = "Only PDF allowed for report.";
        } else {
            $project_source = $github_url;
            
            if ($has_file) {
                $project_ext = strtolower(pathinfo($_FILES["project_file"]["name"], PATHINFO_EXTENSION));
                if ($_FILES["project_file"]["size"] > 30 * 1024 * 1024) {
                    $error_msg = "Project file too large. Max 30MB.";
                } elseif ($project_ext !== "zip") {
                    $error_msg = "Only ZIP allowed for project.";
                } elseif (!move_uploaded_file($_FILES["project_file"]["tmp_name"], $project_path)) {
                    $error_msg = "Failed to upload project file.";
                } else {
                    $project_source = $project_path;
                }
            }
            
            if (empty($error_msg) && move_uploaded_file($_FILES["report_file"]["tmp_name"], $report_path)) {
                $stmt_check = $conn->prepare("SELECT id FROM projects WHERE user_id = ?");
                $stmt_check->bind_param("i", $user_id);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $stmt = $conn->prepare("UPDATE projects SET project_name = ?, file_path = ?, report_path = ?, status = 'Completed', submission_date = NOW() WHERE user_id = ?");
                    $stmt->bind_param("sssi", $project_name, $project_source, $report_path, $user_id);
                } else {
                    $stmt = $conn->prepare("INSERT INTO projects (user_id, project_name, file_path, report_path, status) VALUES (?, ?, ?, ?, 'Completed')");
                    $stmt->bind_param("isss", $user_id, $project_name, $project_source, $report_path);
                }
                $stmt->execute();
                $stmt->close();
                $stmt_check->close();
                
                header("Location: projects.php?msg=Project and report uploaded successfully");
                exit;
            } elseif (empty($error_msg)) {
                $error_msg = "Failed to upload report.";
            }
        }
    }
    
    if (!empty($error_msg)) {
        header("Location: projects.php?error=" . urlencode($error_msg));
        exit;
    }
}

// Fetch Project Data
$project = null;
$report_uploaded = false;
$project_uploaded = false;
$stmt = $conn->prepare("SELECT project_name, status, submission_date, file_path, report_path FROM projects WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $project = $result->fetch_assoc();
    if (!empty($project['file_path'])) $project_uploaded = true;
    if (!empty($project['report_path'])) $report_uploaded = true;
}
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Projects - IFFCO Portal</title>
  <link rel="stylesheet" href="../neobrutalist.css">
</head>
<body style="margin: 0; display: flex; background: #f5f5f5;">

<button class="unver-mobile-menu-btn" aria-label="Menu">
  <span class="unver-menu-icon">
    <span class="unver-menu-line unver-menu-line-1"></span>
    <span class="unver-menu-line unver-menu-line-2"></span>
    <span class="unver-menu-line unver-menu-line-3"></span>
  </span>
</button>

<aside class="unver-sidebar">
    <div style="padding: 24px; text-align: center;">
        <img src="/uploads/IFFCOlogo.svg" alt="IFFCO Logo" style="max-width: 120px; margin: 0 auto;">
    </div>
    <nav style="padding: 0 16px;">
        <a href="dashboard.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Dashboard</a>
        <a href="profile.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Profile & Details</a>
        <a href="projects.php" class="unver-btn unver-btn-primary unver-w-full unver-mb-sm" style="text-decoration: none;">Projects</a>
        <a href="certificates.php" class="unver-btn unver-w-full unver-mb-sm" style="text-decoration: none;">Certificates</a>
        <a href="../logout.php" class="unver-btn unver-btn-danger unver-w-full" style="text-decoration: none; margin-top: 40px;">Logout</a>
    </nav>
</aside>

<main style="flex: 1; padding: 40px;">
  <header class="unver-mb-xl">
    <h1 class="unver-h1">Project Submission</h1>
    <p class="unver-text-muted">Upload your project file and track its status here.</p>
  </header>



  <div class="unver-grid unver-grid-2 unver-mb-xl">
    <div class="unver-card">
      <div class="unver-card-body">
        <h2 class="unver-h3 unver-mb-md">Upload Project & Report</h2>

        <form action="projects.php" method="post" enctype="multipart/form-data" id="uploadForm">
          <label class="unver-label unver-text-sm">Project Title <span class="unver-text-muted">(will be shown on certificate)</span></label>
          <input type="text" name="project_name" required class="unver-input unver-mb-md">

          <label class="unver-label unver-text-sm">Upload Method</label>
          <div style="display: flex; gap: 8px; margin-bottom: 16px;">
            <button type="button" id="githubTab" class="unver-btn unver-btn-primary" style="flex: 1;" onclick="switchTab('github')">GitHub URL</button>
            <button type="button" id="zipTab" class="unver-btn" style="flex: 1;" onclick="switchTab('zip')">ZIP File</button>
          </div>

          <div id="githubSection">
            <label class="unver-label unver-text-sm">Public GitHub Repository URL</label>
            <p class="unver-text-xs unver-mb-sm" style="background: #ff0; color: #000; padding: 8px; border: 2px solid #000;">Repository must be named: <strong>iffco-<?php echo strtolower(str_replace(' ', '-', $_SESSION['name'])); ?></strong></p>
            <input type="url" name="github_url" id="githubUrl" placeholder="https://github.com/username/iffco-<?php echo strtolower(str_replace(' ', '-', $_SESSION['name'])); ?>" class="unver-input">
          </div>

          <div id="zipSection" style="display: none;">
            <label class="unver-label unver-text-sm">Upload Project (.zip, max 30MB)</label>
            <div style="display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap;">
              <input type="file" name="project_file" accept=".zip" id="projectFile" class="unver-input" style="flex: 1; min-width: 200px;">
              <button type="button" onclick="document.getElementById('projectFile').value='';" class="unver-btn unver-btn-sm unver-btn-danger">Clear</button>
            </div>
          </div>

          <label class="unver-label unver-text-sm" style="margin-top: 16px;">Upload Report (.pdf, max 30MB)</label>
          <div style="display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap;">
            <input type="file" name="report_file" accept=".pdf" required id="reportFile" class="unver-input" style="flex: 1; min-width: 200px;">
            <button type="button" onclick="document.getElementById('reportFile').value=''; document.getElementById('reportFile').dispatchEvent(new Event('change'));" class="unver-btn unver-btn-sm unver-btn-danger">Clear</button>
          </div>
          <div class="unver-mb-md"></div>

          <button type="button" onclick="submitForm()" class="unver-btn unver-btn-primary unver-w-full">Upload Project & Report</button>
        </form>
      </div>
    </div>
    <div class="unver-card">
      <div class="unver-card-body">
        <h2 class="unver-h3 unver-mb-md">Current Status</h2>
        <?php if ($project): ?>
          <div>
            <h3 class="unver-font-bold unver-mb-sm">Project Title:</h3>
            <p class="unver-mb-md"><?php echo htmlspecialchars($project['project_name']); ?></p>

            <h3 class="unver-font-bold unver-mb-sm">Status:</h3>
            <p class="unver-mb-sm">
              <?php echo $project_uploaded ? '✅ <span style="color: var(--unver-success); font-weight: bold;">Project: DONE</span>' : '❌ <span style="color: var(--unver-danger);">Project: LEFT</span>'; ?>
            </p>
            <p class="unver-mb-md">
              <?php echo $report_uploaded ? '✅ <span style="color: var(--unver-success); font-weight: bold;">Report: DONE</span>' : '❌ <span style="color: var(--unver-danger);">Report: LEFT</span>'; ?>
            </p>

            <h3 class="unver-font-bold unver-mb-sm">Submission Date:</h3>
            <p class="unver-mb-md"><?php echo isset($project['submission_date']) ? date("F j, Y, g:i a", strtotime($project['submission_date'])) : "—"; ?></p>

            <?php if (!empty($project['file_path']) || !empty($project['report_path'])): ?>
              <div style="display: flex; flex-direction: column; gap: 10px;" class="unver-mb-md">
                <?php if (!empty($project['file_path'])): ?>
                  <?php if (strpos($project['file_path'], 'http') === 0): ?>
                    <a href="<?php echo htmlspecialchars($project['file_path']); ?>" target="_blank" class="unver-btn unver-btn-sm unver-btn-primary" style="text-decoration: none;">View GitHub Repo</a>
                  <?php else: ?>
                    <a href="<?php echo htmlspecialchars($project['file_path']); ?>" class="unver-btn unver-btn-sm unver-btn-primary" download style="text-decoration: none;">Download Project</a>
                  <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($project['report_path'])): ?>
                  <a href="<?php echo htmlspecialchars($project['report_path']); ?>" class="unver-btn unver-btn-sm unver-btn-success" download style="text-decoration: none;">Download Report</a>
                <?php endif; ?>
              </div>
              <form method="post" id="deleteForm">
                <button type="button" onclick="showDialog('Are you sure you want to delete both project and report files? This action cannot be undone.', () => document.getElementById('deleteForm').submit())" class="unver-btn unver-btn-sm unver-btn-danger unver-w-full">Delete All Files</button>
                <input type="hidden" name="delete_all" value="1">
              </form>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div style="text-align: center; padding: 40px 0;">
            <h3 class="unver-h3">No Project Submitted Yet</h3>
            <p class="unver-text-muted unver-mb-md">Use the form to upload your first project and report.</p>
            <span class="unver-badge unver-badge-warning">Not Started</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<script>
  const menuButton = document.querySelector('.unver-mobile-menu-btn');
  const sidebar = document.querySelector('.unver-sidebar');

  menuButton.addEventListener('click', function () {
    this.classList.toggle('active');
    sidebar.classList.toggle('active');
  });

  if (window.innerWidth <= 768) {
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        menuButton.classList.remove('active');
        sidebar.classList.remove('active');
      });
    });
  }
</script>

<script src="../dialog.js"></script>
<script src="../toast.js"></script>
<script>
    <?php if (isset($_GET['msg'])): ?>showToast(<?php echo json_encode($_GET['msg']); ?>, 'success');<?php endif; ?>
    <?php if (isset($_GET['error'])): ?>showToast(<?php echo json_encode($_GET['error']); ?>, 'error');<?php endif; ?>
    
    function switchTab(mode) {
        const zipTab = document.getElementById('zipTab');
        const githubTab = document.getElementById('githubTab');
        const zipSection = document.getElementById('zipSection');
        const githubSection = document.getElementById('githubSection');
        const projectFile = document.getElementById('projectFile');
        const githubUrl = document.getElementById('githubUrl');
        
        if (mode === 'zip') {
            zipTab.classList.add('unver-btn-primary');
            githubTab.classList.remove('unver-btn-primary');
            zipSection.style.display = 'block';
            githubSection.style.display = 'none';
            githubUrl.value = '';
        } else {
            githubTab.classList.add('unver-btn-primary');
            zipTab.classList.remove('unver-btn-primary');
            githubSection.style.display = 'block';
            zipSection.style.display = 'none';
            projectFile.value = '';
        }
    }
    
    function showAnalyzing(files) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';
        overlay.innerHTML = '<div style="background: #fff; border: 4px solid #000; box-shadow: 12px 12px 0 #888; padding: 40px; text-align: center; max-width: 500px; width: 90%;"><h3 style="font-size: 24px; font-weight: 900; margin-bottom: 16px;">AI Repository Analysis</h3><p id="analyzeText" style="margin-bottom: 24px; min-height: 24px; font-family: monospace; color: #000; text-align: left; font-weight: bold;"></p><div style="width: 100%; height: 4px; background: #f0f0f0; border: 2px solid #000;"><div id="progressBar" style="width: 0%; height: 100%; background: #ff5f57;"></div></div></div>';
        document.body.appendChild(overlay);
        
        const progressBar = document.getElementById('progressBar');
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 1;
            progressBar.style.width = progress + '%';
            if (progress >= 100) clearInterval(progressInterval);
        }, 100);
        
        const messages = [
            'CLONING',
            'ANALYZING'
        ];
        
        const textEl = document.getElementById('analyzeText');
        let msgIndex = 0;
        let charIndex = 0;
        let fileIndex = 0;
        
        function typeMessage() {
            if (msgIndex < messages.length) {
                if (messages[msgIndex] === 'CLONING') {
                    const cloningMsgs = [
                        'AI is cloning your repository...',
                        'Downloading project files...',
                        'Setting up analysis environment...'
                    ];
                    if (charIndex < cloningMsgs.length) {
                        textEl.textContent = cloningMsgs[charIndex];
                        charIndex++;
                        setTimeout(typeMessage, 1000);
                    } else {
                        msgIndex++;
                        charIndex = 0;
                        textEl.textContent = '';
                        typeMessage();
                    }
                } else if (messages[msgIndex] === 'ANALYZING') {
                    const analyzingMsgs = [
                        'AI is analyzing your code...',
                        'Checking code quality...',
                        'Validating project structure...',
                        'Verifying dependencies...'
                    ];
                    if (charIndex < analyzingMsgs.length) {
                        textEl.textContent = analyzingMsgs[charIndex];
                        charIndex++;
                        setTimeout(typeMessage, 1000);
                    } else {
                        msgIndex++;
                        charIndex = 0;
                        textEl.textContent = '';
                        typeMessage();
                    }
                }
            }
        }
        
        typeMessage();
        return overlay;
    }
    
    function submitForm() {
        const form = document.getElementById('uploadForm');
        const projectName = document.querySelector('input[name="project_name"]').value.trim();
        const reportFile = document.getElementById('reportFile').files.length;
        const githubUrl = document.getElementById('githubUrl').value.trim();
        const zipFile = document.getElementById('projectFile').files.length;
        
        if (!projectName) {
            showToast('Project title is required.', 'error');
            return;
        }
        
        if (!reportFile) {
            showToast('Report file is required.', 'error');
            return;
        }
        
        const hasGithub = githubUrl !== '';
        const hasZip = zipFile > 0;
        
        if (!hasGithub && !hasZip) {
            showToast('Either upload a project file or provide a GitHub URL.', 'error');
            return;
        }
        
        const formData = new FormData(form);
        let overlay = null;
        let repoFiles = [];
        if (hasGithub) {
            overlay = showAnalyzing(repoFiles);
        }
        
        formData.append('upload_project', '1');
        
        const submitRequest = () => {
            fetch('projects.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                if (overlay) overlay.remove();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const scripts = doc.querySelectorAll('script');
                let hasError = false;
                scripts.forEach(script => {
                    const content = script.textContent;
                    if (content.includes('showToast')) {
                        const match = content.match(/showToast\([^,]+,\s*'(\w+)'\)/);
                        if (match) {
                            const type = match[1];
                            if (type === 'error') hasError = true;
                            eval(content);
                        }
                    }
                });
                if (!hasError) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => {
                if (overlay) overlay.remove();
                console.error(err);
                showToast('Upload failed. Please try again.', 'error');
            });
        };
        
        if (hasGithub) {
            setTimeout(submitRequest, 10000);
        } else {
            submitRequest();
        }
    }
</script>

</body>
</html>
