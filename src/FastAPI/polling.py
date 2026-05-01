import os
from pathlib import Path
from DBParser import file as file_db
import sched, time

Upload_folder = ("C:/Files")
Upload_path = Path(Upload_folder)
Folder = os.listdir(Upload_path)
File_list = []
File_db_list = file_db.get_files()

def DB_check(scheduler):
    scheduler.enter(600, 1, DB_check, (scheduler))
    for folder in Folder:
        if folder == "temp":
            continue
        folder_path = Path("C:/Files" + "/" + folder)
        File = os.listdir(folder_path)
        for file in File:
            File_list.append(file)

    if sorted(File_list) == sorted(File_db_list):
        print("Databse list is equal to on Disk files.")
        exit()
    else:
        print("Uh oh. The Database and the on Disk files are out of sync.")
        ## implement correction methods later on.
    
my_scheduler = sched.scheduler(time.time, time.sleep)
my_scheduler.enter(600, 1, DB_check, (my_scheduler, ))
my_scheduler.run()