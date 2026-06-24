# My Campus LMS

My Campus LMS is one of the core modules of **My Campus Hub**, the main school management system.  
It focuses on three key areas:

1. **Learning Management System (LMS)** – for managing courses, lessons, and student progress.
2. **Enrollment System** – for handling student registration and records.
3. **Landing Page** – public-facing website with information and links.

This project is built with **Laravel (PHP Framework)** using the **Laravel Breeze (Blade)** starter kit for authentication and UI scaffolding.

---

## Requirements

-   PHP >= 8.2
-   Composer
-   MySQL or MariaDB
-   Git
-   Node.js >= 18 and npm or yarn
    > **Tip:** Install nvm (Node Version Manager: [main](https://github.com/nvm-sh/nvm) | [windows](https://github.com/coreybutler/nvm-windows)) if not yet installed, to easily manage Node.js versions.  
    > Use the latest LTS version of Node.js for best compatibility.
    >
    > ```bash
    > nvm install lts
    > nvm use lts
    > ```

---

## Installation

1. **Clone the Repository**

    ```bash
    git clone https://github.com/michael090/MyCampusLMS.git
    cd MyCampusLMS
    ```

2. **Install Dependencies**

    ```bash
    composer install
    npm install && npm run build
    ```

3. **Environment Setup**  
   Copy `.env.example` to `.env`:

    ```bash
    cp .env.example .env
    ```

4. **Generate App Key**

    ```bash
    php artisan key:generate
    ```

5. **Run Migrations**

    ```bash
    php artisan migrate --seed
    ```

6. **Create Storage Link**  
   This will create a public storage symbolic link to public folder.

    ```bash
    php artisan storage:link

    # now you can access storage files through public.
    # ex: <img src="http://127.0.0.1:8000/storage/profile_photos/img_12345.jpg" />
    # or safer: <img src="{{ asset('storage/profile_photos/img_12345.jpg') }}" />
    ```

7. **Serve the Application**

    ```bash
    php artisan serve

    # visit http://127.0.0.1:8000
    # or http://MyCampusLMS.test if using herd or laragon (I recommend using laragon instead of xampp)
    ```

## Project Structure

-   `/app` – Application logic (models, controllers).
-   `/resources/views` – Blade templates for UI.
-   `/routes/web.php` – Web routes.
-   `/database/migrations` – Database schema.

## Features To Be Aware Of

-   **Enum** – constants set
-   **FormRequest** – validation & authorization
-   **Notification** – multi-channel alerts
-   **Mail** – custom email sending
-   **Middleware** – request filters
-   **Migration** – database schema versioning
-   **Resource** – API response formatting

## Recommended Packages

-   **spatie/laravel-permission** – Associate users with roles and permissions.
-   **spatie/laravel-activitylog** – Log users activity inside your Laravel app.
-   **simplesoftwareio/simple-qrcode** – Easily generate QR codes.

## Important Tips

Keep in mind that you are free to do what's best for your development, and these tips are just
a helpful guide for laravel best practice based from my experience lol.

1. **Use migration (super ultra recommended)**  
   Let the team knows you change something in the database.  
   You're also helping future maintainers! ^-^

    ```bash
    php artisan make:migration add_status_column_to_users_table

    # see database/migrations/2025_09_09_203732_add_status_column_to_users_table.php
    ```

    Remember to run the migration after you're done with it!

    ```bash
    php artisan migrate
    ```

2. **Do not create enum columns in database**  
   Cause less headache in the future lol. Just create a php Enum:

    ```bash
    php artisan make:enum Enums/UserStatus --string  # <!-- I recommend this one

    # or

    php artisan make:enum Enums/UserStatus --int
    ```

    Then cast it to a model attribute:

    ```php
    // app/Models/User.php

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            /**
             * Now it acts as an enum in laravel instead of just a string (or varchar).
             * Say less to "add this new status to database",
             * since you can add it directly to UserStatus.php
             */
            'status' => \App\Enums\UserStatus::class,  // <!--
        ];
    }

    ```

    Then you could do like this:

    ```php
    $user->status === UserStatus::Active;  // Check if user status is active

    User::where('status', UserStatus::Suspended)->get();  // Get all suspended users

    ```

3. **Use VS Code extensions**  
   It's very helpful for your coding journey and stressful debugging.  
   Recommended extensions:

    - PHP (by DEVSENSE)
    - Tailwind CSS IntelliSense (by Tailwind Labs)
    - Material Icon Theme (by Philipp Kief) – optional, but keeps your IDE neat

4. **Static and Dynamic Assets**  
   Please put your **static assets** (e.g. images, videos, fonts, etc. that usually tends to stay as it is, no changes)
   in public folder as public/assets/..your_assets. It helps vite to build with less overhead.
   Example structure:

    ```
    MyCampusLMS
    ├── public/
    │   ├── assets/
    │   │   ├── images/
    │   │   │   ├── image1.jpg
    │   │   │   └── image2.jpg
    │   │   ├── videos/
    │   │   │   ├── video1.mp4
    │   │   │   └── video2.mp4
    │   │   └── fonts/
    │   │       ├── font1.ttf
    │   │       └── font2.woff2
    │   ├── build/
    │   └── edurock/
    ```

    For **dynamic assets** (e.g. css, js, scss, etc. that are always changing) must be place in resources folder
    and let Vite handles its thing. Keep in mind that you need to register those assets in vite.config.js.

    ```
    // vite.config.js

    export default defineConfig({
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                refresh: true,
            }),
        ],
    });
    ```

    And use them in your layouts to globally apply it to your views that uses this layout.

    ```
    // resources/views/layouts/app.blade.php

    <!DOCTYPE html>
    <head>
        {{-- ... --}}

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    ```

    Note: css, js, etc. can also be classified as static assets if these assets are minified or mostly cannot be
    change ocasionally.  
     Ex: `swiper-bundle.min.js`

5. **Try tutorials!**  
   Don't just read and watch. Code it!  
   It might be overwhelming, but do it one by one.
