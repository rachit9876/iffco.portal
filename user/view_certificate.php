<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION['role'] !== 'user') {
    header("location: ../index.php");
    exit;
}

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT certificate_path FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ($certificate && file_exists($certificate['certificate_path'])) {
    $html = file_get_contents($certificate['certificate_path']);
    
    // Add download button with html2canvas
    $html = str_replace('</body>', '
    <button id="downloadBtn" style="position: fixed; top: 20px; right: 20px; padding: 15px 30px; background: #22c55e; color: white; border: 3px solid #000; font-weight: bold; cursor: pointer; z-index: 9999; box-shadow: 4px 4px 0 #000;">Download as Image</button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        document.getElementById("downloadBtn").onclick = function() {
            this.textContent = "Generating...";
            const btn = this;
            html2canvas(document.querySelector(".page-wrapper"), {
                scale: 2,
                useCORS: true,
                backgroundColor: "#ffffff"
            }).then(canvas => {
                const link = document.createElement("a");
                link.download = "IFFCO_Certificate.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
                btn.textContent = "Download as Image";
            });
        };
    </script>
    </body>', $html);
    
    echo $html;
} else {
    header("Location: certificates.php");
}
?>
