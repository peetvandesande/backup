.PHONY = install
install :
	sudo mkdir /etc/backup
	sudo cp backup.conf.dist /etc/backup
	sudo cp data.dist /etc/backup
	sudo cp backup /usr/local/bin
	sudo chmod +x /usr/local/bin/backup

.PHONY = update
update :
	git pull
	sudo cp backup /usr/local/bin

.PHONY = clean
clean :
	sudo rm -rf /etc/backup
	sudo rm /usr/local/bin/backup
