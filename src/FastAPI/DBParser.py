import sqlite3

ONE_GB = 1073741824  # 1GB in bytes

conn   = sqlite3.connect('Users.db', check_same_thread=False)
cursor = conn.cursor()


class user:

    def create_User_table():
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Users (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                username TEXT NOT NULL,
                password TEXT NOT NULL,
                user_id TEXT NOT NULL,
                permission TEXT DEFAULT 'user' NOT NULL,
                force_password_change INTEGER DEFAULT 0 NOT NULL,
                storage_limit INTEGER DEFAULT 1073741824 NOT NULL
            ) Strict;
        ''')
        conn.commit()

    def migrate():
        for column, definition in [
            ('force_password_change', 'INTEGER DEFAULT 0 NOT NULL'),
            ('storage_limit',         'INTEGER DEFAULT 1073741824 NOT NULL'),
        ]:
            try:
                cursor.execute(f'ALTER TABLE Users ADD COLUMN {column} {definition}')
                conn.commit()
            except sqlite3.OperationalError:
                pass  # Column already exists

    def add_user(username, pwdhash, user_id, permission, force_password_change=0, storage_limit=ONE_GB):
        cursor.execute('''
            INSERT INTO Users (username, password, user_id, permission, force_password_change, storage_limit)
            VALUES (?, ?, ?, ?, ?, ?)
        ''', (username, pwdhash, user_id, permission, force_password_change, storage_limit))
        conn.commit()

    def get_user(username):
        cursor.execute('SELECT * FROM Users WHERE username = ?', (username,))
        return cursor.fetchone()

    def get_user_id(username):
        cursor.execute('SELECT user_id FROM Users WHERE username = ?', (username,))
        row = cursor.fetchone()
        return row[0] if row else None

    def get_password_hash(username):
        cursor.execute('SELECT password FROM Users WHERE username = ?', (username,))
        row = cursor.fetchone()
        return row[0] if row else None

    def get_user_by_id(user_id):
        cursor.execute('SELECT username FROM Users WHERE user_id = ?', (user_id,))
        return cursor.fetchone()

    def get_user_permission(username):
        cursor.execute('SELECT permission FROM Users WHERE username = ?', (username,))
        return cursor.fetchone()

    def get_force_password_change(user_id):
        cursor.execute('SELECT force_password_change FROM Users WHERE user_id = ?', (user_id,))
        row = cursor.fetchone()
        return bool(row[0]) if row else False

    def set_force_password_change(user_id, value: bool):
        cursor.execute('UPDATE Users SET force_password_change = ? WHERE user_id = ?', (1 if value else 0, user_id))
        conn.commit()

    def get_storage_limit(user_id) -> int:
        cursor.execute('SELECT storage_limit FROM Users WHERE user_id = ?', (user_id,))
        row = cursor.fetchone()
        return row[0] if row else ONE_GB

    def set_storage_limit(user_id, limit: int):
        cursor.execute('UPDATE Users SET storage_limit = ? WHERE user_id = ?', (limit, user_id))
        conn.commit()

    def get_all_users() -> list[dict]:
        conn.row_factory = sqlite3.Row
        cur = conn.cursor()
        cur.execute('SELECT id, username, user_id, permission, force_password_change, storage_limit FROM Users')
        rows = cur.fetchall()
        conn.row_factory = None
        return [dict(row) for row in rows]

    def delete_user_by_id(user_id):
        cursor.execute('DELETE FROM Users WHERE user_id = ?', (user_id,))
        conn.commit()

    def delete_user(username):
        cursor.execute('DELETE FROM Users WHERE username = ?', (username,))
        conn.commit()

    def update_password(username, new_pwdhash):
        cursor.execute('UPDATE Users SET password = ? WHERE username = ?', (new_pwdhash, username))
        conn.commit()

    def update_password_by_id(user_id, new_pwdhash):
        cursor.execute('UPDATE Users SET password = ? WHERE user_id = ?', (new_pwdhash, user_id))
        conn.commit()

    def update_permission(username, new_permission):
        cursor.execute('UPDATE Users SET permission = ? WHERE username = ?', (new_permission, username))
        conn.commit()

    def update_username(old_username, new_username):
        cursor.execute('UPDATE Users SET username = ? WHERE username = ?', (new_username, old_username))
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
        ''', (file_id, file_name, owner_id, file_size, file_path))
        conn.commit()

    def get_filepath(file_path):
        cursor.execute('SELECT * FROM Files WHERE file_path = ?', (file_path,))
        return cursor.fetchone()

    def get_file(owner_id) -> list[dict]:
        conn.row_factory = sqlite3.Row
        cur = conn.cursor()
        cur.execute("SELECT * FROM Files WHERE owner_id = ?", (owner_id,))
        rows = cur.fetchall()
        conn.row_factory = None
        return [dict(row) for row in rows]

    def get_file_by_id(file_id):
        conn.row_factory = sqlite3.Row
        cur = conn.cursor()
        cur.execute("SELECT * FROM Files WHERE file_id = ?", (file_id,))
        return cur.fetchone()

    def get_disk_usage(owner_id) -> int:
        cursor.execute('SELECT COALESCE(SUM(file_size), 0) FROM Files WHERE owner_id = ?', (owner_id,))
        row = cursor.fetchone()
        return row[0] if row else 0

    def delete_file(file_id):
        cursor.execute('DELETE FROM Files WHERE file_id = ?', (file_id,))
        conn.commit()

    def delete_all_user_files(owner_id):
        cursor.execute('DELETE FROM Files WHERE owner_id = ?', (owner_id,))
        conn.commit()