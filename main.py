from DBParser import user, file
from argon2 import PasswordHasher
from fastapi import FastAPI, UploadFile, File, Form
from fastapi.responses import JSONResponse, FileResponse
from pydantic import BaseModel
import uuid
import os
import shutil
import tempfile
from pathlib import Path

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

TEMP_DIR = Path("C:/Files/temp")
UPLOAD_DIR_BASE = Path("C:/Files")

@app.get("/debug/routes")
def list_routes():
    return [{"path": r.path, "methods": list(r.methods)} for r in app.routes]

@app.post("/auth/login")
def login(data: Loginrequest):
    password = data.password
    username = data.username
    hash_ = user.get_password_hash(username,)
    if hash_ is None:
        return{"success": False, "detail": "Invalid username or password!"}
    userid = user.get_user_id(username,)
    if (Hash.verify(hash_, password,)):
        return {"success": True, "user_id": userid} 
    return{"success": False, "detail": "Invalid username or password!"}


@app.post("/auth/create_account")
def create_account(data: Createuserrequest):
    username = data.uname
    password = data.pwd
    permission = 'user'
    if(user.get_user(username)):
        return{"success": False}
    hash = Hash.hash(password)
    user_id = str(uuid.uuid5(uuid.NAMESPACE_DNS, username))
    if(user.add_user(username, hash, user_id, permission)):
        return{"success": True, "user_id": user_id}
    return{"success": False}


@app.get("/files/download/{file_id}")
def download_files(file_id: str):
    file_ = file.get_file_by_id(file_id)

    if file_ is None:
        return JSONResponse(status_code=404, content={"error": "File not found"})
    if not os.path.exists(file_["file_path"]):
        return JSONResponse(status_code=410, content={"error": "File missing from directory"})
    
    return FileResponse(
        path=file_["file_path"],
        filename=file_["file_name"],
        media_type="application/octet-stream"
    )

@app.post("/files/upload/chunk")
async def upload_chunk(
    chunk: UploadFile = File(...),
    upload_id: str = Form(...),   # ties all chunks of one file together
    chunk_index: int = Form(...),
    owner_id: str = Form(...)
):
    os.makedirs(os.path.join(TEMP_DIR, upload_id), exist_ok=True)
    print(f"Received chunk {chunk_index} for upload_id {upload_id}")
    chunk_path = os.path.join(TEMP_DIR, upload_id, str(chunk_index))
    print(f"Writing to {chunk_path}")

    with open(chunk_path, "wb") as buffer:
        shutil.copyfileobj(chunk.file, buffer)

    return {"received": chunk_index}


@app.post("/files/upload/complete")
async def complete_upload(
    upload_id: str = Form(...),
    file_name: str = Form(...),
    total_chunks: int = Form(...),
    owner_id: str = Form(...)
):
    UPLOAD_DIR = os.path.join(UPLOAD_DIR_BASE, owner_id)
    os.makedirs(UPLOAD_DIR, exist_ok=True)

    file_id     = str(uuid.uuid1())
    unique_name = f"{file_id}_{file_name}"
    dest_path   = os.path.join(UPLOAD_DIR, unique_name)

    # Reassemble chunks in order
    with open(dest_path, "wb") as final:
        for i in range(total_chunks):
            chunk_path = os.path.join(TEMP_DIR, upload_id, str(i))
            with open(chunk_path, "rb") as chunk:
                shutil.copyfileobj(chunk, final)
            print(chunk_path)
    print(dest_path)
    # Cleanup temp chunks
    shutil.rmtree(os.path.join(TEMP_DIR, upload_id))

    file.add_file(file_id, file_name, owner_id, os.path.getsize(dest_path), dest_path)
    return {"message": "Upload successful", "file_name": file_name}

@app.post("/files/upload")
async def upload_file(file_: UploadFile = File(...), owner_id: str = Form(...)):
    UPLOAD_DIR = os.path.join(UPLOAD_DIR_BASE, owner_id)
    unique_name = f"{uuid.uuid1()}_{file_.filename}"
    dest_path   = os.path.join(UPLOAD_DIR, unique_name)
    file_id = str(uuid.uuid1())

    # Create Upload path if not yet existend
    if not os.path.exists(UPLOAD_DIR):
        os.makedirs(UPLOAD_DIR)

    with open(dest_path, "wb") as buffer:
        shutil.copyfileobj(file_.file, buffer)

    file.add_file(file_id, file_.filename, owner_id, os.path.getsize(dest_path), dest_path,)

    return {"message": "Upload successful", "file_name": file_.filename}

@app.delete("/files/delete/{file_id}")
def delete_file(file_id: str):
    file_ = file.get_file_by_id(file_id,)
    if file_ is None:
        return JSONResponse(status_code=404, content={"error": "File not found"})

    # Delete from disk first, then DB
    if os.path.exists(file_["file_path"]):
        os.remove(file_["file_path"])

    file.delete_file(file_id,)


    return {"message": "Deleted successfully"}

@app.get("/files/{owner_id}")
def get_files(owner_id: str):
    files = file.get_file(owner_id)
    return JSONResponse(content=files)


