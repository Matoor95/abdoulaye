<?php
require_once(__DIR__ . '/../tcpdf/tcpdf.php');
session_start();
require_once('../config/db.php');

// Vérification
$infirmier_id = $_SESSION['utilisateur']['id'];
$consultation_id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("
    SELECT c.*, p.nom AS nom_patient, p.prenom AS prenom_patient, p.date_naissance,
           rm.diagnostic, rm.ordonnance
    FROM consultations c
    JOIN patients p ON p.id = c.patient_id
    LEFT JOIN reponses_medicales rm ON rm.consultation_id = c.id
    WHERE c.id = ? AND c.infirmier_id = ?
");
$stmt->execute([$consultation_id, $infirmier_id]);
$data = $stmt->fetch();

if (!$data) {
    die('Consultation introuvable ou non autorisée.');
}

// PDF
$pdf = new TCPDF();
$pdf->SetTitle("Fiche de consultation");
$pdf->AddPage();

// Logo
$pdf->Image('../assets/logo.png', 15, 10, 30);
$pdf->Ln(20);

// Titre
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Fiche de Téléconsultation', 0, 1, 'C');
$pdf->Ln(5);

// Infos patient
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8, "Patient : {$data['prenom_patient']} {$data['nom_patient']}", 0, 'L');
$pdf->MultiCell(0, 8, "Date de naissance : {$data['date_naissance']}", 0, 'L');
$pdf->MultiCell(0, 8, "Date de consultation : {$data['date_consultation']}", 0, 'L');
$pdf->Ln(5);

// Diagnostic
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Write(0, "Diagnostic :", '', 0, 'L');
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8, $data['diagnostic'], 0, 'L');
$pdf->Ln(5);

// Ordonnance
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Write(0, "Ordonnance :", '', 0, 'L');
$pdf->Ln(6);
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8, $data['ordonnance'], 0, 'L');
$pdf->Ln(10);

// Signature
$pdf->SetFont('helvetica', '', 12);
$pdf->Write(0, "Signature du médecin : ______________________");

// Sortie
$pdf->Output('fiche_consultation.pdf', 'I');
