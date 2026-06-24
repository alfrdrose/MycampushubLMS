<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h1>Register</h1>

    <form action="{{route('register')}}" method="POST">
        @csrf
        <label for="name">Username: </label>
        <input type="text" name="name" required value="{{old('name')}}"><br>
        
        <label for="email">Email: </label>
        <input type="email" name="email" required value="{{old('email')}}"><br>
        
        <label for="password">Password: (min 8 char)</label>
        <input type="password" name="password" required><br>
    
        <label for="password_confirmation">Confirm Password: </label>
        <input type="password" name="password_confirmation" required><br>

        <div class="flex flex-col gap-1">
            <p class="text-[#373737] font-medium dark:text-white">Account Type</p>
            <select id="cmbtype" name="role" class="p-3 border border-[#ABB7C2] rounded-md text-base dark:bg-whiteColor-dark dark:text-white">
                <option value="" disabled selected>Select role</option>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
            <p id="errortype" class="text-red-400 text-sm hidden">Role is required.</p>
        </div>

        <button type="submit">Register</button>

    </form>
    <form action="{{route('logout')}}" method="POST">
        @csrf
        <button type="submit">Return</button>
    </form>
</body>
</html>