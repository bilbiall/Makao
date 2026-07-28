# 🏘️ Renty Tenant Management System

**Renty** is a modern Laravel-based tenant management system built with [Filament](https://filamentphp.com/). Designed for landlords and property managers, it simplifies remote property management, communication, and tracking, all in one sleek platform.

---

## 🚀 Features

- 🧑‍💼 **Tenant Portal**
  - View and download invoices
  - Track rent payment history
  - Submit and monitor maintenance issues
- 📊 **Admin Panel**
  - Manage tenants, units, issues, and payments
- 🛠️ **Issue Tracker**
  - Tenants can log issues and view real-time status updates
- 💬 **SMS Notifications**
  - Automatic rent reminders, confirmations, and overdue alerts
  - Prevents Fraud by having a dedicated shortcode for your apartments
- 📅 **Payment Tracking**
  - View what’s paid, pending, or overdue at a glance
- 🧾 **Invoices**
  - Generate and send downloadable rental invoices
- 🎨 **Clean UI**
  - Built with Filament Panels for elegant and intuitive UX
- 🌐 **Multi-Panel Architecture**
  - Fully separated admin and tenant environments
- 🔒 **Secure Authentication**
  - Role-based login and access

---

## 🛠️ Tech Stack

- **Backend:** Laravel 10+ (PHP 8.3)
- **Frontend:** Filament Admin Panel
- **Database:** MySQL / MariaDB
- **Notifications:** Laravel Notifications + SMS (Africa's Talking, TextSMS, or Twilio)
- **Hosting:** Laravel-compatible shared hosting or VPS

---

## ⚙️ Installation Guide

### 📁 1. Clone or Download

```bash
git clone https://github.com/bilbiall/renty-tenant-management.git
cd renty-tenant-management
```
##Alternatively, download the .zip and extract it to your working directory.

📦 2. Install Dependencies
```bash
composer install
npm install && npm run build
```

⚙️ 3. Environment Setup


Rename .env.example to .env
Update .env with your:
-Database credentials
-Mail settings
-SMS gateway credentials

Now generate a key for your project
```
cp .env.example .env
php artisan key:generate
```


🧱 4. Run Migrations & Seeders
```
php artisan migrate --seed
```
This will create the required tables and seed default demo users.

👤 5. To add a test admin user we shall use tinker
First initiate tinker
```
php artisan tinker
```
To have demo users to your database eg. admin, run the code below
```
use App\Models\User;

User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'phone_number' => '0700000000',
    'email_verified_at' => now(),
    'password' => bcrypt('Admin@123'),
    'role' => 'admin',
]);

```
this will create an admin user with the details below, for a caretaker role just change the 'role' value above 
```
Login Credentials Created

Email: admin@example.com

Password: Admin@123

Role: admin
```


🔌 6. Serve the App
```
php artisan serve
```
Visit ```http://127.0.0.1:8000``` in your browser.



👥 Demo Login Credentials
Demo Link: ```https://renty.thelearninghub.co.ke```

Admin Panel

    Email: admin@example.com

    Password: 123456

Tenant Panel

    Email: tenant@example.com

    Password: 123456
📸 Screenshots

🏠 Landing Page

<img width="1314" height="674" alt="Landing Page" src="https://github.com/user-attachments/assets/1f22ef09-1bac-4aa8-b4a0-2ffa15c143c6" />

📊 Dashboard

<img width="1313" height="681" alt="Dashboard" src="https://github.com/user-attachments/assets/25b3408c-0c56-4976-8b20-3767319153d5" />

Houses

<img width="1293" height="678" alt="image" src="https://github.com/user-attachments/assets/6e15d42d-a54a-4aee-9f9a-3e832a1d7b2b" />

Create Tenant Accounts for them to login

<img width="1318" height="636" alt="image" src="https://github.com/user-attachments/assets/1acdb901-74be-4939-b58e-7ad16f125723" />


Bills

<img width="1318" height="674" alt="image" src="https://github.com/user-attachments/assets/a53af4cf-b317-402d-8711-a4228cda863b" />

Invoices

<img width="1274" height="662" alt="image" src="https://github.com/user-attachments/assets/83b1ae26-1dba-48cd-8cd5-5aca6f7289dc" />

Payments
<img width="1312" height="668" alt="image" src="https://github.com/user-attachments/assets/2fc3d782-fe77-40c5-8276-9130cd355f4d" />

Issues/Maintenance

<img width="1315" height="660" alt="image" src="https://github.com/user-attachments/assets/a049a148-0af1-4ca4-83a3-3d3c569f8d9f" />

SMS Notifications from dedicated shortcode
![Untitled](https://github.com/user-attachments/assets/c89b064d-3555-470f-85e2-9d824f12fdb0)

![Untitled](https://github.com/user-attachments/assets/65967aa1-528b-476b-97a2-39645780e1f4)





🤝 Contributing

Pull requests are welcome! For major changes, please open an issue first to propose what you'd like to change or add.
📄 License

This project is licensed under the MIT License.
📬 Contact

Want to collaborate or deploy Renty for your property management?

    GitHub: @bilbiall

    Email: billyngare911@gmail.com

    Demo: https://renty.thelearninghub.co.ke

Made with ❤️ in Kenya


