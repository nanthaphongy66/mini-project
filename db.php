<?php
// ==========================
// ⚙️ CONFIGURATION
// ==========================

// 🔧 เปิด/ปิดการใช้งาน JSON Mode
// true = ใช้ JSON ไฟล์ | false = ใช้ PostgreSQL Database
define('USE_JSON_MODE', false);

// 📁 กำหนด path ของไฟล์ JSON
define('JSON_POINTS_FILE', __DIR__ . '/data/points.json');
define('JSON_STUDENTS_FILE', __DIR__ . '/data/students.json');

// ==========================
// 🗄️ Database Configuration (สำหรับ PostgreSQL Mode)
// ==========================
$host = "localhost";
$dbname = "mini";
$user = "postgres";
$pass = "postgres";

// ==========================
// 📦 Initialize
// ==========================
$pdo = null;

if (USE_JSON_MODE) {
    // ✅ JSON Mode: สร้างโฟลเดอร์และไฟล์ JSON หากยังไม่มี
    $dataDir = __DIR__ . '/data';
    
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    
    // สร้างไฟล์ JSON เริ่มต้น
    if (!file_exists(JSON_POINTS_FILE)) {
        file_put_contents(JSON_POINTS_FILE, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    if (!file_exists(JSON_STUDENTS_FILE)) {
        $defaultStudents = [
            
        ];
        file_put_contents(JSON_STUDENTS_FILE, json_encode($defaultStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    // ✅ ลบ echo ออก เพื่อไม่ให้ส่ง HTML comment ไปยุ่งกับ JSON
    // echo "<!-- ✅ Running in JSON Mode -->\n";
    
} else {
    // ✅ Database Mode: เชื่อมต่อ PostgreSQL
    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "<!-- ✅ Running in Database Mode -->\n";
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage();
        exit;
    }
}

// ==========================
// 📖 Helper Functions สำหรับ JSON Mode
// ==========================

/**
 * อ่านข้อมูลจากไฟล์ JSON
 */
function readJSON($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

/**
 * เขียนข้อมูลลงไฟล์ JSON
 */
function writeJSON($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * สร้าง ID ใหม่อัตโนมัติ
 */
function getNextId($data) {
    if (empty($data)) {
        return 1;
    }
    $maxId = max(array_column($data, 'id'));
    return $maxId + 1;
}
?>