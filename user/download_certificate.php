<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch certificate and user data
$stmt = $conn->prepare("SELECT c.certificate_path, u.roll_no FROM certificates c JOIN users u ON c.user_id = u.id WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();
$stmt->close();

$roll_no = $certificate['roll_no'] ?? 'certificate';

if ($certificate && file_exists($certificate['certificate_path'])) {
    $cert_path = $certificate['certificate_path'];
            $ext = strtolower(pathinfo($cert_path, PATHINFO_EXTENSION));
    
    // If it's HTML, render it
    if ($ext === 'html') {
        $html_content = file_get_contents($cert_path);
        
        // Add html2canvas script for image conversion
        $html_content = str_replace('</body>', '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            window.onload = function() {
                const downloadBtn = document.createElement("button");
                downloadBtn.textContent = "Download as Image";
                downloadBtn.style.cssText = "position: fixed; top: 20px; right: 20px; padding: 15px 30px; background: #22c55e; color: white; border: 3px solid #000; font-weight: bold; cursor: pointer; z-index: 9999; box-shadow: 4px 4px 0 #000;";
                document.body.appendChild(downloadBtn);
                
                downloadBtn.onclick = function() {
                    downloadBtn.textContent = "Generating...";
                    html2canvas(document.querySelector(".page-wrapper"), {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: "#ffffff"
                    }).then(canvas => {
                        const link = document.createElement("a");
                        link.download = "IFFCO_Certificate_' . $roll_no . '.png";
                        link.href = canvas.toDataURL("image/png");
                        link.click();
                        downloadBtn.textContent = "Download as Image";
                    });
                };
            };
        </script>
        </body>', $html_content);
        
        echo $html_content;
        exit;
    }
    
            // For PDF files, serve directly
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="IFFCO_Certificate_' . $roll_no . '.pdf"');
    readfile($cert_path);
    exit;
}

$conn->close();
header("Location: certificates.php");
exit;
header("Location: certificates.php");
exit;

?>
