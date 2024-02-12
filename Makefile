.PHONY = install
install :
	sudo mkdir /etc/backup
	sudo cp backup.conf.dist /etc/backup
	sudo cp data.dist /etc/backup
	sudo cp backup /usr/local/bin
	sudo cp Archive.class.php /usr/share/php
	sudo chmod +x /usr/local/bin/backup

.PHONY = update
update :
	git pull
	sudo cp Archive.class.php /usr/share/php
	sudo cp backup /usr/local/bin

.PHONY = clean
clean :
	sudo rm -rf /etc/backup
	sudo rm /usr/share/php/Archive.class.php
	sudo rm /usr/local/bin/backup
