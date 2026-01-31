# Error 0: Missing required arguments.
# Error 1: Insufficient permissions.
# Error 2: User not found.
# Error 3: User already exists.
# Error 4: Unknown error.

from DBParser import add_user, get_user, get_user_by_id, get_user_permission, delete_user, update_password
from argon2 import PasswordHasher
import sys

Hash = PasswordHasher(
    time_cost= 3,
    memory_cost= 64 * 1024, #64MB
    parallelism= 2,
)

password = sys.argv[1] if len(sys.argv) > 1 else None
username = sys.argv[2] if len(sys.argv) > 2 else None
initiating_user = sys.argv[3] if len(sys.argv) > 3 else None

if not password:
    print("Error 0: Password required.")
    exit(1)
else:
    pwdhash = Hash.hash(password)

operation = ""
user_id = ""
permission = ""
user_permission = get_user_permission(username)

#Remember to add special permisions handling!

try:

    if operation == 'add':
        #Only admins and acounts with special permissions can add new users.
        if username and pwdhash and permission and user_permission == 'admin':
            if get_user(username):
                print("Error 3:" "User already exists.")
            else:
                add_user(username, pwdhash, permission)
                print("Success!")
        elif user_permission != 'admin':
            print("Error 1:" "Insufficient permissions to add user.")
        else:
            print("Error 0:" "Username and pwdhash required for adding a user.")

    elif operation == 'get':
        #get userdata either via username or userid.
        if username:
            user = get_user(username)
            if user:
                print("Success!")
                print(user)
            else:
                print("Error 2:" "User not found.")
        elif user_id:
            user = get_user_by_id(user_id)
            if user:
                print("Success!")
                print(user)
            else:
                print("Error 2:" "User not found.")
        else:
            print("Error 0:" "Username required for getting a user.")

#Remember to add a flag for removing the Files owned by the user (potential delay?) for cleanup!(doesn't have do be here just somewere in some script that gets called when deleting a user (maybe seperate script for deleting users?))
    elif operation == 'delete':
        #Only admins, the owner of the account or acounts with special permissions can delete users.
        if username and username != 'admin' and (user_permission == 'admin' or username == initiating_user):
            if not get_user(username):
                print("Error 2:" "User not found.")
            else:
                delete_user(username)
                print("Success!")
        elif user_permission != 'admin' and username != initiating_user:
            print("Error 1:" "Insufficient permissions to delete user.")
        else:
            print("Error 0:" "Username required for deleting a user.")

    elif operation == 'update':
        #Only admins, the owner of the account or acounts with special permissions can update users
        if username and pwdhash and (user_permission == 'admin' or username == initiating_user):
            update_password(username, pwdhash)
        elif user_permission != 'admin' and username != initiating_user:
            print("Error 1:" "Insufficient permissions to update user.")
        else:
            print("Error 0:" "Username and new pwdhash required for updating a user.")
    else :
        print("Error 0:" "Invalid or missing operation parameter.")
        print("Options: add, get, delete, update")

except Exception as e:
    print(f"Error 4:  {e}")