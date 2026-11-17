<?php

error_reporting(0);
ini_set('display_errors', 0);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
boot_session();

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isCustomer = isset($_SESSION['user_id']);

if (!$isAdmin && !$isCustomer) {
    http_response_code(403);
    die("Unauthorized access.");
}

$booking_id = (int)($_GET['id'] ?? 0);
if ($booking_id <= 0) {
    http_response_code(400);
    die("Invalid booking ID.");
}

try {
    $sql = "SELECT b.*, v.type_name, p.name as package_name, p.description as package_description
            FROM bookings b
            LEFT JOIN vehicle_types v ON b.vehicle_type_id = v.id
            LEFT JOIN tint_packages p ON b.package_id = p.id
            WHERE b.id = ?";
    $params = [$booking_id];

    if (!$isAdmin) {
        
        $sql .= " AND b.user_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(404);
        die("Booking not found or unauthorized.");
    }

    
    if (strtolower($booking['status'] ?? '') !== 'completed') {
        http_response_code(400);
        die("Warranty certificate is only available for Completed installations.");
    }
} catch (Exception $e) {
    http_response_code(500);
    die("Database Error: " . $e->getMessage());
}

require_once __DIR__ . '/includes/fpdf/fpdf.php';

class WarrantyPDF extends FPDF {
    function Header() {
        
        $this->SetFillColor(15, 15, 15); 
        $this->Rect(10, 10, 190, 38, 'F');

        
        $this->SetFillColor(212, 137, 26); 
        $this->Rect(10, 48, 190, 2, 'F');

        
        $this->SetTextColor(245, 166, 35); 
        $this->SetFont('Arial', 'B', 24);
        $this->SetXY(16, 16);
        $this->Cell(178, 10, 'RG CARTINTWORKS', 0, 1, 'L');

        
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->SetX(16);
        $this->Cell(178, 4, 'PREMIUM WINDOW TINTING & PROTECTION SERVICES', 0, 1, 'L');
        
        $this->SetTextColor(200, 200, 200);
        $this->SetFont('Arial', '', 8);
        $this->SetX(16);
        $this->Cell(178, 4, 'Davao City, Philippines | +63 994 654 6456', 0, 1, 'L');
    }

