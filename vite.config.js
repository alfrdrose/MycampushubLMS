import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/preloader.js",
                "resources/js/theme.js",
                "resources/js/login.js",
                "resources/js/stickystickyHeader.js",
                "resources/js/logout.js",
                "resources/js/lmsmodal.js",
                "resources/js/lmsmodal2.js",
                "resources/js/sections.js",
                "resources/js/teachers.js",
                "resources/js/students.js",
                "resources/js/subjects.js",
                "resources/js/TeacherPage.js",
                "resources/js/StudentPage.js",
                "resources/js/ActSubmissionFiles.js",
                "resources/js/activityOptions.js",
                "resources/js/tags.js",
                "resources/js/notification.js",
                "resources/js/admin-modal-loading.js",
            ],
            refresh: true,
        }),
    ],
});
