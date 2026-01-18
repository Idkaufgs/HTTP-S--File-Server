import sqlite3
import sys

operation = sys.argv[1] if len(sys.argv) > 1 else None
username = sys.argv[2] if len(sys.argv) > 2 else None
user_id = sys.argv[3] if len(sys.argv) > 3 else None
password = sys.argv[4] if len(sys.argv) > 4 else None
permission = sys.argv[5] if len(sys.argv) > 5 else 'user'
user_permission = sys.argv[6] if len(sys.argv) > 6 else 'user'
initiating_user = sys.argv[7] if len(sys.argv) > 7 else None

#Adding support for future special user permissions
special_user_permission_manage_users = sys.argv[8] if len(sys.argv) > 8 else None
special_user_permission_add_users = sys.argv[9] if len(sys.argv) > 9 else None

supmu = special_user_permission_manage_users
supau = special_user_permission_add_users

operation_mode = 'STRICT'  # Enabling strict mode for SQLite
conn = sqlite3.connect('Users.db')
cursor = conn.cursor()

def create_table():
    cursor.execute('''
        CREATE TABLE IF NOT EXISTS Users (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            username TEXT NOT NULL,
            password TEXT NOT NULL,
            Permissions TEXT DEFAULT 'user' NOT NULL
        ) Strict;
    ''') 
    conn.commit()

def add_user(username, password, permission):
    cursor.execute('''
        INSERT INTO Users (username, password, Permissions)
        VALUES (?, ?, ?)
    ''', (username, password, permission))
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

def delete_user(username):
    cursor.execute('''
        DELETE FROM Users WHERE username = ?
    ''', (username,))
    conn.commit()

def update_password(username, new_password):
    cursor.execute('''
        UPDATE Users SET password = ? WHERE username = ?
    ''', (new_password, username))
    conn.commit()

create_table()

try:

    if operation == 'add':
        if username and password and permission and (user_permission == 'admin' or supau == 'true'):
            if get_user(username):
                print("Error 3:" "User already exists.")
            else:
                add_user(username, password, permission)
        elif user_permission != 'admin' and supau != 'true':
            print("Error 1:" "Insufficient permissions to add user.")
        else:
            print("Error 0:" "Username and password required for adding a user.")

    elif operation == 'get':
        if username:
            user = get_user(username)
            if user:
                print(user)
            else:
                print("Error 2:" "User not found.")
        elif user_id:
            user = get_user_by_id(user_id)
            if user:
                print(user)
            else:
                print("Error 2:" "User not found.")
        else:
            print("Error 0:" "Username required for getting a user.")

    elif operation == 'delete':
        if username and username != 'admin' and (user_permission == 'admin' or username == initiating_user or supmu == 'true'):
            if not get_user(username):
                print("Error 2:" "User not found.")
            else:
                delete_user(username)
        elif user_permission != 'admin' and username != initiating_user and supmu != 'true':
            print("Error 1:" "Insufficient permissions to delete user.")
        else:
            print("Error 0:" "Username required for deleting a user.")

    elif operation == 'update':
        if username and password and (user_permission == 'admin' or username == initiating_user or supmu == 'true'):
            update_password(username, password)
        elif user_permission != 'admin' and username != initiating_user and supmu != 'true':
            print("Error 1:" "Insufficient permissions to update user.")
        else:
            print("Error 0:" "Username and new password required for updating a user.")
    else :
        print("Error 0:" "Invalid or missing operation parameter.")
        print("Options: add, get, delete, update")

except Exception as e:
    print(f"Error 4:  {e}")

# Error 0: Missing required arguments.
# Error 1: Insufficient permissions.
# Error 2: User not found.
# Error 3: User already exists.
# Error 4: Unknown error.

conn.close()