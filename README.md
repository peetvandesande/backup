# backup

Grant the required permissions to the backup user  
<code>GRANT SELECT, LOCK TABLES, SHOW VIEW ON *.* TO `backup`@`localhost`;</code>

To store backup remotely using rsync, enable the remote backup account and give it a shell:  
<code>usermod -s /bin/bash backup</code>
