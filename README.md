# Movie & TV Database Manager

A comprehensive PHP-based web application for managing and querying a movie, TV series, and anime database with an integrated SQL console interface.

## 🎬 Project Overview

This project provides a complete database management system for entertainment content including movies, TV series, and anime. It features an intuitive web interface with a built-in SQL console, automatic database initialization, and sample data for immediate testing and exploration.

The application automatically creates and populates a MySQL database with interconnected tables for genres, directors, actors, movies, TV series, anime, episodes, and reviews. Users can execute SQL queries directly through the web interface and explore the database schema.

## ✨ Features

### Database Management

- **Automatic Setup**: Database and tables are created automatically on first run
- **Sample Data**: Pre-loaded with sample movies, TV series, anime, actors, directors, and reviews
- **Foreign Key Constraints**: Proper relationships between entities
- **Error Handling**: Robust error handling for database operations

### SQL Console

- **Real-time Execution**: Execute SQL queries with instant results
- **Query History**: Maintains history of executed queries (last 10 unique queries)
- **Syntax Highlighting**: Monospace font for better SQL readability
- **Performance Metrics**: Shows execution time and affected rows

### Database Schema Explorer

- **Table Overview**: List all tables in the database
- **Quick Actions**: Describe tables and preview data
- **Schema Information**: Detailed table structure and relationships

### User Interface

- **Responsive Design**: Works on desktop and mobile devices
- **Modern UI**: Clean, gradient-based design with smooth animations
- **Keyboard Shortcuts**: Ctrl+Enter to execute queries
- **Sample Queries**: Pre-built example queries for common operations

## 🗄️ Database Schema

The database consists of 10 interconnected tables:

### Core Tables

- **`genres`** - Movie/TV genres with descriptions
- **`directors`** - Film directors with biographical information
- **`actors`** - Cast members with personal details

### Content Tables

- **`movies`** - Feature films with ratings, budgets, and relationships
- **`tv_series`** - Television shows with season information
- **`anime_series`** - Animated series with studio information

### Relationship Tables

- **`movie_actors`** - Junction table linking movies to actors with roles
- **`tv_episodes`** - Individual TV episodes with air dates
- **`anime_episodes`** - Individual anime episodes with air dates
- **`reviews`** - User reviews for movies, TV series, and anime

### Key Relationships

- Movies → Directors, Genres, Actors (many-to-many)
- TV Series → Directors, Genres, Episodes
- Anime Series → Genres, Episodes
- Reviews → Movies/TV Series/Anime (polymorphic relationship)

## 🚀 Setup Instructions

### Prerequisites

- **PHP 7.4+** with PDO extension
- **MySQL 5.7+** or **MariaDB 10.0+**
- **Web Server** (Apache/Nginx) or **XAMPP/WAMP** for local development
- **Browser** with JavaScript enabled

### Installation Steps

1. **Clone/Download the Project**

   ```bash
   # Place the index.php file in your web server's document root
   # For XAMPP: htdocs/DBProject/
   # For WAMP: www/DBProject/
   ```

2. **Configure Database Connection**
   Edit the database configuration at the top of `index.php`:

   ```php
   $DB_HOST = '127.0.0.1';  // Your MySQL host
   $DB_NAME = 'movie_tv_db'; // Database name (will be created automatically)
   $DB_USER = 'root';        // Your MySQL username
   $DB_PASS = '';            // Your MySQL password
   ```

3. **Access the Application**
   Open your browser and navigate to:

   ```
   http://localhost/DBProject/index.php
   ```

4. **Automatic Initialization**
   - The application will automatically create the database and tables
   - Sample data will be inserted on first run
   - A success message will appear when initialization is complete

### Troubleshooting

- **Connection Failed**: Check MySQL credentials and ensure MySQL is running
- **Permission Denied**: Ensure PHP has write permissions for database operations
- **Tables Not Created**: Check MySQL user privileges for CREATE DATABASE and CREATE TABLE

## 📖 Usage Guide

### Basic Operations

