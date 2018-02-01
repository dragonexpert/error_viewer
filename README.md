# Error Viewer
A plugin for MyBB that allows you to view the error log without logging into FTP.

**Installation**  
1) Upload the zip file to your forum directory.  
2) Unzip the file and everything should go to the right place automatically.  
3) If you changed the admin directory, make sure you move /admin/tools/error_viewer.php to the correct directory.  

**Permissions**  
By default only the super admin(s) are able to view the error log since it contains sensitive information.  You are able to change who can view it by going to Users & Groups -> Admin Permissions -> Tools & Maintenance.  

**Accessing**  
You can access the error log by clicking on the Tools & Maintenance tab and then selecting Error Log.  At this time MyBB doesn't log which file caused an error if it is an SQL error.
