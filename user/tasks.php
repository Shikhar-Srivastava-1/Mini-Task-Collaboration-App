<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user_id = $stmt->fetchColumn();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$_SESSION['user_id'] = $user_id;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $where = [];
        $params = [];
        
        if (isset($_GET['status']) && in_array($_GET['status'], ['Pending', 'Completed'])) {
            $where[] = 't.status = ?';
            $params[] = $_GET['status'];
        }
        if (isset($_GET['priority']) && in_array($_GET['priority'], ['High', 'Medium', 'Low'])) {
            $where[] = 't.priority = ?';
            $params[] = $_GET['priority'];
        }
        if (isset($_GET['deadline'])) {
            if ($_GET['deadline'] == 'today') {
                $where[] = 't.deadline = CURDATE()';
            } elseif ($_GET['deadline'] == 'week') {
                $where[] = 't.deadline <= CURDATE() + INTERVAL 7 DAY';
            }
        }
        
        $sort_by = isset($_GET['sort_by']) && in_array($_GET['sort_by'], ['title', 'creator', 'deadline', 'priority']) ? $_GET['sort_by'] : 'deadline';
        $sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], ['asc', 'desc']) ? $_GET['sort_order'] : 'asc';
        
        $sort_field = match($sort_by) {
            'title' => 't.title',
            'creator' => 'u.name',
            'deadline' => 't.deadline',
            'priority' => 'FIELD(t.priority, "High", "Medium", "Low")',
            default => 't.deadline'
        };
        
        $sql = "SELECT t.id, t.user_id, t.title, t.deadline, t.priority, t.status, t.description, u.name AS creator_name 
                FROM tasks t JOIN users u ON t.user_id = u.id";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY t.status ASC, $sort_field $sort_order";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT id, title, deadline FROM tasks WHERE user_id = ? AND status = 'Pending' AND deadline <= CURDATE() + INTERVAL 2 DAY ORDER BY deadline ASC");
        $stmt->execute([$user_id]);
        $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['tasks' => $tasks, 'reminders' => $reminders]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['task_id']) && !isset($data['description'])) {
            $task_id = $data['task_id'];
            $title = trim($data['title']);
            $deadline = $data['deadline'];
            $priority = $data['priority'];
            $status = $data['status'];
            
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title is required']);
                exit;
            }
            if (!strtotime($deadline)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid deadline']);
                exit;
            }
            if (!in_array($priority, ['High', 'Medium', 'Low'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid priority']);
                exit;
            }
            if (!in_array($status, ['Pending', 'Completed'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            if ($stmt->fetchColumn() != $user_id) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, deadline = ?, priority = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $deadline, $priority, $status, $task_id]);
            
            echo json_encode(['message' => 'Task updated']);
        } elseif (isset($data['task_id']) && isset($data['description'])) {
            $task_id = $data['task_id'];
            $description = trim($data['description']);
            
            if ($description === '') {
                $description = null;
            }
            
            $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = ?");
            $stmt->execute([$task_id]);
            if ($stmt->fetchColumn() != $user_id) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE tasks SET description = ? WHERE id = ?");
            $stmt->execute([$description, $task_id]);
            
            echo json_encode(['message' => 'Description updated']);
        } else {
            $title = trim($data['title']);
            $deadline = $data['deadline'];
            $priority = $data['priority'];
            $description = isset($data['description']) ? trim($data['description']) : null;
            
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title is required']);
                exit;
            }
            if (!strtotime($deadline)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid deadline']);
                exit;
            }
            if (!in_array($priority, ['High', 'Medium', 'Low'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid priority']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, deadline, priority, status, description) VALUES (?, ?, ?, ?, 'Pending', ?)");
            $stmt->execute([$user_id, $title, $deadline, $priority, $description]);
            
            $task_id = $pdo->lastInsertId();
            echo json_encode(['message' => 'Task created', 'task_id' => $task_id]);
        }
        break;
        
    case 'DELETE':
        parse_str(file_get_contents('php://input'), $data);
        $task_id = $data['task_id'] ?? null;
        
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        if ($stmt->fetchColumn() != $user_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: You can only delete your own tasks']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        
        echo json_encode(['message' => 'Task deleted']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
