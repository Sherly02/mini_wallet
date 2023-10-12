# Mini Wallet | Installation Guide

## A. Preparation
### 1. Install PHP Server
Follow this guide to install PHP server on your device :
- MAC OS (https://medium.com/@rodolfovmartins/how-to-install-php-on-mac-6795ce469802) 
- Windows (https://www.geeksforgeeks.org/how-to-install-php-in-windows-10/)
- Linux (https://www.geeksforgeeks.org/how-to-install-php-on-linux/)

### 2. Clone this repository using this on your terminal / command prompt
    git clone https://github.com/Sherly02/mini_wallet.git
### 3. Install XAMPP and Setup Local Database
1) Follow this guide to install XAMPP for MAC OS, Windows and Linux : https://badoystudio.com/cara-install-xampp/
2) Start MySQL services and Apache.
If you are having trouble using mac, run this on your terminal :
 - sudo /Applications/XAMPP/xamppfiles/xampp startapache
 - sudo /Applications/XAMPP/xamppfiles/xampp startmysql
3) Open localhost/phpmyadmin in your browser
4) Create new database named mini_wallet
5) Import this database backup : https://github.com/Sherly02/mini_wallet/blob/main/mini_wallet.sql

### 4. Import Postman API Collection to your postman workspaces
https://github.com/Sherly02/mini_wallet/blob/main/Mini%20Wallet.postman_collection.json

# Run the Project

### 1. Open the mini wallet source code folder that you have cloned via terminal / command prompt
    command example => cd project/mini_wallet
### 2. Run php server inside of the mini wallet project
    php -S localhost:3000

### 3. Test API
Access localhost:3000 on your browser / postman. If it says Welcome to Mini Wallet API, then it's ready to use on postman.

## Notes :
Kindly contact me on Whatsapp (+6281553236791) or Skype (live:.cid.9810b85c2b566b7c) if you're having any issues with the installation.