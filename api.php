<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

// ❌ ลบบรรทัดเหล่านี้ออก (เพราะมีใน db.php แล้ว)
// define('USE_JSON_MODE', true);
// define('JSON_POINTS_FILE', __DIR__ . '/data/points.json');
// define('JSON_STUDENTS_FILE', __DIR__ . '/data/students.json');

// ✅ เรียกใช้ db.php เพื่อดึง configuration
require_once 'db.php';

// ฟังก์ชันช่วยเหลือสำหรับ JSON (ใช้จาก db.php ที่มีอยู่แล้ว)
// readJSON(), writeJSON(), getNextId() ถูก define ใน db.php แล้ว

// รับ action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // ตรวจสอบว่าใช้โหมดไหน
    if (USE_JSON_MODE) {
        // ===== JSON MODE =====
        handleJsonMode($action);
    } else {
        // ===== DATABASE MODE =====
        handleDatabaseMode($action, $pdo);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// ===============================
// JSON MODE HANDLER
// ===============================
function handleJsonMode($action) {
    switch ($action) {
        // POINTS
        case 'points':
        case 'get_points':
            $points = readJSON(JSON_POINTS_FILE);
            echo json_encode($points, JSON_UNESCAPED_UNICODE);
            break;

        case 'add_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["name"]) || !isset($data["lat"]) || !isset($data["lon"])) {
                throw new Exception("Missing required fields");
            }
            $points = readJSON(JSON_POINTS_FILE);
            $newId = getNextId($points);
            $newPoint = [
                "id" => $newId,
                "name" => $data["name"],
                "description" => $data["description"] ?? "",
                "lat" => $data["lat"],
                "lon" => $data["lon"],
                "created_at" => date('Y-m-d H:i:s')
            ];
            $points[] = $newPoint;
            writeJSON(JSON_POINTS_FILE, $points);
            echo json_encode(["status" => "success", "id" => $newId, "data" => $newPoint], JSON_UNESCAPED_UNICODE);
            break;

        case 'update_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing point ID");
            $points = readJSON(JSON_POINTS_FILE);
            $updated = false;
            foreach ($points as $key => $point) {
                if ($point['id'] == $data["id"]) {
                    $points[$key] = array_merge($points[$key], [
                        'name' => $data["name"],
                        'description' => $data["description"],
                        'lat' => $data["lat"],
                        'lon' => $data["lon"],
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                writeJSON(JSON_POINTS_FILE, $points);
                echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("Point not found");
            }
            break;

        case 'delete_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing point ID");
            $points = readJSON(JSON_POINTS_FILE);
            $points = array_values(array_filter($points, fn($p) => $p['id'] != $data["id"]));
            writeJSON(JSON_POINTS_FILE, $points);
            echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            break;

        // STUDENTS
        case 'students':
        case 'get_students':
            $students = readJSON(JSON_STUDENTS_FILE);
            echo json_encode($students, JSON_UNESCAPED_UNICODE);
            break;

        case 'add_student':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["s_id"]) || !isset($data["s_name"])) {
                throw new Exception("Missing required fields");
            }
            $students = readJSON(JSON_STUDENTS_FILE);
            $newId = getNextId($students);
            $newStudent = [
                "id" => $newId,
                "s_id" => $data["s_id"],
                "s_name" => $data["s_name"],
                "หลักสูตร" => $data["หลักสูตร"] ?? "",
                "ภาควิชา" => $data["ภาควิชา"] ?? "",
                "คณะ" => $data["คณะ"] ?? "",
                "จังหวัด" => $data["จังหวัด"] ?? "",
                "lat" => $data["lat"] ?? "",
                "long" => $data["long"] ?? ""
            ];
            $students[] = $newStudent;
            writeJSON(JSON_STUDENTS_FILE, $students);
            echo json_encode(["status" => "success", "id" => $newId, "data" => $newStudent], JSON_UNESCAPED_UNICODE);
            break;

        case 'update_student':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing student ID");
            $students = readJSON(JSON_STUDENTS_FILE);
            $updated = false;
            foreach ($students as $key => $student) {
                if ($student['id'] == $data["id"]) {
                    $students[$key] = array_merge($students[$key], [
                        's_id' => $data["s_id"],
                        's_name' => $data["s_name"],
                        'หลักสูตร' => $data["หลักสูตร"],
                        'ภาควิชา' => $data["ภาควิชา"],
                        'คณะ' => $data["คณะ"],
                        'จังหวัด' => $data["จังหวัด"],
                        'lat' => $data["lat"],
                        'long' => $data["long"]
                    ]);
                    $updated = true;
                    break;
                }
            }
            if ($updated) {
                writeJSON(JSON_STUDENTS_FILE, $students);
                echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("Student not found");
            }
            break;

        case 'delete_student':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing student ID");
            $students = readJSON(JSON_STUDENTS_FILE);
            $students = array_values(array_filter($students, fn($s) => $s['id'] != $data["id"]));
            writeJSON(JSON_STUDENTS_FILE, $students);
            echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            break;

        case 'status':
        case 'info':
            echo json_encode([
                "mode" => "JSON File System",
                "points_count" => count(readJSON(JSON_POINTS_FILE)),
                "students_count" => count(readJSON(JSON_STUDENTS_FILE))
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Invalid action: " . $action);
    }
}

// ===============================
// DATABASE MODE HANDLER
// ===============================
function handleDatabaseMode($action, $pdo) {
    if (!$pdo) {
        throw new Exception("Database connection not available");
    }

    switch ($action) {
        // POINTS
        case 'points':
        case 'get_points':
            $stmt = $pdo->query("SELECT id, name, description, 
                                 ST_Y(geom::geometry) as lat, 
                                 ST_X(geom::geometry) as lon,
                                 created_at, updated_at
                                 FROM points ORDER BY id");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
            break;

        case 'add_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["name"]) || !isset($data["lat"]) || !isset($data["lon"])) {
                throw new Exception("Missing required fields");
            }
            $sql = "INSERT INTO points (name, description, geom) 
                    VALUES (:name, :description, ST_SetSRID(ST_MakePoint(:lon, :lat), 4326))
                    RETURNING id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $data["name"],
                ":description" => $data["description"] ?? "",
                ":lat" => $data["lat"],
                ":lon" => $data["lon"]
            ]);
            $newId = $stmt->fetchColumn();
            echo json_encode(["status" => "success", "id" => $newId], JSON_UNESCAPED_UNICODE);
            break;

        case 'update_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing point ID");
            $sql = "UPDATE points 
                    SET name = :name, 
                        description = :description, 
                        geom = ST_SetSRID(ST_MakePoint(:lon, :lat), 4326),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":name" => $data["name"],
                ":description" => $data["description"],
                ":lat" => $data["lat"],
                ":lon" => $data["lon"],
                ":id" => $data["id"]
            ]);
            echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            break;

        case 'delete_point':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing point ID");
            $stmt = $pdo->prepare("DELETE FROM points WHERE id = :id");
            $stmt->execute([":id" => $data["id"]]);
            echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            break;

        // STUDENTS - GET
case 'students':
    case 'get_students':
        $stmt = $pdo->query("SELECT id, s_id, s_name, 
                             program, department, faculty, province,
                             lat, long
                             FROM students ORDER BY id");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        break;
    
    // STUDENTS - ADD
    case 'add_student':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data["s_id"]) || !isset($data["s_name"])) {
            throw new Exception("Missing required fields");
        }
        $sql = "INSERT INTO students (s_id, s_name, program, department, faculty, province, lat, long) 
                VALUES (:s_id, :s_name, :program, :department, :faculty, :province, :lat, :long)
                RETURNING id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":s_id" => $data["s_id"],
            ":s_name" => $data["s_name"],
            ":program" => $data["program"] ?? $data["หลักสูตร"] ?? "",
            ":department" => $data["department"] ?? $data["ภาควิชา"] ?? "",
            ":faculty" => $data["faculty"] ?? $data["คณะ"] ?? "",
            ":province" => $data["province"] ?? $data["จังหวัด"] ?? "",
            ":lat" => $data["lat"] ?? "",
            ":long" => $data["long"] ?? ""
        ]);
        $newId = $stmt->fetchColumn();
        echo json_encode(["status" => "success", "id" => $newId], JSON_UNESCAPED_UNICODE);
        break;
    
    // STUDENTS - UPDATE
    case 'update_student':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!isset($data["id"])) throw new Exception("Missing student ID");
        $sql = "UPDATE students 
                SET s_id = :s_id, s_name = :s_name,
                    program = :program, department = :department,
                    faculty = :faculty, province = :province,
                    lat = :lat, long = :long
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":s_id" => $data["s_id"],
            ":s_name" => $data["s_name"],
            ":program" => $data["program"] ?? $data["หลักสูตร"],
            ":department" => $data["department"] ?? $data["ภาควิชา"],
            ":faculty" => $data["faculty"] ?? $data["คณะ"],
            ":province" => $data["province"] ?? $data["จังหวัด"],
            ":lat" => $data["lat"],
            ":long" => $data["long"],
            ":id" => $data["id"]
        ]);
        echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
        break;

        case 'delete_student':
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data["id"])) throw new Exception("Missing student ID");
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
            $stmt->execute([":id" => $data["id"]]);
            echo json_encode(["status" => "success"], JSON_UNESCAPED_UNICODE);
            break;

        case 'status':
        case 'info':
            $pointsCount = $pdo->query("SELECT COUNT(*) FROM points")->fetchColumn();
            $studentsCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            echo json_encode([
                "mode" => "PostgreSQL Database",
                "points_count" => $pointsCount,
                "students_count" => $studentsCount
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            throw new Exception("Invalid action: " . $action);
    }
}
?>