    function Footer() {
        
        $this->SetDrawColor(220, 220, 220);
        $this->Line(10, 275, 200, 275);

        
        $this->SetXY(10, 277);
        $this->SetTextColor(120, 120, 120);
        $this->SetFont('Arial', 'I', 7.5);
        
        $this->Cell(95, 4, 'RG CARTINTWORKS - Official Digital Warranty Certificate', 0, 0, 'L');
        $this->Cell(95, 4, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
    }
}

$pdf = new WarrantyPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->SetY(55);

$pdf->SetTextColor(15, 15, 15);
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(190, 8, 'OFFICIAL WARRANTY CERTIFICATE', 0, 1, 'C');

$pdf->SetTextColor(80, 80, 80);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(190, 5, 'This document certifies that the vehicle described below has been professionally tinted', 0, 1, 'C');
$pdf->Cell(190, 5, 'by RG Cartintworks using premium grade solar protection films.', 0, 1, 'C');

$pdf->Ln(8);

$startX = 10;
$startY = $pdf->GetY();

$pdf->SetXY($startX, $startY);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetDrawColor(220, 220, 220);
$pdf->Rect($startX, $startY, 92, 72, 'DF');

$pdf->SetXY($startX + 4, $startY + 4);
$pdf->SetTextColor(15, 15, 15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(84, 5, 'VEHICLE & OWNER DETAILS', 'B', 1, 'L');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);

$labels = [
    'Owner Name:' => $booking['customer_name'] ?: 'N/A',
    'Phone Number:' => $booking['phone'] ?: 'N/A',
    'Vehicle Model:' => ($booking['car_brand'] ? $booking['car_brand'] . ' ' : '') . $booking['vehicle_model'],
    'Vehicle Color:' => $booking['vehicle_color'] ?: 'N/A',
    'Vehicle Type:' => $booking['type_name'] ?: 'N/A',
];

$pdf->Ln(2);
foreach ($labels as $lbl => $val) {
    $pdf->SetX($startX + 4);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(28, 6, $lbl, 0, 0, 'L');
    $pdf->SetTextColor(15, 15, 15);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(56, 6, $val, 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
}

$pdf->SetXY($startX + 98, $startY);
$pdf->Rect($startX + 98, $startY, 92, 72, 'DF');

$pdf->SetXY($startX + 102, $startY + 4);
$pdf->SetTextColor(15, 15, 15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(84, 5, 'WARRANTY & INSTALLATION SPECS', 'B', 1, 'L');

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(100, 100, 100);

$warranty_years = (int)($booking['warranty_years'] ?? 0);
if ($warranty_years <= 0) {
    $warranty_years = 5; 
}
$expiry_date = date('M d, Y', strtotime($booking['appointment_date'] . ' + ' . $warranty_years . ' years'));
$expiry_text = "$warranty_years Years ($expiry_date)";

$ref_code = $booking['reference_code'] ?: ($booking['booking_ref'] ?: 'RG-WNT-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT));

$specs = [
    'Certificate No:' => $ref_code,
    'Tint Package:' => $booking['package_name'] ?: 'Custom Tint Package',
    'Film Type:' => ucwords(str_replace('-', ' ', $booking['tint_type'] ?: 'Premium Carbon/Ceramic')),
    'Shade Rating:' => ($booking['shade_percentage'] ? $booking['shade_percentage'] . '%' : 'N/A'),
    'Install Date:' => date('M d, Y', strtotime($booking['appointment_date'])),
    'Warranty Limit:' => $expiry_text,
];

$pdf->Ln(2);
foreach ($specs as $lbl => $val) {
    $pdf->SetX($startX + 102);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(28, 6, $lbl, 0, 0, 'L');
    
    
    if ($lbl === 'Warranty Limit:') {
        $pdf->SetTextColor(212, 137, 26); 
    } else {
        $pdf->SetTextColor(15, 15, 15);
    }
    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(56, 6, $val, 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
}

$pdf->Ln(6);

$pdf->SetX(10);
$pdf->SetFillColor(254, 252, 232); 
$pdf->SetDrawColor(254, 240, 138); 
$pdf->Rect(10, $pdf->GetY(), 190, 42, 'DF');

$pdf->SetY($pdf->GetY() + 3);
$pdf->SetX(14);
$pdf->SetTextColor(146, 64, 14); 
$pdf->SetFont('Arial', 'B', 9.5);
$pdf->Cell(182, 5, 'OFFICIAL WARRANTY COVERAGE TERMS:', 0, 1, 'L');

$pdf->SetTextColor(100, 80, 40);
$pdf->SetFont('Arial', '', 8);

$bullet_points = [
    '- This warranty is fully active from the date of professional installation.',
    '- Coverage covers defects including: adhesive failure, bubbling, peeling, cracking, and excessive color fading.',
    '- Non-coverage: physical scratching, glass breakage, chemical damage, or self-modifications.',
    '- For validation, please scan the QR code/verification link below and present this certificate at the shop.'
];

foreach ($bullet_points as $bp) {
    $pdf->SetX(14);
    $pdf->Cell(182, 4.5, $bp, 0, 1, 'L');
}

$pdf->Ln(5);

$currentY = $pdf->GetY();

$pdf->SetXY(10, $currentY);
$pdf->SetTextColor(15, 15, 15);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(110, 5, 'WARRANTY VERIFICATION PORTAL', 0, 1, 'L');

$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$projRoot = str_replace('\\', '/', realpath(__DIR__));
$webPath = str_replace($docRoot, '', $projRoot);
$verifyUrl = $proto . '://' . $host . $webPath . '/index.php?verify=' . $ref_code;

$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('Arial', '', 8);
$pdf->SetX(10);
$pdf->Cell(110, 4, 'Verify this certificate anytime online by visiting:', 0, 1, 'L');
$pdf->SetX(10);
$pdf->SetTextColor(212, 137, 26); 
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(110, 4, $verifyUrl, 0, 1, 'L');

$pdf->SetDrawColor(212, 137, 26);
$pdf->Rect(10, $pdf->GetY() + 2, 22, 22);
$pdf->SetXY(10, $pdf->GetY() + 4);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetFont('Arial', 'I', 6);
$pdf->MultiCell(22, 3, "SCAN OR VISIT\nTO VALIDATE", 0, 'C');

$pdf->SetXY(130, $currentY);
$pdf->SetTextColor(15, 15, 15);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 5, 'AUTHORIZED SIGNATURE', 0, 1, 'C');

$pdf->Ln(12);
$pdf->SetX(130);
$pdf->Cell(60, 1, '', 'B', 1, 'C');
$pdf->SetX(130);
$pdf->SetFont('Arial', 'B', 8.5);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(60, 4.5, 'RG CARTINTWORKS SPECIALIST', 0, 1, 'C');
$pdf->SetX(130);
$pdf->SetFont('Arial', '', 7.5);
$pdf->Cell(60, 3.5, 'Quality Assurance Team', 0, 1, 'C');

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="warranty_certificate_' . $ref_code . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

$pdf->Output('I', 'warranty_certificate_' . $ref_code . '.pdf');
exit;
?>
