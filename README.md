# 📚 Tuition Class Management System
### SQL Server Edition — Setup Guide for Beginners

This guide will walk you through **every step** needed to set up and run this PHP web application on your computer using **XAMPP** and **Microsoft SQL Server (SSMS)**. Follow each step carefully and in order.

---

## 🧰 What You Will Need (Prerequisites)

Before you start, make sure you have these installed on your computer:

| Software | What it does |
|----------|--------------|
| **XAMPP** | Runs PHP and Apache on your local computer |
| **SQL Server Express** | The database engine (comes with SSMS) |
| **SQL Server Management Studio (SSMS)** | The tool you use to manage your database |

> ⚠️ **Important:** XAMPP does **not** support SQL Server by default. You need to install extra drivers (called PHP SQLSRV Drivers) before anything will work. The steps below will guide you through this.

---

## 🔧 Part 1 — Install the Required Drivers

### Step 1: Install the Microsoft ODBC Driver

The ODBC Driver allows PHP to "talk" to SQL Server.

1. Open your web browser and go to the official Microsoft website.
2. Search for **"Microsoft ODBC Driver for SQL Server download"**.
3. Download the **x64** version (this is for 64-bit Windows, which most computers use today).
4. Run the downloaded `.msi` installer and follow the on-screen instructions to complete the installation.

---

### Step 2: Find Out Your PHP Version

You need to know your PHP version so you can download the correct driver.

1. Open the **XAMPP Control Panel**.
2. Click the **"phpinfo"** button at the top right of the control panel.

   > If you don't see this button, open your browser and go to: `http://localhost/dashboard/phpinfo.php`

3. A page will open. Look near the top for a line that says something like:
   ```
   PHP Version 8.2.x  or  PHP Version 8.3.x
   ```
4. **Write down your PHP version number** (e.g., `8.2`). You will need it in the next step.

---

### Step 3: Download the PHP SQLSRV Driver Files

1. Go to the Microsoft website and search for **"Microsoft Drivers for PHP for SQL Server download"**.
2. On the download page, find the version that **matches your PHP version** (e.g., if your PHP is `8.2`, download the driver for `PHP 8.2`).
3. Download the `.zip` file and extract/unzip it to a folder on your Desktop.
4. Inside the extracted folder, you will see many `.dll` files. You need to find **exactly these two files**:
   ```
   php_sqlsrv_XX_ts_x64.dll
   php_pdo_sqlsrv_XX_ts_x64.dll
   ```
   > 📝 **Note:** Replace `XX` with your PHP version digits. For example, if your PHP is `8.2`, the files will be named `php_sqlsrv_82_ts_x64.dll` and `php_pdo_sqlsrv_82_ts_x64.dll`.

   > ✅ Make sure you pick the files with **`_ts_x64`** in the name. The `ts` means "Thread Safe" and `x64` means 64-bit. These are the correct ones for XAMPP.

5. **Copy** both `.dll` files.
6. **Paste** them into this folder on your computer:
   ```
   C:\xampp\php\ext\
   ```

---

### Step 4: Enable the Drivers in XAMPP (Editing php.ini)

Now you need to tell XAMPP to actually use the drivers you just installed.

1. Open the **XAMPP Control Panel**.
2. Find **Apache** in the list and click the **"Config"** button next to it.
3. A small menu will appear. Click on **"PHP (php.ini)"**. This will open a text file in Notepad.
4. Press **Ctrl + F** to open the search box and search for the word:
   ```
   extension=
   ```
5. Scroll to find the section that lists other extensions. Add these **two new lines** at the end of that section:
   ```
   extension=php_sqlsrv_XX_ts_x64.dll
   extension=php_pdo_sqlsrv_XX_ts_x64.dll
   ```
   > 📝 **Remember:** Replace `XX` with your actual PHP version digits (e.g., `82` for PHP 8.2).

   **Example (if your PHP version is 8.2):**
   ```
   extension=php_sqlsrv_82_ts_x64.dll
   extension=php_pdo_sqlsrv_82_ts_x64.dll
   ```

6. Press **Ctrl + S** to save the file, then close Notepad.
7. Go back to the XAMPP Control Panel and click **"Stop"** then **"Start"** next to Apache to restart it.

> ✅ The drivers are now installed and enabled!

---

## 📁 Part 2 — Set Up the Project Files

### Step 5: Move the Project Folder to htdocs

The `htdocs` folder is where XAMPP looks for websites on your computer.

1. Find the project folder named **`tutionManagementSystem`** (this was given to you).
2. Move or copy the entire folder to:
   ```
   C:\xampp\htdocs\
   ```
3. After this step, the path should look like:
   ```
   C:\xampp\htdocs\tutionManagementSystem\
   ```

---

### Step 6: Find Your SQL Server Name

You need your computer's SQL Server name to connect the project to your database.

1. Open **SQL Server Management Studio (SSMS)**.
2. When it opens, a **"Connect to Server"** login box will appear immediately.
3. Look at the **"Server name:"** field at the top of that box.
4. It will show something like one of these examples:
   - `LAPTOP-XXXXX\SQLEXPRESS`
   - `DESKTOP-XXXXX\SQLEXPRESS`
   - `localhost\SQLEXPRESS`
   - `localhost`
