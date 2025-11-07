<?php
header("Content-Type: application/json; charset=utf-8");

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Test</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e293b;color:#e2e8f0}";
echo ".box{background:#334155;padding:20px;border-radius:8px;margin:10px 0}";
echo ".success{color:#10b981}.error{color:#ef4444}.info{color:#3b82f6}</style></head><body>";

echo "<h1>🔍 Database Connection Test</h1>";

// ===========================
// 1. ตรวจสอบไฟล์ db.php
// ===========================
echo "<div class='box'>";
echo "<h2>1️⃣ ตรวจสอบไฟล์ db.php</h2>";

if (file_exists('db.php')) {
    echo "<p class='success'>✅ ไฟล์ db.php พบแล้ว</p>";
    require_once 'db.php';
    
    echo "<p class='info'>📝 USE_JSON_MODE = " . (USE_JSON_MODE ? 'TRUE (JSON Mode)' : 'FALSE (Database Mode)') . "</p>";
} else {
    echo "<p class='error'>❌ ไม่พบไฟล์ db.php</p>";
    die("</div></body></html>");
}
echo "</div>";

// ===========================
// 2. ตรวจสอบการเชื่อมต่อ Database
// ===========================
echo "<div class='box'>";
echo "<h2>2️⃣ ตรวจสอบการเชื่อมต่อ Database</h2>";

if (USE_JSON_MODE) {
    echo "<p class='info'>ℹ️ ระบบอยู่ใน JSON Mode - ข้ามการตรวจสอบ Database</p>";
} else {
    if ($pdo) {
        echo "<p class='success'>✅ เชื่อมต่อ PostgreSQL สำเร็จ</p>";
        
        try {
            // ทดสอบ Query
            $version = $pdo->query('SELECT version()')->fetchColumn();
            echo "<p class='info'>📦 PostgreSQL Version: " . substr($version, 0, 50) . "...</p>";
            
            // ตรวจสอบ Extension PostGIS
            $postgis = $pdo->query("SELECT PostGIS_version()")->fetchColumn();
            echo "<p class='success'>✅ PostGIS Version: $postgis</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ ไม่สามารถเชื่อมต่อ Database</p>";
    }
}
echo "</div>";

// ===========================
// 3. ตรวจสอบตาราง
// ===========================
echo "<div class='box'>";
echo "<h2>3️⃣ ตรวจสอบตารางในฐานข้อมูล</h2>";

if (USE_JSON_MODE) {
    echo "<p class='info'>ℹ️ JSON Mode - ตรวจสอบไฟล์ JSON</p>";
    
    if (file_exists(JSON_POINTS_FILE)) {
        $points = json_decode(file_get_contents(JSON_POINTS_FILE), true);
        echo "<p class='success'>✅ points.json: " . count($points) . " รายการ</p>";
    } else {
        echo "<p class='error'>❌ ไม่พบไฟล์ points.json</p>";
    }
    
    if (file_exists(JSON_STUDENTS_FILE)) {
        $students = json_decode(file_get_contents(JSON_STUDENTS_FILE), true);
        echo "<p class='success'>✅ students.json: " . count($students) . " รายการ</p>";
    } else {
        echo "<p class='error'>❌ ไม่พบไฟล์ students.json</p>";
    }
} else {
    if ($pdo) {
        try {
            // ตรวจสอบตาราง points
            $pointsCount = $pdo->query("SELECT COUNT(*) FROM points")->fetchColumn();
            echo "<p class='success'>✅ ตาราง points: $pointsCount รายการ</p>";
            
            // แสดงข้อมูล 3 รายการแรก
            $stmt = $pdo->query("SELECT id, name, ST_Y(geom::geometry) as lat, ST_X(geom::geometry) as lon FROM points LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre style='background:#1e293b;padding:10px;border-radius:4px;overflow:auto'>";
            print_r($samples);
            echo "</pre>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ ตาราง points: " . $e->getMessage() . "</p>";
            echo "<p class='info'>💡 กรุณารัน SQL Script เพื่อสร้างตาราง</p>";
        }
        
        try {
            // ตรวจสอบตาราง students
            $studentsCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            echo "<p class='success'>✅ ตาราง students: $studentsCount รายการ</p>";
            
            // แสดงข้อมูล 3 รายการแรก
            $stmt = $pdo->query('SELECT id, s_id, s_name, "จังหวัด" FROM students LIMIT 3');
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre style='background:#1e293b;padding:10px;border-radius:4px;overflow:auto'>";
            print_r($samples);
            echo "</pre>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ ตาราง students: " . $e->getMessage() . "</p>";
            echo "<p class='info'>💡 กรุณารัน SQL Script เพื่อสร้างตาราง</p>";
        }
    }
}
echo "</div>";

// ===========================
// 4. ทดสอบ API
// ===========================
echo "<div class='box'>";
echo "<h2>4️⃣ ทดสอบ API Endpoints</h2>";

$apiTests = [
    'api.php?action=status' => 'ตรวจสอบสถานะระบบ',
    'api.php?action=points' => 'ดึงข้อมูล Points',
    'api.php?action=students' => 'ดึงข้อมูล Students'
];

foreach ($apiTests as $endpoint => $description) {
    echo "<p class='info'>🔗 <a href='$endpoint' target='_blank' style='color:#60a5fa'>$endpoint</a> - $description</p>";
}

echo "</div>";

// ===========================
// 5. คำแนะนำ
// ===========================
echo "<div class='box'>";
echo "<h2>5️⃣ คำแนะนำการแก้ไข</h2>";
echo "<ol style='line-height:2'>";
echo "<li>ถ้าใช้ <strong>Database Mode</strong>: รัน SQL Script ด้านบนเพื่อสร้างตาราง</li>";
echo "<li>ถ้าใช้ <strong>JSON Mode</strong>: ตรวจสอบให้แน่ใจว่ามีโฟลเดอร์ <code>/data</code> และไฟล์ JSON</li>";
echo "<li>ตรวจสอบ <strong>db.php</strong> ให้แน่ใจว่า:";
echo "<ul><li>Database credentials ถูกต้อง</li>";
echo "<li>ตั้งค่า USE_JSON_MODE ถูกต้อง</li></ul></li>";
echo "<li>คลิกลิงก์ API ด้านบนเพื่อทดสอบว่าข้อมูลโหลดได้หรือไม่</li>";
echo "</ol>";
echo "</div>";

echo "<div class='box' style='background:#10b981;color:#000'>";
echo "<h2>✅ ขั้นตอนต่อไป</h2>";
echo "<p>1. รัน SQL Script ใน pgAdmin หรือ psql</p>";
echo "<p>2. Refresh หน้านี้อีกครั้ง</p>";
echo "<p>3. คลิกทดสอบ API Endpoints</p>";
echo "<p>4. เปิดแอปพลิเคชันหลักของคุณ</p>";
echo "</div>";

echo "</body></html>";
?>