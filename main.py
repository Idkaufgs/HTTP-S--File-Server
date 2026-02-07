from DBParser import user, file
from argon2 import PasswordHasher
from fastapi import FastAPI
from pydantic import BaseModel
import uuid

Hash = PasswordHasher(
    time_cost= 3,
    memory_cost= 64 * 1024, #64MB
    parallelism= 2,
)

app = FastAPI()

class Loginrequest(BaseModel):
    username:str
    password:str

class Createuserrequest(BaseModel):
    uname:str
    pwd:str

class GrabFilesrequest(BaseModel):
    userid:str

@app.post("/auth/login")
def login(data: Loginrequest):
    password = data.password
    username = data.username
    hash_ = user.get_password_hash(username,)
    if hash_ is None:
        return{"success": False, "detail": "Invalid username or password!"}
    userid = user.get_user_id(username,)
    if not (Hash.verify(hash_, password)):
        return{"success": False, "detail": "Invalid username or password!"}
    return {"success": True, "user_id": userid}

@app.post("/auth/create_account")
def create_account(data: Createuserrequest):
    username = data.uname
    password = data.pwd
    if(not user.get_user(username)):
        return{"success": False}
    hash = Hash.hash(password)
    user_id = uuid.uuid5(uuid.NAMESPACE_DNS, username)
    if(user.add_user(username, hash, user_id)):
        return{"success": True, "user_id": user_id}
    return{"success": False}


@app.get("/files/get")
def get_files(data: GrabFilesrequest):
    owner = data.userid
    files = file.get_file(owner)
    return{files}
