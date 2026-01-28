#!/bin/bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS simpleakunting_eska;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'eska'@'localhost' IDENTIFIED BY '5@8@12Yaa';"
sudo mysql -e "GRANT ALL PRIVILEGES ON simpleakunting_eska.* TO 'eska'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
echo "Database setup complete."