5. **Write down or copy** whatever is shown there — this is your Server Name.

---

### Step 7: Configure the Database Connection (db.php)

Now you will tell the project how to connect to your SQL Server.

1. Open the project folder at `C:\xampp\htdocs\tutionManagementSystem\`.
2. Find the file named **`db.php`** and open it with **Notepad** (right-click → Open with → Notepad).
3. You will see this code:
   ```php
   <?php
   $serverName = "YOUR_COMPUTER_NAME\SQLEXPRESS";
   $connectionInfo = array(
       "Database" => "tution_db",
       "UID" => "",
       "PWD" => ""
   );

   $conn = sqlsrv_connect($serverName, $connectionInfo);

   if ($conn === false) {
       die(print_r(sqlsrv_errors(), true));
   }
   ?>
   ```
4. Replace `YOUR_COMPUTER_NAME\SQLEXPRESS` with the **Server Name** you found in Step 6.

   **Example:** If your server name is `LAPTOP-ABC123\SQLEXPRESS`, the line should look like:
   ```php
   $serverName = "LAPTOP-ABC123\SQLEXPRESS";
   ```
5. The `UID` and `PWD` fields can stay **empty** if you are using **Windows Authentication** (which is the default for most local setups).
6. Save the file (**Ctrl + S**) and close Notepad.

---

## 🗄️ Part 3 — Set Up the Database in SSMS

### Step 8: Create a New Database

1. Open **SQL Server Management Studio (SSMS)** and connect to your server.
2. On the left side, you will see a panel called **"Object Explorer"**.
3. Right-click on **"Databases"** and select **"New Database..."**.
4. In the box that appears, type the database name exactly as it is written in your `db.php` file:
   ```
   tution_db
   ```
5. Click **OK**. You will now see `tution_db` appear in the Databases list.

---

### Step 9: Run the SQL Script to Create the Tables

The project comes with a `.sql` script file that automatically creates all the required tables.

1. Open your project folder at `C:\xampp\htdocs\tutionManagementSystem\`.
2. Find the file ending in **`.sql`** (it may be named something like `tution_db.sql` or `database.sql`).
3. Open it with **Notepad** and **select all** the text (Ctrl + A), then **copy** it (Ctrl + C).
4. Go back to **SSMS**. Click on **"New Query"** button at the top.
5. Make sure the dropdown at the top shows **`tution_db`** as the selected database.
6. **Paste** the copied SQL code into the query window (Ctrl + V).
7. Click the **"Execute"** button (or press **F5**).
8. You should see a message saying **"Command(s) completed successfully"** at the bottom.

> ✅ All the tables are now created in your database!

---

## ▶️ Part 4 — Run the Application

### Step 10: Start Apache in XAMPP

1. Open the **XAMPP Control Panel**.
2. Click **"Start"** next to **Apache**.
3. The status should turn **green**.

> 🛑 You do **NOT** need to start MySQL — this project uses SQL Server (SSMS), not MySQL.

---

### Step 11: Open the Application in Your Browser

1. Open any web browser (Chrome, Firefox, Edge, etc.).
2. In the address bar, type:
   ```
   http://localhost/tutionManagementSystem/
   ```
3. Press **Enter**.

> 🎉 The Tuition Class Management System should now load and be ready to use!

---

## ❓ Troubleshooting Common Problems

| Problem | What to check |
|--------|---------------|
| Page shows a blank screen or PHP errors | Make sure Apache is started in XAMPP |
| "Could not connect to SQL Server" error | Double-check your Server Name in `db.php` (Step 7) |
| PHP errors about `sqlsrv` not found | Make sure the `.dll` files are in `C:\xampp\php\ext\` and the lines are added to `php.ini` (Steps 3–4) |
| Tables don't exist error | Make sure you ran the `.sql` script in SSMS (Step 9) |
| Apache won't start | Another program may be using Port 80. Try changing the Apache port in XAMPP Config. |

---

## 📌 Quick Summary Checklist

Use this checklist to make sure you have done everything:

- [ ] Installed Microsoft ODBC Driver (Step 1)
- [ ] Checked PHP version in XAMPP (Step 2)
- [ ] Downloaded and copied the two `.dll` driver files to `C:\xampp\php\ext\` (Step 3)
- [ ] Added the two `extension=` lines to `php.ini` and restarted Apache (Step 4)
- [ ] Moved the project folder to `C:\xampp\htdocs\` (Step 5)
- [ ] Found your SQL Server Name from SSMS (Step 6)
- [ ] Updated `db.php` with your Server Name (Step 7)
- [ ] Created the `tution_db` database in SSMS (Step 8)
- [ ] Ran the `.sql` script to create all the tables (Step 9)
- [ ] Started Apache in XAMPP (Step 10)
- [ ] Opened `http://localhost/tutionManagementSystem/` in your browser (Step 11)

---

