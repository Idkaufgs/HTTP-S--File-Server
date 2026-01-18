#Remember to translate comments to german for final version!

import sqlite3
import sys

operation_mode = 'STRICT'  # Enabling strict mode for SQLite
conn = sqlite3.connect('Users.db')
cursor = conn.cursor()

def create_User_table():
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS Users (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            username TEXT NOT NULL,
            password TEXT NOT NULL,
            permission TEXT DEFAULT 'user' NOT NULL
        ) Strict;
    ''') 
    conn.commit()

def add_user(username, pwdhash, permission):
    cursor.execute('''
        INSERT INTO Users (username, password, permission)
        VALUES (?, ?, ?)
    ''', (username, pwdhash, permission))
    conn.commit()

def get_user(username):
    cursor.execute('''
        SELECT * FROM Users WHERE username = ?
    ''', (username,))
    return cursor.fetchone()

def get_user_by_id(user_id):
    cursor.execute('''
        SELECT * FROM Users WHERE id = ?
    ''', (user_id,))
    return cursor.fetchone()

def get_user_permission(username):
    cursor.execute('''
        SELECT permission FROM users WHERE username = ?
    '''(username,))
    return cursor.fetchone()

def delete_user(username):
    cursor.execute('''
        DELETE FROM Users WHERE username = ?
    ''', (username,))
    conn.commit()

def update_password(username, new_pwdhash):
    cursor.execute('''
        UPDATE Users SET password = ? WHERE username = ?
    ''', (new_pwdhash, username))
    conn.commit()

def create_File_table():
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS Files (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            file_name TEXT NOT NULL,
            owner TEXT NOT NULL,
            size INT NOT NULL
            file_type TEXT NOT NULL
            file_path TEXT NOT NULL
        ) Strict;
    ''') 
    conn.commit()

def add_file(file_name, owner, size, file_type, file_path):
    cursor.execute('''
        INSERT INTO Files (file_name, owner, size, file_type, file_path)
        VALUES (?, ?, ?, ?, ?)
    ''', (file_name, owner, size, file_type, file_path))
    conn.commit()

def get_file(file_path):
    cursor.execute('''
        SELECT * FROM Files WHERE file_path = ?
    ''', (file_path,))
    return cursor.fetchone()

def get_file_by_id(file_id):
    cursor.execute('''
        SELECT * FROM Files WHERE id = ?
    ''', (file_id,))
    return cursor.fetchone()

def delete_file(file_name):
    cursor.execute('''
        DELETE FROM Files WHERE file_name = ?
    ''', (file_name,))
    conn.commit()