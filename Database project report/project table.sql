set pagesize 100;
set linesize 200;

DROP TABLE media_genres;
DROP TABLE media_people;
DROP TABLE people;
DROP TABLE genres;
DROP TABLE media;

CREATE TABLE media (
    media_id NUMBER PRIMARY KEY,
    title VARCHAR2(255) NOT NULL,
    overview CLOB,
    release_date DATE,
    runtime NUMBER,
    media_type VARCHAR2(10) NOT NULL CHECK (media_type IN ('Movie', 'TV Series')),
    poster_url VARCHAR2(500),
    status VARCHAR2(50) DEFAULT 'Released',
    number_of_seasons NUMBER DEFAULT 0,
    number_of_episodes NUMBER DEFAULT 0
);

---------------------------------------------------
-- 2. Genres Table
---------------------------------------------------
CREATE TABLE genres (
    genre_id NUMBER PRIMARY KEY,
    genre_name VARCHAR2(100) NOT NULL UNIQUE
);

---------------------------------------------------
-- 3. People Table
---------------------------------------------------
CREATE TABLE people (
    person_id NUMBER PRIMARY KEY,
    person_name VARCHAR2(255) NOT NULL,
    biography CLOB,
    profile_url VARCHAR2(500),
    date_of_birth DATE
);

---------------------------------------------------
-- 4. Media_People (junction table)
---------------------------------------------------
CREATE TABLE media_people (
    media_id NUMBER,
    person_id NUMBER,
    role VARCHAR2(100) NOT NULL,
    character_name VARCHAR2(255),
    PRIMARY KEY (media_id, person_id, role),
    FOREIGN KEY (media_id) REFERENCES media(media_id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(person_id) ON DELETE CASCADE
);

---------------------------------------------------
-- 5. Media_Genres (junction table)
---------------------------------------------------
CREATE TABLE media_genres (
    media_id NUMBER,
    genre_id NUMBER,
    PRIMARY KEY (media_id, genre_id),
    FOREIGN KEY (media_id) REFERENCES media(media_id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
);

---------------------------------------------------
-- Media (3 records)
---------------------------------------------------
INSERT INTO media (media_id, title, overview, release_date, runtime, media_type, poster_url, status, number_of_seasons, number_of_episodes)
VALUES (1, 'Inception', 'A thief who steals corporate secrets through dream-sharing technology.', TO_DATE('2010-07-16','YYYY-MM-DD'), 148, 'Movie', 'http://example.com/inception.jpg', 'Released', 0, 0);

INSERT INTO media (media_id, title, overview, release_date, runtime, media_type, poster_url, status, number_of_seasons, number_of_episodes)
VALUES (2, 'Breaking Bad', 'A chemistry teacher turned meth producer.', TO_DATE('2008-01-20','YYYY-MM-DD'), NULL, 'TV Series', 'http://example.com/breakingbad.jpg', 'Ended', 5, 62);

INSERT INTO media (media_id, title, overview, release_date, runtime, media_type, poster_url, status, number_of_seasons, number_of_episodes)
VALUES (3, 'The Matrix', 'A computer hacker learns the true nature of reality.', TO_DATE('1999-03-31','YYYY-MM-DD'), 136, 'Movie', 'http://example.com/matrix.jpg', 'Released', 0, 0);

---------------------------------------------------
-- Genres (4 records)
---------------------------------------------------
INSERT INTO genres (genre_id, genre_name) VALUES (1, 'Action');
INSERT INTO genres (genre_id, genre_name) VALUES (2, 'Drama');
INSERT INTO genres (genre_id, genre_name) VALUES (3, 'Science Fiction');
INSERT INTO genres (genre_id, genre_name) VALUES (4, 'Thriller');

---------------------------------------------------
-- People (4 records)
---------------------------------------------------
INSERT INTO people (person_id, person_name, biography, profile_url, date_of_birth)
VALUES (1, 'Leonardo DiCaprio', 'American actor and film producer.', 'http://example.com/leo.jpg', TO_DATE('1974-11-11','YYYY-MM-DD'));

INSERT INTO people (person_id, person_name, biography, profile_url, date_of_birth)
VALUES (2, 'Bryan Cranston', 'American actor, director, and producer.', 'http://example.com/cranston.jpg', TO_DATE('1956-03-07','YYYY-MM-DD'));

INSERT INTO people (person_id, person_name, biography, profile_url, date_of_birth)
VALUES (3, 'Keanu Reeves', 'Canadian actor known for The Matrix and John Wick.', 'http://example.com/keanu.jpg', TO_DATE('1964-09-02','YYYY-MM-DD'));

INSERT INTO people (person_id, person_name, biography, profile_url, date_of_birth)
VALUES (4, 'Christopher Nolan', 'British-American film director, producer, and screenwriter.', 'http://example.com/nolan.jpg', TO_DATE('1970-07-30','YYYY-MM-DD'));

---------------------------------------------------
-- Media_People (Actors/Directors)
---------------------------------------------------
-- Inception: Leonardo DiCaprio as actor
INSERT INTO media_people (media_id, person_id, role, character_name)
VALUES (1, 1, 'Actor', 'Dom Cobb');

-- Breaking Bad: Bryan Cranston as actor
INSERT INTO media_people (media_id, person_id, role, character_name)
VALUES (2, 2, 'Actor', 'Walter White');

-- The Matrix: Keanu Reeves as actor
INSERT INTO media_people (media_id, person_id, role, character_name)
VALUES (3, 3, 'Actor', 'Neo');

-- Inception directed by Christopher Nolan
INSERT INTO media_people (media_id, person_id, role, character_name)
VALUES (1, 4, 'Director', NULL);

---------------------------------------------------
-- Media_Genres (Genre links)
---------------------------------------------------
-- Inception = Action + Science Fiction + Thriller
INSERT INTO media_genres (media_id, genre_id) VALUES (1, 1);
INSERT INTO media_genres (media_id, genre_id) VALUES (1, 3);
INSERT INTO media_genres (media_id, genre_id) VALUES (1, 4);

-- Breaking Bad = Drama + Thriller
INSERT INTO media_genres (media_id, genre_id) VALUES (2, 2);
INSERT INTO media_genres (media_id, genre_id) VALUES (2, 4);

-- The Matrix = Action + Science Fiction
INSERT INTO media_genres (media_id, genre_id) VALUES (3, 1);
INSERT INTO media_genres (media_id, genre_id) VALUES (3, 3);
