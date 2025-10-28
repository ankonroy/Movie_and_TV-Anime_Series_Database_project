<?php
// MovieTVDB.php - Complete Movie & TV Database Project
session_start();

/* ====== DATABASE CONFIGURATION ====== */
$DB_HOST = '127.0.0.1';
$DB_NAME = 'movie_tv_db';
$DB_USER = 'root';  // Change if needed
$DB_PASS = '';      // Change if needed

// Initialize database connection
$connected = false;
$errorMsg = null;
$pdo = null;
$initialized = false;

try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
    $pdo->exec("USE `$DB_NAME`");
    $connected = true;
    
    // Check if database is already initialized
    $initialized = checkIfInitialized($pdo);
    
    // Initialize database only if not already initialized
    if (!$initialized) {
        initializeDatabase($pdo);
        $initialized = true;
    }
    
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
}

/* ====== HELPER FUNCTIONS ====== */
function checkIfInitialized($pdo) {
    try {
        // Check if our main tables exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'movies'");
        $moviesExists = $stmt->fetch();
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'genres'");
        $genresExists = $stmt->fetch();
        
        return $moviesExists && $genresExists;
    } catch (PDOException $e) {
        return false;
    }
}

function initializeDatabase($pdo) {
    // Drop tables if they exist (in correct order due to foreign keys)
    $dropTables = [
        'reviews', 'movie_actors', 'tv_episodes', 'anime_episodes',
        'actors', 'directors', 'movies', 'tv_series', 'anime_series', 'genres'
    ];

    foreach ($dropTables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        } catch (PDOException $e) {
            // Ignore errors if tables don't exist
        }
    }

    // Create tables with better error handling
    $createTables = [
        "CREATE TABLE IF NOT EXISTS genres (
            genre_id INT AUTO_INCREMENT PRIMARY KEY,
            genre_name VARCHAR(50) NOT NULL UNIQUE,
            description TEXT
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS directors (
            director_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            nationality VARCHAR(50),
            birth_date DATE,
            UNIQUE(first_name, last_name)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS actors (
            actor_id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            gender ENUM('Male', 'Female', 'Other'),
            birth_date DATE,
            nationality VARCHAR(50)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS movies (
            movie_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            release_year YEAR,
            duration_minutes INT,
            rating DECIMAL(3,1),
            budget DECIMAL(15,2),
            director_id INT,
            genre_id INT,
            FOREIGN KEY (director_id) REFERENCES directors(director_id) ON DELETE SET NULL,
            FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS tv_series (
            series_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            start_year YEAR,
            end_year YEAR,
            seasons INT DEFAULT 1,
            rating DECIMAL(3,1),
            director_id INT,
            genre_id INT,
            FOREIGN KEY (director_id) REFERENCES directors(director_id) ON DELETE SET NULL,
            FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS anime_series (
            anime_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            original_title VARCHAR(200),
            release_year YEAR,
            episodes INT,
            rating DECIMAL(3,1),
            studio VARCHAR(100),
            genre_id INT,
            FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS movie_actors (
            movie_id INT,
            actor_id INT,
            role_name VARCHAR(100),
            is_lead_role BOOLEAN DEFAULT FALSE,
            PRIMARY KEY (movie_id, actor_id),
            FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
            FOREIGN KEY (actor_id) REFERENCES actors(actor_id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS tv_episodes (
            episode_id INT AUTO_INCREMENT PRIMARY KEY,
            series_id INT,
            season_number INT NOT NULL,
            episode_number INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            duration_minutes INT,
            air_date DATE,
            rating DECIMAL(3,1),
            FOREIGN KEY (series_id) REFERENCES tv_series(series_id) ON DELETE CASCADE,
            UNIQUE(series_id, season_number, episode_number)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS anime_episodes (
            episode_id INT AUTO_INCREMENT PRIMARY KEY,
            anime_id INT,
            episode_number INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            duration_minutes INT,
            air_date DATE,
            rating DECIMAL(3,1),
            FOREIGN KEY (anime_id) REFERENCES anime_series(anime_id) ON DELETE CASCADE,
            UNIQUE(anime_id, episode_number)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS reviews (
            review_id INT AUTO_INCREMENT PRIMARY KEY,
            movie_id INT NULL,
            tv_series_id INT NULL,
            anime_id INT NULL,
            reviewer_name VARCHAR(100),
            rating INT,
            review_text TEXT,
            review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
            FOREIGN KEY (tv_series_id) REFERENCES tv_series(series_id) ON DELETE CASCADE,
            FOREIGN KEY (anime_id) REFERENCES anime_series(anime_id) ON DELETE CASCADE,
            CONSTRAINT chk_review_content CHECK (
                (movie_id IS NOT NULL AND tv_series_id IS NULL AND anime_id IS NULL) OR
                (movie_id IS NULL AND tv_series_id IS NOT NULL AND anime_id IS NULL) OR
                (movie_id IS NULL AND tv_series_id IS NULL AND anime_id IS NOT NULL)
            )
        ) ENGINE=InnoDB"
    ];

    foreach ($createTables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Table creation error: " . $e->getMessage());
            // Continue with other tables even if one fails
        }
    }

    // Insert sample data only if tables are empty
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM genres");
        $genreCount = $stmt->fetch()['count'];
        
        if ($genreCount == 0) {
            insertSampleData($pdo);
        }
    } catch (PDOException $e) {
        // Tables might not exist yet, try inserting anyway
        insertSampleData($pdo);
    }
}

function insertSampleData($pdo) {
    $sampleData = [
        // Genres
        "INSERT IGNORE INTO genres (genre_name, description) VALUES 
        ('Action', 'High-energy physical stunts and chases'),
        ('Drama', 'Emotional character development and serious themes'),
        ('Comedy', 'Humorous and entertaining stories'),
        ('Sci-Fi', 'Futuristic technology and space exploration'),
        ('Fantasy', 'Magical elements and mythical creatures'),
        ('Thriller', 'Suspenseful and exciting stories'),
        ('Romance', 'Love stories and relationships'),
        ('Horror', 'Scary and frightening content')",
        
        // Directors
        "INSERT IGNORE INTO directors (first_name, last_name, nationality, birth_date) VALUES 
        ('Christopher', 'Nolan', 'British', '1970-07-30'),
        ('Quentin', 'Tarantino', 'American', '1963-03-27'),
        ('Hayao', 'Miyazaki', 'Japanese', '1941-01-05'),
        ('David', 'Fincher', 'American', '1962-08-28'),
        ('Steven', 'Spielberg', 'American', '1946-12-18'),
        ('James', 'Cameron', 'Canadian', '1954-08-16')",
        
        // Actors
        "INSERT IGNORE INTO actors (first_name, last_name, gender, birth_date, nationality) VALUES 
        ('Leonardo', 'DiCaprio', 'Male', '1974-11-11', 'American'),
        ('Tom', 'Hanks', 'Male', '1956-07-09', 'American'),
        ('Meryl', 'Streep', 'Female', '1949-06-22', 'American'),
        ('Robert', 'Downey Jr.', 'Male', '1965-04-04', 'American'),
        ('Scarlett', 'Johansson', 'Female', '1984-11-22', 'American'),
        ('Brad', 'Pitt', 'Male', '1963-12-18', 'American'),
        ('Jennifer', 'Lawrence', 'Female', '1990-08-15', 'American')",
        
        // Movies
        "INSERT IGNORE INTO movies (title, release_year, duration_minutes, rating, budget, director_id, genre_id) VALUES 
        ('Inception', 2010, 148, 8.8, 160000000, 1, 4),
        ('Pulp Fiction', 1994, 154, 8.9, 8000000, 2, 1),
        ('The Social Network', 2010, 120, 7.7, 40000000, 4, 2),
        ('Spirited Away', 2001, 125, 8.6, 19000000, 3, 5),
        ('The Dark Knight', 2008, 152, 9.0, 185000000, 1, 1),
        ('Forrest Gump', 1994, 142, 8.8, 55000000, 5, 2)",
        
        // TV Series
        "INSERT IGNORE INTO tv_series (title, start_year, end_year, seasons, rating, director_id, genre_id) VALUES 
        ('Breaking Bad', 2008, 2013, 5, 9.5, NULL, 2),
        ('Stranger Things', 2016, NULL, 4, 8.7, NULL, 4),
        ('The Crown', 2016, 2023, 6, 8.6, NULL, 2),
        ('Game of Thrones', 2011, 2019, 8, 9.3, NULL, 5)",
        
        // Anime Series
        "INSERT IGNORE INTO anime_series (title, original_title, release_year, episodes, rating, studio, genre_id) VALUES 
        ('Attack on Titan', '進撃の巨人', 2013, 75, 9.0, 'Wit Studio', 1),
        ('Death Note', 'デスノート', 2006, 37, 8.6, 'Madhouse', 6),
        ('My Hero Academia', '僕のヒーローアカデミア', 2016, 113, 8.4, 'Bones', 1),
        ('Demon Slayer', '鬼滅の刃', 2019, 44, 8.7, 'Ufotable', 1)",
        
        // Movie Actors
        "INSERT IGNORE INTO movie_actors (movie_id, actor_id, role_name, is_lead_role) VALUES 
        (1, 1, 'Cobb', TRUE),
        (2, 1, 'Jimmie Dimmick', FALSE),
        (5, 1, 'Cobb', TRUE),
        (6, 2, 'Forrest Gump', TRUE),
        (3, 2, 'Paul Avery', FALSE)",
        
        // TV Episodes
        "INSERT IGNORE INTO tv_episodes (series_id, season_number, episode_number, title, duration_minutes, air_date, rating) VALUES 
        (1, 1, 1, 'Pilot', 58, '2008-01-20', 8.9),
        (2, 1, 1, 'Chapter One: The Vanishing of Will Byers', 47, '2016-07-15', 8.6),
        (4, 1, 1, 'Winter Is Coming', 62, '2011-04-17', 9.1)",
        
        // Anime Episodes
        "INSERT IGNORE INTO anime_episodes (anime_id, episode_number, title, duration_minutes, air_date, rating) VALUES 
        (1, 1, 'To You, in 2000 Years: The Fall of Shiganshina, Part 1', 24, '2013-04-07', 8.8),
        (1, 2, 'That Day: The Fall of Shiganshina, Part 2', 24, '2013-04-14', 8.7),
        (2, 1, 'Rebirth', 24, '2006-10-04', 9.0)",
        
        // Reviews - FIXED with proper foreign keys
        "INSERT IGNORE INTO reviews (movie_id, tv_series_id, anime_id, reviewer_name, rating, review_text) VALUES 
        (1, NULL, NULL, 'MovieBuff42', 5, 'Mind-bending masterpiece with incredible visuals'),
        (NULL, 1, NULL, 'TVFanatic', 5, 'Best character development in television history'),
        (NULL, NULL, 1, 'AnimeLover', 4, 'Incredible animation and intense storyline'),
        (5, NULL, NULL, 'CinemaExpert', 5, 'Heath Ledger\'s performance as Joker is legendary'),
        (NULL, NULL, 2, 'MangaReader', 4, 'Brilliant psychological thriller with amazing plot twists')"
    ];

    foreach ($sampleData as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Data insertion error: " . $e->getMessage());
            // Continue with other inserts even if one fails
        }
    }
}

function h($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

function is_select_like($sql) {
    return preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $sql) === 1;
}

function list_tables($pdo) {
    try {
        $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

function run_single_statement($pdo, $sql) {
    $sql = trim($sql);
    $start = microtime(true);

    if (is_select_like($sql)) {
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        $time = (microtime(true) - $start) * 1000.0;
        return ['type'=>'resultset', 'rows'=>$rows, 'rowcount'=>count($rows), 'time_ms'=>$time];
    } else {
        $count = $pdo->exec($sql);
        $time = (microtime(true) - $start) * 1000.0;
        return ['type'=>'status', 'rowcount'=>$count, 'time_ms'=>$time];
    }
}

/* ====== HANDLE SQL EXECUTION ====== */
$output = null;
$runError = null;
$input_sql = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $connected && isset($_POST['sql'])) {
    $input_sql = $_POST['sql'] ?? '';
    if (strlen(trim($input_sql)) > 0) {
        try {
            $output = run_single_statement($pdo, $input_sql);
            // Query history (keep unique last 10)
            $_SESSION['history'] = $_SESSION['history'] ?? [];
            array_unshift($_SESSION['history'], trim($input_sql));
            $_SESSION['history'] = array_slice(array_unique($_SESSION['history']), 0, 10);
        } catch (Throwable $ex) {
            $runError = $ex->getMessage();
        }
    }
}

/* ====== PRELOAD INFO ====== */
$tables = $connected ? list_tables($pdo) : [];

/* ====== HTML OUTPUT ====== */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Movie & TV Database Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root { 
    --bg: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    --bg-solid: #f8fafc;
    --card: rgba(255, 255, 255, 0.95);
    --card-border: rgba(0, 0, 0, 0.1);
    --muted: #64748b;
    --accent: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    --accent-hover: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    --success: #10b981;
    --error: #ef4444;
    --warning: #f59e0b;
    --text: #1e293b;
    --text-secondary: #475569;
  }
  
  * { 
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  
  body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg-solid);
    background-attachment: fixed;
    color: var(--text);
    line-height: 1.6;
    min-height: 100vh;
  }
  
  .wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 1rem;
  }
  
  .header {
    text-align: center;
    margin-bottom: 3rem;
  }
  
  .header h1 {
    font-size: 3.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 50%, #6366f1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
  }
  
  .header p {
    color: var(--text-secondary);
    font-size: 1.125rem;
    font-weight: 300;
  }
  
  .grid {
    display: grid;
    gap: 2rem;
    grid-template-columns: 1.3fr 0.7fr;
  }
  
  @media (max-width: 1024px) {
    .grid { grid-template-columns: 1fr; }
    .header h1 { font-size: 2.5rem; }
  }
  
  .card {
    background: var(--card);
    backdrop-filter: blur(20px);
    border: 1px solid var(--card-border);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
  }
  
  .card:hover {
    border-color: rgba(59, 130, 246, 0.3);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  }
  
  .card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text);
  }
  
  .card-subtitle {
    color: var(--muted);
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    font-weight: 400;
  }
  
  textarea, input, select {
    font-family: 'JetBrains Mono', monospace;
    width: 100%;
    border-radius: 12px;
    border: 1px solid var(--card-border);
    background: white;
    color: var(--text);
    padding: 1rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
  }
  
  textarea {
    min-height: 200px;
    resize: vertical;
    line-height: 1.5;
  }
  
  textarea:focus, input:focus, select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
  
  .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
  }
  
  .btn.primary {
    background: var(--accent);
    color: white;
    font-weight: 600;
  }
  
  .btn.primary:hover {
    background: var(--accent-hover);
    transform: translateY(-1px);
  }
  
  .btn.secondary {
    background: rgba(100, 116, 139, 0.1);
    color: var(--text-secondary);
    border: 1px solid var(--card-border);
  }
  
  .btn.secondary:hover {
    background: rgba(100, 116, 139, 0.2);
    color: var(--text);
  }
  
  .btn-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 1rem;
  }
  
  .divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, var(--card-border) 50%, transparent 100%);
    margin: 1.5rem 0;
  }
  
  .table-container {
    overflow: auto;
    border-radius: 8px;
    background: white;
    border: 1px solid var(--card-border);
    margin-top: 1rem;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
  }
  
  th, td {
    padding: 0.75rem;
    font-size: 0.875rem;
    border-bottom: 1px solid var(--card-border);
    text-align: left;
  }
  
  th {
    background: #f8fafc;
    font-weight: 600;
    color: var(--text-secondary);
  }
  
  td {
    font-family: 'JetBrains Mono', monospace;
    color: var(--text);
  }
  
  tr:hover td {
    background: #f1f5f9;
  }
  
  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .status-success {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }
  
  .status-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
  }
  
  .pill {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    background: rgba(100, 116, 139, 0.1);
    border: 1px solid var(--card-border);
    color: var(--text-secondary);
    font-size: 0.75rem;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
  }
  
  .pill:hover {
    background: rgba(100, 116, 139, 0.2);
    color: var(--text);
  }
  
  .code-inline {
    background: #f1f5f9;
    border: 1px solid var(--card-border);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.8rem;
  }
  
  .flex {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
  }
  
  .col {
    flex: 1;
    min-width: 200px;
  }
  
  .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  
  .history-item {
    display: block;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    background: #f8fafc;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.8rem;
    transition: all 0.2s ease;
    word-break: break-all;
    cursor: pointer;
  }
  
  .history-item:hover {
    background: #f1f5f9;
    color: var(--text);
  }
  
  .table-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    background: #f8fafc;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    transition: all 0.2s ease;
  }
  
  .table-item:hover {
    background: #f1f5f9;
  }
  
  .table-name {
    font-weight: 600;
    color: var(--text);
  }
  
  .error-message {
    padding: 1rem 1.5rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    color: #ef4444;
    margin-top: 1rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9rem;
  }
  
  .connection-error {
    text-align: center;
    padding: 2rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 12px;
    margin-bottom: 2rem;
  }
  
  .metrics {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid var(--card-border);
  }
  
  .metric-value {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 600;
    color: var(--success);
  }
  
  .metric-label {
    color: var(--muted);
    font-size: 0.875rem;
  }
  
  .sample-query {
    background: #f8fafc;
    border: 1px solid var(--card-border);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .sample-query:hover {
    background: #f1f5f9;
  }
  
  .sample-query code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.8rem;
    color: var(--text);
  }
  
  @media (max-width: 768px) {
    .wrap { padding: 1rem; }
    .card { padding: 1.5rem; }
    .header h1 { font-size: 2rem; }
    .flex { flex-direction: column; }
    .col { min-width: auto; }
    .row { flex-direction: column; align-items: stretch; }
  }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🎬 Movie & TV Database</h1>
    <p>Advanced SQL workspace for managing movies, TV series, and anime database</p>
    <?php if ($initialized): ?>
      <div style="margin-top: 1rem;">
        <span class="status-badge status-success">Database Initialized Successfully</span>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$connected): ?>
    <div class="connection-error">
      <div class="status-badge status-error">
        <span>●</span> Connection Failed
      </div>
      <p style="margin-top: 1rem; color: var(--error);"><?=h($errorMsg)?></p>
      <p style="margin-top: 0.5rem; font-size: 0.9rem;">Please check your database configuration at the top of the file.</p>
    </div>
  <?php endif; ?>

  <div class="grid">
    <!-- LEFT: SQL Console -->
    <div class="card">
      <div class="row">
        <div>
          <h2 class="card-title">SQL Console</h2>
          <p class="card-subtitle">Write and execute SQL statements with real-time feedback</p>
        </div>
        <?php if ($connected): ?>
          <div class="status-badge status-success">
            <span>●</span> <?=h($DB_NAME)?>
          </div>
        <?php endif; ?>
      </div>

      <form method="post">
        <textarea name="sql" id="sqlBox" placeholder="-- Write your SQL query here
