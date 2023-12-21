.PHONY = install
install :
	mkdir /etc/backup
	cp backup.conf.dist /etc/backup
	cp data /etc/backup
	cp backup /usr/local/bin
	chmod +x /usr/local/bin/backup

.PHONY = update
update :
	cp backup /usr/local/bin

.PHONY = clean
clean :
	rm -rf /etc/backup
	rm /usr/local/bin/backup
