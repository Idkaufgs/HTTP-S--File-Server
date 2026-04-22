from DBParser import user, file as file_db
from argon2 import PasswordHasher
from fastapi import FastAPI, UploadFile, File, Form, Request
from fastapi.responses import JSONResponse, FileResponse
from pydantic import BaseModel
from pathlib import Path
from collections import defaultdict
import uuid, os, shutil, time, secrets, string

Hash = PasswordHasher(time_cost=3, memory_cost=64*1024, parallelism=2)

app = FastAPI()
user.migrate()  # run the migrate call for the DB (potentially usefull in the futur so keep)

TEMP_DIR        = Path("C:/Files/temp")
UPLOAD_DIR_BASE = Path("C:/Files")
ONE_HUNDRED_GB  = 107_374_182_400

if not os.path.exists(UPLOAD_DIR_BASE):
    os.mkdir(UPLOAD_DIR_BASE)
    os.mkdir(TEMP_DIR)

## Rate limit
rate_limit_store: dict[str, list[float]] = defaultdict(list)
RATE_WINDOW = 60

def is_rate_limited(ip: str, max_attempts: int) -> bool:
    now    = time.time()
    window = now - RATE_WINDOW
    rate_limit_store[ip] = [t for t in rate_limit_store[ip] if t > window]
    if len(rate_limit_store[ip]) >= max_attempts:
        return True
    rate_limit_store[ip].append(now)
    return False

@app.middleware("http")
async def admin_localhost_only(request: Request, call_next):
    if request.url.path.startswith("/admin"):
        if request.client.host not in ("127.0.0.1", "::1"):
            return JSONResponse(status_code=403, content={"error": "Forbidden"})
    return await call_next(request)

def generate_temp_password(length: int = 16) -> str:
    alphabet = string.ascii_letters + string.digits + "!@#$%^&*"
    return ''.join(secrets.choice(alphabet) for _ in range(length))

def get_max_allowed_limit() -> int:
    """Max a user can be assigned: min(20% of disk, 100GB)."""
    usage = shutil.disk_usage(str(UPLOAD_DIR_BASE))
    return min(int(usage.total * 0.2), ONE_HUNDRED_GB)

def format_bytes(size: int) -> str:
    for unit in ('B', 'KB', 'MB', 'GB', 'TB'):
        if size < 1024:
            return f"{size:.2f} {unit}"
        size /= 1024
    return f"{size:.2f} TB"

class LoginRequest(BaseModel):
    username: str
    password: str

class CreateUserRequest(BaseModel):
    uname: str
    pwd:   str

class ChangePasswordRequest(BaseModel):
    user_id:      str
    old_password: str
    new_password: str

class AdminCreateUserRequest(BaseModel):
    uname:         str
    permission:    str  = 'user'
    storage_limit: int  = 1073741824  # default 1GB

class AdminForceChangeRequest(BaseModel):
    user_id:      str
    new_password: str | None = None

class AdminSetLimitRequest(BaseModel):
    storage_limit: int

## Authentication Enpoints
@app.post("/auth/login")
def login(data: LoginRequest, request: Request):
    if is_rate_limited(request.client.host, 10):
        return JSONResponse(status_code=429, content={"success": False, "detail": "Too many attempts."})

    hash_ = user.get_password_hash(data.username)
    if hash_ is None:
        return {"success": False, "detail": "Invalid username or password!"}

    try:
        Hash.verify(hash_, data.password)
    except Exception:
        return {"success": False, "detail": "Invalid username or password!"}

    user_id      = user.get_user_id(data.username)
    force_change = user.get_force_password_change(user_id)
    permission   = user.get_user_permission(data.username)
    perm_value   = permission[0] if permission else 'user'

    return {
        "success":               True,
        "user_id":               user_id,
        "permission":            perm_value,
        "force_password_change": force_change,
    }


@app.post("/auth/create_account")
def create_account(data: CreateUserRequest, request: Request):
    if is_rate_limited(request.client.host, 5):
        return JSONResponse(status_code=429, content={"success": False, "detail": "Too many attempts."})

    if user.get_user(data.uname):
        return {"success": False, "detail": "Username already taken."}

    pwd_hash = Hash.hash(data.pwd)
    user_id  = str(uuid.uuid5(uuid.NAMESPACE_DNS, data.uname))
    user.add_user(data.uname, pwd_hash, user_id, 'user')
    return {"success": True, "user_id": user_id}


