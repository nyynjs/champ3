<?php
// test_api.php - Plik do testowania API z poprawnymi danymi połączenia

// Włącz config
require_once 'config.php';

// Włącz wyświetlanie błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test API Salonu Fryzjerskiego</h2>";

// Test 1: Połączenie z bazą danych
echo "<h3>1. Test połączenia z bazą danych</h3>";
try {
    $db = new Database();
    $connectionInfo = $db->getConnectionInfo();
    
    echo "Próba połączenia z:<br>";
    echo "- Host: <strong>{$connectionInfo['host']}</strong><br>";
    echo "- Baza: <strong>{$connectionInfo['database']}</strong><br>";
    echo "- Użytkownik: <strong>{$connectionInfo['username']}</strong><br><br>";
    
    $pdo = $db->connect();
    echo "✅ Połączenie z bazą danych: <strong>OK</strong><br>";
    
    // Test podstawowego zapytania
    $stmt = $pdo->query("SELECT NOW() as current_time, DATABASE() as database_name");
    $result = $stmt->fetch();
    echo "- Aktualna data/czas: <strong>{$result['current_time']}</strong><br>";
    echo "- Nazwa bazy danych: <strong>{$result['database_name']}</strong><br>";
    
} catch (Exception $e) {
    echo "❌ Błąd połączenia z bazą danych: " . $e->getMessage() . "<br>";
    echo "<p><strong>Możliwe przyczyny:</strong></p>";
    echo "<ul>";
    echo "<li>Serwer bazy danych jest niedostępny</li>";
    echo "<li>Błędne dane logowania</li>";
    echo "<li>Baza danych nie istnieje</li>";
    echo "<li>Problemy z siecią</li>";
    echo "</ul>";
    
    echo "<h4>Spróbuj zainicjalizować bazę danych:</h4>";
    echo "<p><a href='api.php?action=init_database' target='_blank' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔧 Inicjalizuj bazę danych</a></p>";
    
    // Nie kończymy tutaj - pokażemy inne testy
}

// Test 2: Sprawdzenie tabel
echo "<h3>2. Test struktury bazy danych</h3>";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Znalezione tabele: <strong>" . count($tables) . "</strong><br>";
        
        if (in_array('pracownicy', $tables)) {
            echo "✅ Tabela 'pracownicy': <strong>istnieje</strong><br>";
            
            // Sprawdź strukturę tabeli pracownicy
            $stmt = $pdo->query("DESCRIBE pracownicy");
            $columns = $stmt->fetchAll();
            echo "&nbsp;&nbsp;Kolumny: ";
            foreach ($columns as $col) {
                echo $col['Field'] . " ";
            }
            echo "<br>";
            
        } else {
            echo "❌ Tabela 'pracownicy': <strong>BRAK</strong><br>";
        }
        
        if (in_array('wizyty', $tables)) {
            echo "✅ Tabela 'wizyty': <strong>istnieje</strong><br>";
            
            // Sprawdź strukturę tabeli wizyty
            $stmt = $pdo->query("DESCRIBE wizyty");
            $columns = $stmt->fetchAll();
            echo "&nbsp;&nbsp;Kolumny: ";
            foreach ($columns as $col) {
                echo $col['Field'] . " ";
            }
            echo "<br>";
            
        } else {
            echo "❌ Tabela 'wizyty': <strong>BRAK</strong><br>";
        }
        
        if (count($tables) == 0 || !in_array('pracownicy', $tables) || !in_array('wizyty', $tables)) {
            echo "<br><p><strong>⚠️ Brakuje tabel - zainicjalizuj bazę danych:</strong></p>";
            echo "<p><a href='#' onclick='initDatabase()' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; cursor: pointer;'>🔧 Utwórz tabele</a></p>";
        }
        
    } catch (Exception $e) {
        echo "❌ Błąd sprawdzania tabel: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⏭️ Pominięto - brak połączenia z bazą danych<br>";
}

// Test 3: Sprawdzenie danych pracowników
echo "<h3>3. Test danych pracowników</h3>";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM pracownicy ORDER BY imie");
        $pracownicy = $stmt->fetchAll();
        
        echo "Liczba pracowników: <strong>" . count($pracownicy) . "</strong><br>";
        if (count($pracownicy) > 0) {
            foreach ($pracownicy as $pracownik) {
                echo "- ID: {$pracownik['id']}, Imię: {$pracownik['imie']}, Aktywny: " . 
                     ($pracownik['aktywny'] ? 'TAK' : 'NIE') . "<br>";
            }
        } else {
            echo "⚠️ Brak pracowników w bazie danych<br>";
        }
    } catch (Exception $e) {
        echo "❌ Błąd ładowania pracowników: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⏭️ Pominięto - brak połączenia z bazą danych<br>";
}

