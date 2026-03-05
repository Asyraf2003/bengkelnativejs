<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <h1>Login Admin</h1>

    @if ($errors->any())
        <div style="color:red;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.perform') }}">
        @csrf

        <div>
            <label>Username</label><br>
            <input name="username" value="{{ old('username') }}" required>
        </div>

        <div style="margin-top:8px;">
            <label>Password</label><br>
            <input type="password" name="password" required>
        </div>

        <div style="margin-top:12px;">
            <button type="submit">Login</button>
        </div>
    </form>
</body>
</html>