@app.post("/auth/change_password")
def change_password(data: ChangePasswordRequest):
    row = user.get_user_by_id(data.user_id)
    if row is None:
        return JSONResponse(status_code=404, content={"success": False, "detail": "User not found."})

    username = row[0]
    hash_    = user.get_password_hash(username)

    try:
        Hash.verify(hash_, data.old_password)
    except Exception:
        return {"success": False, "detail": "Current password is incorrect."}

    if data.old_password == data.new_password:
        return {"success": False, "detail": "New password must differ from current password."}

    user.update_password_by_id(data.user_id, Hash.hash(data.new_password))
    user.set_force_password_change(data.user_id, False)
    return {"success": True}

## Admin Endpoints
@app.get("/admin/users")
def admin_get_users():
    users = user.get_all_users()
    for u in users:
        usage = file_db.get_disk_usage(u['user_id'])
        u['disk_usage']        = usage
        u['disk_usage_fmt']    = format_bytes(usage)
        u['storage_limit_fmt'] = format_bytes(u['storage_limit'])
    return users


@app.get("/admin/user/{target_user_id}")
def admin_get_user(target_user_id: str):
    row = user.get_user_by_id(target_user_id)
    if row is None:
        return JSONResponse(status_code=404, content={"error": "User not found."})

    username  = row[0]
    u         = user.get_user(username)
    usage     = file_db.get_disk_usage(target_user_id)
    files     = file_db.get_file(target_user_id)
    limit     = user.get_storage_limit(target_user_id)

    return {
        "username":            username,
        "user_id":             target_user_id,
        "permission":          u[4],
        "force_password_change": bool(u[5]),
        "storage_limit":       limit,
        "storage_limit_fmt":   format_bytes(limit),
        "disk_usage":          usage,
        "disk_usage_fmt":      format_bytes(usage),
        "file_count":          len(files),
        "max_allowed_limit":   get_max_allowed_limit(),
        "max_allowed_fmt":     format_bytes(get_max_allowed_limit()),
    }


@app.post("/admin/create_account")
def admin_create_account(data: AdminCreateUserRequest):
    if user.get_user(data.uname):
        return {"success": False, "detail": "Username already taken."}

    max_limit = get_max_allowed_limit()
    limit     = min(data.storage_limit, max_limit)

    temp_password = generate_temp_password()
    pwd_hash      = Hash.hash(temp_password)
    user_id       = str(uuid.uuid5(uuid.NAMESPACE_DNS, data.uname))

    user.add_user(data.uname, pwd_hash, user_id, data.permission,
                  force_password_change=1, storage_limit=limit)

    return {
        "success":       True,
        "user_id":       user_id,
        "temp_password": temp_password,
        "permission":    data.permission,
        "storage_limit": limit,
    }


@app.post("/admin/force_password_change/{target_user_id}")
def admin_force_password_change(target_user_id: str, data: AdminForceChangeRequest):
    row = user.get_user_by_id(target_user_id)
    if row is None:
        return JSONResponse(status_code=404, content={"success": False, "detail": "User not found."})

    username      = row[0]
    temp_password = data.new_password if data.new_password else generate_temp_password()
    user.update_password(username, Hash.hash(temp_password))
    user.set_force_password_change(target_user_id, True)
    return {"success": True, "temp_password": temp_password}


@app.post("/admin/set_storage_limit/{target_user_id}")
def admin_set_storage_limit(target_user_id: str, data: AdminSetLimitRequest):
    row = user.get_user_by_id(target_user_id)
    if row is None:
        return JSONResponse(status_code=404, content={"error": "User not found."})

    max_limit = get_max_allowed_limit()
    if data.storage_limit > max_limit:
        return JSONResponse(status_code=400, content={
            "error": f"Limit exceeds maximum allowed ({format_bytes(max_limit)})."
        })

    user.set_storage_limit(target_user_id, data.storage_limit)
    return {"success": True, "storage_limit": data.storage_limit}


@app.delete("/admin/delete_user/{target_user_id}")
def admin_delete_user(target_user_id: str):
    row = user.get_user_by_id(target_user_id)
    if row is None:
        return JSONResponse(status_code=404, content={"error": "User not found."})

    # Delete DB records first, then nuke the whole user directory
    file_db.delete_all_user_files(target_user_id)

    user_dir = UPLOAD_DIR_BASE / target_user_id
    if user_dir.exists():
        shutil.rmtree(str(user_dir))

    user.delete_user_by_id(target_user_id)
    return {"success": True}