// Test 4: Sprawdzenie wizyt
echo "<h3>4. Test danych wizyt</h3>";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM wizyty");
        $count = $stmt->fetch();
        echo "Liczba wizyt w bazie: <strong>" . $count['total'] . "</strong><br>";
        
        if ($count['total'] > 0) {
            $stmt = $pdo->query("
                SELECT w.*, p.imie as pracownik_imie 
                FROM wizyty w 
                JOIN pracownicy p ON w.pracownik_id = p.id 
                ORDER BY w.data_wizyty DESC, w.godzina_rozpoczecia DESC 
                LIMIT 3
            ");
            $wizyty = $stmt->fetchAll();
            
            echo "Ostatnie 3 wizyty:<br>";
            foreach ($wizyty as $wizyta) {
                echo "- {$wizyta['data_wizyty']} {$wizyta['godzina_rozpoczecia']}-{$wizyta['godzina_zakonczenia']}: ";
                echo "{$wizyta['imie_zwierzaka']} ({$wizyta['imie_klienta']}) - {$wizyta['pracownik_imie']}<br>";
            }
        }
    } catch (Exception $e) {
        echo "❌ Błąd ładowania wizyt: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⏭️ Pominięto - brak połączenia z bazą danych<br>";
}

// Test 5: Test API endpoints
echo "<h3>5. Test API endpoints</h3>";

// Test connection endpoint
echo "<h4>Test GET /api.php?action=test</h4>";
echo "<div id='test-connection'>Testowanie...</div>";

// Test GET pracownicy
echo "<h4>Test GET /api.php?action=pracownicy</h4>";
echo "<div id='test-employees'>Testowanie...</div>";

// Test GET wizyty
echo "<h4>Test GET /api.php?action=wizyty</h4>";
echo "<div id='test-appointments'>Testowanie...</div>";

// Test 6: Sprawdzenie konfiguracji PHP
echo "<h3>6. Konfiguracja PHP</h3>";
echo "Wersja PHP: <strong>" . phpversion() . "</strong><br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ <strong>dostępne</strong>' : '❌ <strong>BRAK</strong>') . "<br>";
echo "JSON: " . (extension_loaded('json') ? '✅ <strong>dostępne</strong>' : '❌ <strong>BRAK</strong>') . "<br>";
echo "cURL: " . (extension_loaded('curl') ? '✅ <strong>dostępne</strong>' : '❌ <strong>BRAK</strong>') . "<br>";

echo "<h3>7. Szybkie akcje</h3>";
echo "<p>";
echo "<button onclick='initDatabase()' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;'>🔧 Inicjalizuj bazę danych</button>";
echo "<button onclick='testAllEndpoints()' style='background: #007cba; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;'>🧪 Testuj wszystkie API</button>";
echo "<button onclick='window.open(\"index.html\", \"_blank\")' style='background: #6f42c1; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;'>🚀 Otwórz kalendarz</button>";
echo "</p>";

?>

<script>
async function testEndpoint(url, elementId, description) {
    const element = document.getElementById(elementId);
    element.innerHTML = `<span style="color: #007cba;">⏳ Testowanie ${description}...</span>`;
    
    try {
        const response = await fetch(url);
        const contentType = response.headers.get('content-type');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        if (contentType && contentType.includes('application/json')) {
            const data = await response.json();
            
            if (Array.isArray(data)) {
                element.innerHTML = `✅ <strong>OK</strong> - Zwrócono ${data.length} rekordów`;
            } else if (data.success) {
                element.innerHTML = `✅ <strong>OK</strong> - ${data.message || 'Sukces'}`;
            } else {
                element.innerHTML = `✅ <strong>OK</strong> - ${JSON.stringify(data)}`;
            }
        } else {
            const text = await response.text();
            element.innerHTML = `⚠️ <strong>Nieprawidłowa odpowiedź</strong> - Oczekiwano JSON, otrzymano: ${text.substring(0, 100)}...`;
        }
    } catch (error) {
        element.innerHTML = `❌ <strong>BŁĄD</strong> - ${error.message}`;
    }
}

async function initDatabase() {
    try {
        const response = await fetch('api.php?action=init_database', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Błąd inicjalizacji: ' + (data.error || 'Nieznany błąd'));
        }
    } catch (error) {
        alert('❌ Błąd: ' + error.message);
    }
}

async function testAllEndpoints() {
    await testEndpoint('api.php?action=test', 'test-connection', 'połączenia');
    await new Promise(resolve => setTimeout(resolve, 500));
    
    await testEndpoint('api.php?action=pracownicy', 'test-employees', 'pracowników');
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const startDate = new Date().toISOString().split('T')[0];
    const endDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    await testEndpoint(`api.php?action=wizyty&start=${startDate}&end=${endDate}`, 'test-appointments', 'wizyt');
}

// Uruchom testy automatycznie
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        testAllEndpoints();
    }, 1000);
});
</script>

<style>
body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
h3 { color: #34495e; margin-top: 30px; }
h4 { color: #7f8c8d; margin-top: 20px; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
button { transition: all 0.3s ease; }
button:hover { transform: translateY(-1px); opacity: 0.9; }
</style>