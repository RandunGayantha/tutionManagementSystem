# Tuition Class Management System (SQL Server Edition)

This guide will help you set up and run this PHP application using XAMPP and Microsoft SQL Server (SSMS).

---

## Prerequisites

Because XAMPP does not support SQL Server out of the box, you must install the official Microsoft PHP Drivers first.

### 1. Download & Install ODBC Driver
* Download and install the **Microsoft ODBC Driver for SQL Server** (x64) from the official Microsoft website.

### 2. Download PHP SQLSRV Drivers
1. Check your PHP version (Open XAMPP, click **phpinfo** at the top right). It should show something like `PHP 8.2.x` or `PHP 8.3.x`.
2. Go to Microsoft's website and download the **Microsoft Drivers for PHP for SQL Server** that matches your exact PHP version.
3. Extract the downloaded `.zip` file. Find these two specific `.dll` files (Make sure to pick the **`_ts_x64`** (Thread Safe) versions):
   * `php_sqlsrv_XX_ts_x64.dll`
   * `php_pdo_sqlsrv_XX_ts_x64.dll`
   
   *(Note: 'XX' represents your PHP version, like 82 for PHP 8.2)*
4. Copy both `.dll` files and paste them inside your XAMPP folder at:  
   `C:\xampp\php\ext\`

### 3. Enable the Drivers in XAMPP
1. Open the **XAMPP Control Panel** and click the **Config** button next to **Apache**, then select **PHP (php.ini)**.
2. Scroll down to the extensions section and add these two lines (replace `XX` with your version number):
```text
   extension=php_sqlsrv_XX_ts_x64.dll
   extension=php_pdo_sqlsrv_XX_ts_x64.dll

3.Restart Apache in the XAMPP Control Panel to apply changes.