1. **Execute SQL Queries**

   - Type your SQL in the textarea
   - Click "Execute Query" or press Ctrl+Enter
   - View results in the table below

2. **Explore Database Schema**

   - Use the "Database Schema" panel on the right
   - Click "Describe" to see table structure
   - Click "Preview" to see sample data

3. **Use Quick Actions**
   - Pre-built buttons for common queries
   - Includes table counts, genre breakdowns, and review statistics

### Sample Queries

#### Movies

```sql
-- Find top-rated movies
SELECT title, release_year, rating
FROM movies
WHERE rating > 8.0
ORDER BY rating DESC;
```

#### Directors and Movies

```sql
-- Movies with their directors
SELECT m.title, m.release_year,
       CONCAT(d.first_name, ' ', d.last_name) AS director
FROM movies m
JOIN directors d ON m.director_id = d.director_id;
```

#### Actors and Roles

```sql
-- Actors in movies (with roles)
SELECT m.title,
       CONCAT(a.first_name, ' ', a.last_name) AS actor,
       ma.role_name
FROM movie_actors ma
JOIN movies m ON ma.movie_id = m.movie_id
JOIN actors a ON ma.actor_id = a.actor_id;
```

#### Reviews

```sql
-- Reviews with content details
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
LEFT JOIN anime_series a ON r.anime_id = a.anime_id;
```

### Advanced Queries

#### Complex Joins

```sql
-- Movies with directors, genres, and lead actors
SELECT m.title, m.release_year, m.rating,
       CONCAT(d.first_name, ' ', d.last_name) AS director,
       g.genre_name,
       CONCAT(a.first_name, ' ', a.last_name) AS lead_actor
FROM movies m
LEFT JOIN directors d ON m.director_id = d.director_id
LEFT JOIN genres g ON m.genre_id = g.genre_id
LEFT JOIN movie_actors ma ON m.movie_id = ma.movie_id AND ma.is_lead_role = TRUE
LEFT JOIN actors a ON ma.actor_id = a.actor_id;
```

#### Analytics

```sql
-- Average rating by genre
SELECT g.genre_name,
       COUNT(m.movie_id) as movie_count,
       ROUND(AVG(m.rating), 2) as avg_rating
FROM genres g
LEFT JOIN movies m ON g.genre_id = m.genre_id
GROUP BY g.genre_id, g.genre_name
ORDER BY avg_rating DESC;
```

## 🔧 Technical Details

### Architecture

- **Single File Application**: Everything contained in `index.php`
- **Session Management**: Uses PHP sessions for query history
- **PDO Database Layer**: Secure database operations with prepared statements
- **Responsive CSS**: Modern styling with CSS custom properties

### Security Features

- **Input Sanitization**: All user inputs are escaped using `htmlspecialchars()`
- **SQL Injection Protection**: PDO with parameterized queries
- **Session Security**: Proper session handling for user data

### Performance

- **Lazy Loading**: Database initialized only when needed
- **Query Optimization**: Efficient table structures with proper indexing
- **Caching**: Session-based query history storage

### Browser Compatibility

- **Modern Browsers**: Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- **Mobile Support**: Responsive design works on tablets and phones
- **JavaScript**: Required for interactive features

## 📊 Database Statistics

After initialization, the database contains:

- **10 Tables** with proper relationships
- **Sample Data**: Movies, TV series, anime, actors, directors, reviews
- **Foreign Keys**: Enforced referential integrity
- **Indexes**: Optimized for common query patterns

## 🤝 Contributing

To extend this project:

1. **Add New Tables**: Modify the `initializeDatabase()` function
2. **Update Sample Data**: Edit the `insertSampleData()` function
3. **Enhance UI**: Modify the CSS and HTML sections
4. **Add Features**: Extend the JavaScript functionality

## 📄 License

This project is open source and available under the MIT License.

## 🆘 Support

If you encounter issues:

1. Check the database connection settings
2. Ensure MySQL is running and accessible
3. Verify PHP PDO extension is installed
4. Check browser console for JavaScript errors

For questions or improvements, please refer to the code comments in `index.php` for detailed implementation notes.