-- Examples:
-- SELECT * FROM movies WHERE rating > 8.5;
-- SELECT title, release_year FROM movies ORDER BY release_year DESC;
-- SELECT m.title, d.first_name, d.last_name 
--   FROM movies m 
--   JOIN directors d ON m.director_id = d.director_id;"><?=h($input_sql ?: "")?></textarea>
        
        <div class="btn-row">
          <button class="btn primary" type="submit">
            <span>⚡</span> Execute Query
          </button>
          <button class="btn secondary" type="button" id="clearBtn">
            <span>🗑</span> Clear
          </button>
          <button class="btn secondary" type="button" id="sampleBtn">
            <span>📋</span> Load Sample
          </button>
          <button class="btn secondary" type="button" id="reinitBtn">
            <span>🔄</span> Reinitialize DB
          </button>
        </div>
      </form>

      <?php if ($runError): ?>
        <div class="error-message">
          <strong>Query Error:</strong> <?=h($runError)?>
        </div>
      <?php endif; ?>

      <?php if ($output): ?>
        <div class="divider"></div>
        
        <?php if ($output['type']==='resultset'): ?>
          <div class="metrics">
            <div>
              <span class="metric-label">Rows Returned:</span>
              <span class="metric-value"><?=h($output['rowcount'])?></span>
            </div>
            <div>
              <span class="metric-label">Execution Time:</span>
              <span class="metric-value"><?=number_format($output['time_ms'],2)?> ms</span>
            </div>
          </div>
          
          <?php $rows=$output['rows']; if (count($rows)): ?>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <?php foreach (array_keys($rows[0]) as $col): ?>
                      <th><?=h($col)?></th>
                    <?php endforeach; ?>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                    <tr>
                      <?php foreach ($r as $val): ?>
                        <td><?=h($val===null ? 'NULL' : (string)$val)?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="card-subtitle" style="margin-top: 1rem;">No rows returned from query.</div>
          <?php endif; ?>
        <?php else: ?>
          <div class="metrics">
            <div>
              <span class="metric-label">Affected Rows:</span>
              <span class="metric-value"><?=h($output['rowcount'])?></span>
            </div>
            <div>
              <span class="metric-label">Execution Time:</span>
              <span class="metric-value"><?=number_format($output['time_ms'],2)?> ms</span>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <!-- Sample Queries -->
      <div class="divider"></div>
      <h3 class="card-title">Sample Queries</h3>
      <p class="card-subtitle">Try these example queries to explore the database</p>
      
      <div class="sample-query" onclick="loadSampleQuery(this)">
        <code>-- Find top-rated movies