## File Management Endpoints
def check_storage(owner_id: str, incoming_bytes: int) -> bool:
    """Returns True if the upload fits within the user's limit."""
    usage = file_db.get_disk_usage(owner_id)
    limit = user.get_storage_limit(owner_id)
    return (usage + incoming_bytes) <= limit


@app.get("/files/download/{file_id}")
def download_files(file_id: str):
    file_ = file_db.get_file_by_id(file_id)
    if file_ is None:
        return JSONResponse(status_code=404, content={"error": "File not found"})
    if not os.path.exists(file_["file_path"]):
        return JSONResponse(status_code=410, content={"error": "File missing from directory"})
    return FileResponse(
        path=file_["file_path"],
        filename=file_["file_name"],
        media_type="application/octet-stream"
    )

@app.get("/files/upload/validate_available_storage/{owner_id}")
def validate_available_storage(owner_id: str):
    available_storage = user.get_storage_limit(owner_id)
    if available_storage is None:
        if user.get_user_by_id(owner_id) is None:
            return JSONResponse(status_code=404, content={"error": "User not found"})
        return JSONResponse(status_code=404, content={"error": "Could not fetch available Storage"})
    return JSONResponse(status_code=200, content={available_storage})


@app.post("/files/upload")
async def upload_file(file: UploadFile = File(...), owner_id: str = Form(...)):
    # Read into memory to get size before writing
    data = await file.read()
    if not check_storage(owner_id, len(data)):
        return JSONResponse(status_code=413, content={"error": "Storage limit exceeded."})

    upload_dir = UPLOAD_DIR_BASE / owner_id
    upload_dir.mkdir(parents=True, exist_ok=True)

    file_id     = str(uuid.uuid1())
    dest_path   = upload_dir / f"{file_id}_{file.filename}"

    with open(dest_path, "wb") as buf:
        buf.write(data)

    file_db.add_file(file_id, file.filename, owner_id, dest_path.stat().st_size, str(dest_path))
    return {"message": "Upload successful", "file_name": file.filename}


@app.post("/files/upload/chunk")
async def upload_chunk(
    chunk: UploadFile = File(...),
    upload_id: str    = Form(...),
    chunk_index: int  = Form(...),
    owner_id: str     = Form(...)
):
    chunk_dir = TEMP_DIR / upload_id
    chunk_dir.mkdir(parents=True, exist_ok=True)
    chunk_path = chunk_dir / str(chunk_index)

    with open(chunk_path, "wb") as buffer:
        shutil.copyfileobj(chunk.file, buffer)

    return {"received": chunk_index}


@app.post("/files/upload/complete")
async def complete_upload(
    upload_id:    str = Form(...),
    file_name:    str = Form(...),
    total_chunks: int = Form(...),
    owner_id:     str = Form(...)
):
    # Calculate total size from chunks before committing
    chunk_dir  = TEMP_DIR / upload_id
    total_size = sum((chunk_dir / str(i)).stat().st_size
                     for i in range(total_chunks)
                     if (chunk_dir / str(i)).exists())

    if not check_storage(owner_id, total_size):
        shutil.rmtree(str(chunk_dir), ignore_errors=True)
        return JSONResponse(status_code=413, content={"error": "Storage limit exceeded."})

    upload_dir = UPLOAD_DIR_BASE / owner_id
    upload_dir.mkdir(parents=True, exist_ok=True)

    file_id   = str(uuid.uuid1())
    dest_path = upload_dir / f"{file_id}_{file_name}"

    with open(dest_path, "wb") as final:
        for i in range(total_chunks):
            chunk_path = chunk_dir / str(i)
            if not chunk_path.exists():
                return JSONResponse(status_code=500, content={"error": f"Missing chunk {i}"})
            with open(chunk_path, "rb") as chunk:
                shutil.copyfileobj(chunk, final)

    shutil.rmtree(str(chunk_dir))
    file_db.add_file(file_id, file_name, owner_id, dest_path.stat().st_size, str(dest_path))
    return {"message": "Upload successful", "file_name": file_name}


@app.delete("/files/delete/{file_id}")
def delete_file(file_id: str):
    file_ = file_db.get_file_by_id(file_id)
    if file_ is None:
        return JSONResponse(status_code=404, content={"error": "File not found"})
    if os.path.exists(file_["file_path"]):
        os.remove(file_["file_path"])
    file_db.delete_file(file_id)
    return {"message": "Deleted successfully"}


@app.get("/files/{owner_id}")
def get_files(owner_id: str):
    files = file_db.get_file(owner_id)
    return JSONResponse(content=files)