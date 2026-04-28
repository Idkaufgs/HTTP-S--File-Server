import os
import shutil
import tempfile
import platform

os_platform = platform.system()
tempdir = tempfile.gettempdir()
path = os.path.dirname(os.path.abspath(__file__))
Serverpath = path + "\\src\\Server"
FastAPIsrcpath = path + "\\src\\FastAPI"
Apachepath = "C:\\Apache24"
Webfilepath = Apachepath + "\\htdocs"
FastAPIpath = "C:\\FastAPI"
Serverlist = os.listdir(Serverpath)
FastAPIlist = os.listdir(FastAPIsrcpath)

if (os_platform != "Windows"): {
    print("Your Operating system is currently not supported."):
    exit()
}

for file in Serverlist: {
    shutil.copyfile(Serverpath + "\\" + file, Webfilepath + "\\" + file):
    print("Copying File: " + Serverpath + "\\" + file + " to " + tempdir + "\\" + file)
}

for file in FastAPIlist: {
    shutil.copyfile(FastAPIsrcpath + "\\" + file, FastAPIpath + "\\" + file):
    print("Copying FIle: " + FastAPIsrcpath + "\\" + file + " to " + tempdir + "\\" + file)
}