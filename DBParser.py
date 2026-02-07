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
                uuid TEXT NOT NULL,
                permission TEXT DEFAULT 'user' NOT NULL
            ) Strict;
        ''') 
        conn.commit()

    def add_user(username, pwdhash, uuid ,permission):
        cursor.execute('''
            INSERT INTO Users (username, password, uuid, permission)
            VALUES (?, ?, ?, ?)
        ''', (username, pwdhash, uuid, permission))
        conn.commit()

    def get_user(username):
        cursor.execute('''
            SELECT * FROM Users WHERE username = ?
        ''', (username,))
        return cursor.fetchone()
    
    def get_user_id(username):
        cursor.execute('''
            SELECT uuid FROM Users WHERE username = ?
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
            SELECT username FROM Users WHERE uuid = ?
        ''', (user_id))
        return cursor.fetchone()

    def get_user_permission(username):
        cursor.execute('''
            SELECT permission FROM Users WHERE username = ?
        ''', (username))
        return cursor.fetchone()

    def delete_user(username):
        cursor.execute('''
            DELETE FROM Users WHERE username = ?
        ''', (username))
        conn.commit()

    def update_password(username, new_pwdhash):
        cursor.execute('''
            UPDATE Users SET password = ? WHERE username = ?
        ''', (new_pwdhash, username))
        conn.commit()

    def update_permission(username, new_permission):
        cursor.execute('''
            UPDATE Users SET permission = ? WHERE username = ?
        ''', (new_permission, username))
        conn.commit()

    def update_username(old_username, new_username):
        cursor.execute('''
            UPDATE Users SET username = ? WHERE username = ?
        ''', (new_username, old_username))
        conn.commit()

class file:

    def create_File_table():
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Files (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                file_name TEXT NOT NULL,
                ownerid INTEGER NOT NULL,
                size INT NOT NULL,
                file_type TEXT NOT NULL,
                file_path TEXT NOT NULL
            ) Strict;
        ''')
        conn.commit()

    def add_file(file_name, ownerid, size, file_type, file_path):
        cursor.execute('''
            INSERT INTO Files (file_name, ownerid, size, file_type, file_path)
            VALUES (?, ?, ?, ?, ?)
        ''', (file_name, ownerid, size, file_type, file_path))
        conn.commit()

    def get_filepath(file_path):
        cursor.execute('''
            SELECT * FROM Files WHERE file_path = ?
        ''', (file_path,))
        return cursor.fetchone()

    def get_file(ownerid):
        cursor.execute('''
            SELECT * FROM Files WHERE ownerid = ?
        ''', (ownerid))
        return cursor.fetchall

    def get_file_by_id(file_id):
        cursor.execute('''
            SELECT * FROM Files WHERE id = ?
        ''', (file_id,))
        return cursor.fetchone()

    #Implement file deletion on server too!
    def delete_file(file_name):
        cursor.execute('''
            DELETE FROM Files WHERE file_name = ?
        ''', (file_name,))
        conn.commit()