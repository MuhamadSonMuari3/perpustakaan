<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Login Perpustakaan</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .login-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-title h2 {
            margin-bottom: 5px;
        }

        .login-title p {
            color: #777;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input:focus {
            outline: none;
            border-color: #0d6efd;
        }

        button {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 5px;
            background: #0d6efd;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #0b5ed7;
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-box">

        <div class="login-title">

            <h2>Perpustakaan</h2>

            <p>Silakan Login</p>

        </div>

        <form action="proses_login.php" method="POST">

            <div class="form-group">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    placeholder="Masukkan username"
                    required
                >

            </div>

            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >

            </div>

            <button type="submit">
                Login
            </button>

        </form>

    </div>

</div>

</body>

</html>