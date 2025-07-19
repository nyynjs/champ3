<?php
// Włącz config
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$db = new Database();
$pdo = $db->connect();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Dodaj logowanie dla debugowania
error_log("API Call: Method=$method, Action=$action, GET=" . print_r($_GET, true));

try {
    switch ($method) {
        case 'GET':
            if ($action === 'wizyty') {
                getWizyty($pdo);
            } elseif ($action === 'pracownicy') {
                getPracownicy($pdo);
            } elseif ($action === 'sprawdz_dostepnosc') {
                sprawdzDostepnosc($pdo);
            } elseif ($action === 'test') {
                testConnection($pdo);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action: ' . $action]);
            }
            break;
            
        case 'POST':
            if ($action === 'dodaj_wizyte') {
                dodajWizyte($pdo);
            } elseif ($action === 'init_database') {
                initDatabase($pdo);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown POST action: ' . $action]);
            }
            break;
            
        case 'DELETE':
            if ($action === 'usun_wizyte') {
                usunWizyte($pdo);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Unknown DELETE action: ' . $action]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed: ' . $method]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

function testConnection($pdo) {
    try {
        $stmt = $pdo->query("SELECT 'Connection OK' as status, NOW() as time");
        $result = $stmt->fetch();
        echo json_encode(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        throw new Exception('Connection test failed: ' . $e->getMessage());
    }
}

function initDatabase($pdo) {
    try {
        // Tworzenie tabeli pracownicy
        $pdo->exec("CREATE TABLE IF NOT EXISTS pracownicy (
            id INT AUTO_INCREMENT PRIMARY KEY,
            imie VARCHAR(100) NOT NULL,
            telefon VARCHAR(20),
            email VARCHAR(100),
            aktywny TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Tworzenie tabeli wizyt
        $pdo->exec("CREATE TABLE IF NOT EXISTS wizyty (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_wizyty DATE NOT NULL,
            godzina_rozpoczecia TIME NOT NULL,
            godzina_zakonczenia TIME NOT NULL,
            imie_klienta VARCHAR(100) NOT NULL,
            imie_zwierzaka VARCHAR(100) NOT NULL,
            telefon VARCHAR(20) NOT NULL,
            nazwa_uslugi VARCHAR(200) NOT NULL,
            pracownik_id INT NOT NULL,
            notatki TEXT,
            status VARCHAR(50) DEFAULT 'scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Dodaj przykładowych pracowników
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM pracownicy");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $pdo->exec("INSERT INTO pracownicy (imie, telefon, email, aktywny) VALUES 
                ('Anna Kowalska', '123-456-789', 'anna@salon.pl', 1),
                ('Maria Nowak', '123-456-790', 'maria@salon.pl', 1),
                ('Piotr Wiśniewski', '123-456-791', 'piotr@salon.pl', 1),
                ('Katarzyna Wójcik', '123-456-792', 'katarzyna@salon.pl', 1)");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Baza danych została zainicjalizowana pomyślnie',
            'employees_count' => $pdo->query("SELECT COUNT(*) FROM pracownicy")->fetchColumn()
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Database initialization failed: ' . $e->getMessage());
    }
}

function getWizyty($pdo) {
    try {
        $start_date = $_GET['start'] ?? date('Y-m-d');
        $end_date = $_GET['end'] ?? date('Y-m-d', strtotime('+7 days'));
        
        error_log("Getting appointments from $start_date to $end_date");
        
        $stmt = $pdo->prepare("
            SELECT w.*, p.imie as pracownik_imie 
            FROM wizyty w 
            JOIN pracownicy p ON w.pracownik_id = p.id 
            WHERE w.data_wizyty BETWEEN ? AND ? 
            ORDER BY w.data_wizyty, w.godzina_rozpoczecia
        ");
        $stmt->execute([$start_date, $end_date]);
        
        $results = $stmt->fetchAll();
        error_log("Found " . count($results) . " appointments");
        
        echo json_encode($results);
    } catch (Exception $e) {
        error_log("Error in getWizyty: " . $e->getMessage());
        throw $e;
    }
}

function getPracownicy($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pracownicy WHERE aktywny = 1 ORDER BY imie");
        $stmt->execute();
        
        $results = $stmt->fetchAll();
        error_log("Found " . count($results) . " employees");
        
        echo json_encode($results);
    } catch (Exception $e) {
        error_log("Error in getPracownicy: " . $e->getMessage());
        throw $e;
    }
}

function sprawdzDostepnosc($pdo) {
    $data = $_GET['data'];
    $godzina_start = $_GET['godzina_start'];
    $godzina_end = $_GET['godzina_end'];
    $pracownik_id = $_GET['pracownik_id'];
    $wizyta_id = $_GET['wizyta_id'] ?? null;
    
    $sql = "
        SELECT COUNT(*) as count 
        FROM wizyty 
        WHERE data_wizyty = ? 
        AND pracownik_id = ? 
        AND (
            (godzina_rozpoczecia < ? AND godzina_zakonczenia > ?) OR
            (godzina_rozpoczecia < ? AND godzina_zakonczenia > ?) OR
            (godzina_rozpoczecia >= ? AND godzina_zakonczenia <= ?)
        )
    ";
    
    $params = [$data, $pracownik_id, $godzina_end, $godzina_start, $godzina_start, $godzina_start, $godzina_start, $godzina_end];
    
    if ($wizyta_id) {
        $sql .= " AND id != ?";
        $params[] = $wizyta_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $result = $stmt->fetch();
    echo json_encode(['dostepny' => $result['count'] == 0]);
}

function dodajWizyte($pdo) {
    $input = file_get_contents('php://input');
    error_log("Raw input: " . $input);
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Walidacja danych
    $required = ['data_wizyty', 'godzina_rozpoczecia', 'godzina_zakonczenia', 'imie_klienta', 'imie_zwierzaka', 'telefon', 'nazwa_uslugi', 'pracownik_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Pole $field jest wymagane");
        }
    }
    
    // Walidacja godzin
    if ($data['godzina_rozpoczecia'] >= $data['godzina_zakonczenia']) {
        throw new Exception('Godzina rozpoczęcia musi być wcześniejsza niż godzina zakończenia');
    }
    
    // Sprawdź dostępność pracownika
    $checkSql = "
        SELECT COUNT(*) as count 
        FROM wizyty 
        WHERE data_wizyty = ? 
        AND pracownik_id = ? 
        AND (
            (godzina_rozpoczecia < ? AND godzina_zakonczenia > ?) OR
            (godzina_rozpoczecia < ? AND godzina_zakonczenia > ?) OR
            (godzina_rozpoczecia >= ? AND godzina_zakonczenia <= ?)
        )
    ";
    
    $checkParams = [
        $data['data_wizyty'], 
        $data['pracownik_id'], 
        $data['godzina_zakonczenia'], 
        $data['godzina_rozpoczecia'],
        $data['godzina_rozpoczecia'], 
        $data['godzina_rozpoczecia'], 
        $data['godzina_rozpoczecia'], 
        $data['godzina_zakonczenia']
    ];
    
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute($checkParams);
    
    if ($stmt->fetch()['count'] > 0) {
        throw new Exception('Pracownik nie jest dostępny w wybranym terminie');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO wizyty (data_wizyty, godzina_rozpoczecia, godzina_zakonczenia, imie_klienta, imie_zwierzaka, telefon, nazwa_uslugi, pracownik_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['data_wizyty'],
        $data['godzina_rozpoczecia'],
        $data['godzina_zakonczenia'],
        $data['imie_klienta'],
        $data['imie_zwierzaka'],
        $data['telefon'],
        $data['nazwa_uslugi'],
        $data['pracownik_id']
    ]);
    
    echo json_encode([
        'success' => true, 
        'id' => $pdo->lastInsertId(),
        'message' => 'Wizyta została dodana pomyślnie'
    ]);
}

function usunWizyte($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID wizyty jest wymagane');
    }
    
    $stmt = $pdo->prepare("DELETE FROM wizyty WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Wizyta została usunięta']);
    } else {
        throw new Exception('Nie znaleziono wizyty o podanym ID');
    }
}
?>