SELECT title, release_year, rating 
FROM movies 
WHERE rating > 8.0 
ORDER BY rating DESC;</code>
      </div>
      
      <div class="sample-query" onclick="loadSampleQuery(this)">
        <code>-- Movies with their directors
SELECT m.title, m.release_year, 
       CONCAT(d.first_name, ' ', d.last_name) AS director
FROM movies m
JOIN directors d ON m.director_id = d.director_id;</code>
      </div>
      
      <div class="sample-query" onclick="loadSampleQuery(this)">
        <code>-- Actors in movies (with roles)
SELECT m.title, 
       CONCAT(a.first_name, ' ', a.last_name) AS actor,
       ma.role_name
FROM movie_actors ma
JOIN movies m ON ma.movie_id = m.movie_id
JOIN actors a ON ma.actor_id = a.actor_id;</code>
      </div>

      <div class="sample-query" onclick="loadSampleQuery(this)">
        <code>-- Reviews with content details (NEW)
SELECT 
    r.review_id,
    r.reviewer_name,
    r.rating,
    r.review_text,
    COALESCE(m.title, t.title, a.title) as content_title,
    CASE 
        WHEN r.movie_id IS NOT NULL THEN 'Movie'
        WHEN r.tv_series_id IS NOT NULL THEN 'TV Series' 
        WHEN r.anime_id IS NOT NULL THEN 'Anime'
    END as content_type
