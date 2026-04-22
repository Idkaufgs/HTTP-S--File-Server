import os
import shutil
import tempfile

tempdir = tempfile.gettempdir()
path = os.path.dirname(os.path.abspath(__file__))
Serverpath = path + "\\src\\Server"
FastAPIsrcpath = path + "\\src\\FastAPI"
Apachepath = "C:\\Apache24"
Webfilepath = Apachepath + "\\htdocs"
FastAPIpath = "C:\\FastAPI"
Serverlist = os.listdir(Serverpath)
FastAPIlist = os.listdir(FastAPIsrcpath)

for file in Serverlist: {
    shutil.copyfile(Serverpath + "\\" + file, tempdir + "\\" + file):
    print("Copying File: " + Serverpath + "\\" + file + " to " + tempdir + "\\" + file)
}

for file in Serverlist: {
    shutil.move(tempdir + "\\" + file, Webfilepath + "\\" + file):
    print("Replacing File: " + Webfilepath + "\\" + file + " with: " + Serverpath + "\\" + file)
}

for file in FastAPIlist: {
    shutil.copyfile(FastAPIsrcpath + "\\" + file, tempdir + "\\" + file):
    print("Copying FIle: " + FastAPIsrcpath + "\\" + file + " to " + tempdir + "\\" + file)
}

for file in FastAPIlist: {
    shutil.move(tempdir + "\\" + file, FastAPIpath + "\\" + file):
    print("Replacing File: " + FastAPIpath + "\\" + file + " with " + FastAPIsrcpath + "\\" + file)
}
