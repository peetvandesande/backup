install:
	mkdir /etc/backup
	cp backup.conf.dist /etc/backup
	cp data /etc/backup
	cp backup /usr/local/bin
	chmod +x /usr/local/bin/backup