FROM reviews r
LEFT JOIN movies m ON r.movie_id = m.movie_id
LEFT JOIN tv_series t ON r.tv_series_id = t.series_id
LEFT JOIN anime_series a ON r.anime_id = a.anime_id;</code>
      </div>

      <?php if (!empty($_SESSION['history'])): ?>
        <div class="divider"></div>
        <h3 class="card-title">Query History</h3>
        <div>
          <?php foreach ($_SESSION['history'] as $q): ?>
            <a href="#" class="history-item history" data-sql="<?=h($q)?>"><?=h($q)?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Schema + Quick Actions -->
    <div class="card">
      <h2 class="card-title">Database Schema</h2>
      <p class="card-subtitle">Explore tables in the movie database</p>
      
      <?php if (count($tables) === 0): ?>
        <div class="card-subtitle">No tables found in the database.</div>
      <?php else: ?>
        <div style="margin-bottom: 1.5rem;">
          <?php foreach ($tables as $t): ?>
            <div class="table-item">
              <div class="table-name">📊 <?=h($t)?></div>
              <div style="display: flex; gap: 0.5rem;">
                <a class="pill" href="#" onclick="quickDescribe('<?=h($t)?>')">
                  <span>🔍</span> Describe
                </a>
                <a class="pill" href="#" onclick="quickPreview('<?=h($t)?>')">
                  <span>👁</span> Preview
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="divider"></div>

      <h2 class="card-title">Quick Actions</h2>
      <p class="card-subtitle">Common database operations</p>
      
      <div class="btn-row" style="flex-direction: column; align-items: stretch;">
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SHOW TABLES;')">
          Show All Tables
        </button>
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SELECT COUNT(*) AS total_movies FROM movies;')">
          Count Movies
        </button>
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SELECT COUNT(*) AS total_actors FROM actors;')">
          Count Actors
        </button>
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SELECT genre_name, COUNT(*) AS count FROM genres g LEFT JOIN movies m ON g.genre_id = m.genre_id GROUP BY g.genre_id, g.genre_name;')">
          Movies by Genre
        </button>
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SELECT content_type, COUNT(*) AS count FROM reviews GROUP BY content_type;')">
          Reviews by Type
        </button>
        <button class="btn secondary" type="button" onclick="loadQuickQuery('SELECT COUNT(*) AS total_reviews FROM reviews;')">
          Count Reviews
        </button>
      </div>

      <div class="divider"></div>
      
      <h2 class="card-title">Database Info</h2>
      <p class="card-subtitle">About this movie database</p>
      
      <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid var(--card-border);">
        <p style="margin-bottom: 0.5rem;"><strong>10 Tables:</strong></p>
        <ul style="padding-left: 1.5rem; color: var(--text-secondary); font-size: 0.9rem;">
          <li>genres, directors, actors</li>
          <li>movies, tv_series, anime_series</li>
          <li>movie_actors, tv_episodes, anime_episodes, reviews</li>
        </ul>
        <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--muted);">
          <strong>Fixed:</strong> Reviews table now has proper foreign key constraints linking to movies, tv_series, and anime_series.
        </p>
      </div>
    </div>
  </div>
