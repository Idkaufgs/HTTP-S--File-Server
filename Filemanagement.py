from fastapi import FastAPI, HTTPException,UploadFile,Depends, File
from fastapi.responses import StreamingResponse
import sys,uuid,os,shutil
from DBParser import get_file, add_file, get_user_permission

file_path = sys.argv[1] if len(sys.argv) > 1 else None
userid = sys.argv[3] if len (sys.argv) > 3 else None

tmp_up_dir = "/tmp/uploads"
tmp_dw_dir = "/tmp/downloads"
final_dir = "/data/files"

app = FastAPI()

os.makedirs(tmp_up_dir, exist_ok=True)
os.makedirs(tmp_dw_dir, exist_ok=True)

@app.post("/upload/")
async def Upload_File(
    file: UploadFile = File(...),
    user: str = Depends(get_current_user()) # type: ignore
):
    temp_file_path = os.path.join(tmp_up_dir, str(uuid.uuid4()))

    with open(temp_file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    final_file_path = os.path.join(final_dir, file.filename) #type: ignore
    shutil.move(temp_file_path, final_file_path)

    file_size = os.path.getsize(final_file_path)
    file_type = file.content_type

    add_file(file.filename, user, file_size, file_type, final_file_path)

    return {"filename": file.filename, "status": "uploaded"}

@app.get("/download/")
async def Download_File(
    file_path: str,
    user: str = Depends(get_current_user()) # type: ignore
):
    file_metadata = get_file(file_path)

    if not file_metadata:
        raise HTTPException(status_code=404)
    
    if file_metadata["ownerid"] != user:
        raise HTTPException(status_code=403)

    download_path = os.path.join(file_path, file_metadata["file_name"])

    if not os.path.exists(download_path):
        raise HTTPException(status_code=404)

    def iterfile():
        with open(download_path, mode="rb") as file_like:
            yield from file_like

    return StreamingResponse(iterfile(), media_type="application/octet-stream")
