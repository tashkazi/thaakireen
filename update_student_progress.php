<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$surah_id_raw = $data['last_memorized_surah_id'] ?? null;
$dua_id_raw = $data['last_memorized_dua_id'] ?? null;

$surah_id = is_numeric($surah_id_raw) ? (int)$surah_id_raw : null;
$dua_id = is_numeric($dua_id_raw) ? (int)$dua_id_raw : null;

$readingMaterial = $data['readingMaterial'] ?? '';
$qaidaPage = isset($data['qaidaPage']) && $data['qaidaPage'] !== '' ? (int)$data['qaidaPage'] : null;
$book_grade = $data['book_grade'] ?? '';
$notes = $data['notes'] ?? '';

if (!$id) {
    echo json_encode(['message' => 'Student ID is missing']);
    exit;
}

// 🗓️ School year block for August
$month = (int)date('n');
if ($month === 8) {
    echo json_encode(['message' => 'Updates are locked during August. Please wait until the new school year starts in September.']);
    exit;
}

// DB connection
$host = "localhost";
$username = "root";
$password = "Tashreeka94_";
$database = "thaakireen";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

// Determine current school year
if ($month >= 9) {
    $school_year = date('Y') . '/' . (date('Y') + 1);
} else {
    $school_year = (date('Y') - 1) . '/' . date('Y');
}

// Archive year check
$archivedCheck = $conn->prepare("SELECT 1 FROM archived_years WHERE school_year = ?");
$archivedCheck->bind_param("s", $school_year);
$archivedCheck->execute();
$archivedCheck->store_result();
if ($archivedCheck->num_rows > 0) {
    echo json_encode(['message' => 'This school year is archived. Progress cannot be updated.']);
    $archivedCheck->close();
    exit;
}
$archivedCheck->close();

// Validate foreign keys exist (only if not null)
if ($surah_id !== null) {
    $checkSurah = $conn->prepare("SELECT id FROM surahs WHERE id = ?");
    $checkSurah->bind_param("i", $surah_id);
    $checkSurah->execute();
    if (!$checkSurah->get_result()->num_rows) {
        echo json_encode(['message' => 'Invalid Surah ID']);
        exit;
    }
}
if ($dua_id !== null) {
    $checkDua = $conn->prepare("SELECT id FROM duas WHERE id = ?");
    $checkDua->bind_param("i", $dua_id);
    $checkDua->execute();
    if (!$checkDua->get_result()->num_rows) {
        echo json_encode(['message' => 'Invalid Dua ID']);
        exit;
    }
}

// Prepare query
$query = "UPDATE students 
          SET last_memorized_surah_id = ?, 
              last_memorized_dua_id = ?, 
              readingMaterial = ?, 
              qaidaPage = ?, 
              book_grade = ?, 
              notes = ?, 
              updatedAt = NOW()
          WHERE id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    'iissssi',
    $surah_id,
    $dua_id,
    $readingMaterial,
    $qaidaPage,
    $book_grade,
    $notes,
    $id
);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Student progress updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Error updating student: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