</div>

<script>
  // DOM elements
  const sqlBox = document.getElementById('sqlBox');
  const clearBtn = document.getElementById('clearBtn');
  const sampleBtn = document.getElementById('sampleBtn');
  const reinitBtn = document.getElementById('reinitBtn');
  
  // Event listeners
  clearBtn.addEventListener('click', () => {
    sqlBox.value = '';
    sqlBox.focus();
  });
  
  sampleBtn.addEventListener('click', () => {
    sqlBox.value = `-- Find all movies with their directors and genres
SELECT m.title, m.release_year, m.rating,
       CONCAT(d.first_name, ' ', d.last_name) AS director,
       g.genre_name
FROM movies m
LEFT JOIN directors d ON m.director_id = d.director_id
LEFT JOIN genres g ON m.genre_id = g.genre_id
ORDER BY m.rating DESC;`;
    sqlBox.focus();
  });
  
  reinitBtn.addEventListener('click', () => {
    if (confirm('This will reset the entire database and reload sample data. Continue?')) {
      window.location.href = '?reinit=1';
    }
  });
  
  // History items
  document.querySelectorAll('.history').forEach(a => {
    a.addEventListener('click', (e) => {
      e.preventDefault();
      sqlBox.value = a.dataset.sql;
      sqlBox.focus();
    });
  });
  
  // Keyboard shortcut (Ctrl+Enter to execute)
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      sqlBox.closest('form').submit();
    }
  });
  
  // Helper functions
  function loadSampleQuery(element) {
    sqlBox.value = element.querySelector('code').textContent;
    sqlBox.focus();
  }
  
  function loadQuickQuery(query) {
    sqlBox.value = query;
    sqlBox.focus();
  }
  
  function quickDescribe(tableName) {
    sqlBox.value = `DESCRIBE \`${tableName}\`;`;
    sqlBox.focus();
  }
  
  function quickPreview(tableName) {
    sqlBox.value = `SELECT * FROM \`${tableName}\` LIMIT 10;`;
    sqlBox.focus();
  }
  
  // Handle reinitialization via URL parameter
  <?php if (isset($_GET['reinit']) && $_GET['reinit'] == '1'): ?>
    setTimeout(() => {
      alert('Database reinitialized successfully!');
      window.location.href = window.location.pathname;
    }, 100);
  <?php endif; ?>
</script>
</body>
</html>