set datestamp=%DATE:~11%-%DATE:~7,2%-%DATE:~3,2%
set datestamp=%datestamp: =0%
set timestamp=%TIME:~0,2%-%TIME:~3,2%
set timestamp=%timestamp: =0%

C:\wamp\bin\mysql\mysql5.6.17\bin\mysqldump.exe --force --opt --user=ztp --password=Instar123 ztp-ostra > C:\Users\dhe\OneDrive\ztp-backup\ztp-ostra-%datestamp%-%timestamp%.sql