#Remember to translate comments to german for final version!

import sqlite3

operation_mode = 'STRICT'  # Enabling strict mode for SQLite
conn = sqlite3.connect('Users.db', check_same_thread=False)
cursor = conn.cursor()

class user:

    def create_User_table():
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Users (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                username TEXT NOT NULL,
                password TEXT NOT NULL,
                user_id TEXT NOT NULL,
                permission TEXT DEFAULT 'user' NOT NULL
            ) Strict;
        ''') 
        conn.commit()

    def add_user(username, pwdhash, user_id, permission):
        cursor.execute('''
            INSERT INTO Users (username, password, user_id, permission)
            VALUES (?, ?, ?, ?)
        ''', (username, pwdhash, user_id, permission,))
        conn.commit()

    def get_user(username):
        cursor.execute('''
            SELECT * FROM Users WHERE username = ?
        ''', (username,))
        return cursor.fetchone()
    
    def get_user_id(username):
        cursor.execute('''
            SELECT user_id FROM Users WHERE username = ?
        ''', (username,))
        row = cursor.fetchone()
        return row[0] if row else None

    def get_password_hash(username):
        cursor.execute('''
            SELECT password FROM Users WHERE username = ?
        ''', (username,))
        row = cursor.fetchone()
        return row[0] if row else None

    def get_user_by_id(user_id):
        cursor.execute('''
            SELECT username FROM Users WHERE user_id = ?
        ''', (user_id,))
        return cursor.fetchone()

    def get_user_permission(username):
        cursor.execute('''
            SELECT permission FROM Users WHERE username = ?
        ''', (username,))
        return cursor.fetchone()

    def delete_user(username):
        cursor.execute('''
            DELETE FROM Users WHERE username = ?
        ''', (username,))
        conn.commit()

    def update_password(username, new_pwdhash):
        cursor.execute('''
            UPDATE Users SET password = ? WHERE username = ?
        ''', (new_pwdhash, username,))
        conn.commit()

    def update_permission(username, new_permission):
        cursor.execute('''
            UPDATE Users SET permission = ? WHERE username = ?
        ''', (new_permission, username,))
        conn.commit()

    def update_username(old_username, new_username):
        cursor.execute('''
            UPDATE Users SET username = ? WHERE username = ?
        ''', (new_username, old_username,))
        conn.commit()

class file:

    def create_File_table():
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Files (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                file_id TEXT NOT NULL,
                file_name TEXT NOT NULL,
                owner_id TEXT NOT NULL,
                file_size INT NOT NULL,
                file_path TEXT NOT NULL
            ) Strict;
        ''')
        conn.commit()

    def add_file(file_id, file_name, owner_id, file_size, file_path):
        cursor.execute('''
            INSERT INTO Files (file_id, file_name, owner_id, file_size, file_path)
            VALUES (?, ?, ?, ?, ?)
        ''', (file_id, file_name, owner_id, file_size, file_path,))
        conn.commit()

    def get_filepath(file_path):
        cursor.execute('''
            SELECT * FROM Files WHERE file_path = ?
        ''', (file_path,))
        return cursor.fetchone()

    def get_file(owner_id) -> list[dict]:
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM files WHERE owner_id = ?", (owner_id,))
        rows = cursor.fetchall()
        return [dict(row) for row in rows]
    
    def get_file_by_id(file_id):
        conn.row_factory = sqlite3.Row
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM Files WHERE file_id = ?", (file_id,))
        return cursor.fetchone()

    def delete_file(file_id):
        cursor.execute('''
            DELETE FROM Files WHERE file_id = ?
        ''', (file_id,))
        conn.commit()