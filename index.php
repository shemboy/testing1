<?php
// This is the secure, server-side code. It's not visible to the user's browser.

// The questions and answers are stored here, securely on the server.
// List of students (for demonstration purposes)
$students = [];
if (file_exists('students.txt')) {
    $lines = file('students.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        list($id, $last, $first) = explode('|', $line);
        $students[$id] = "$first $last";
    }
}

session_start();
header('Content-Type: text/html');

// Handle API requests from the JavaScript code
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    switch ($action) {
        case 'registerStudent':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';
            $lastName = $data['lastName'] ?? '';
            $firstName = $data['firstName'] ?? '';
            if (!$id || !$lastName || !$firstName) {
                echo json_encode(['status' => 'error', 'message' => 'All fields required.']);
                exit;
            }
            $student_line = "$id|$lastName|$firstName\n";
            file_put_contents('students.txt', $student_line, FILE_APPEND | LOCK_EX);
            echo json_encode(['status' => 'success']);
            exit;

        case 'getStudentName':
            $id = $_GET['id'] ?? '';
            $name = $students[$id] ?? '';
            echo json_encode(['name' => $name]);
            exit;

        case 'logTabLeave':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 'UnknownID';
            $name = $data['name'] ?? 'Anonymous';
            $date = date('Y-m-d H:i:s');
            $log_line = "Date: $date | ID: $id | Name: $name | Event: Tab minimized or left\n";
            file_put_contents('tab_leave_logs.txt', $log_line, FILE_APPEND | LOCK_EX);
            echo json_encode(['status' => 'logged']);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Professor Dualos Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #c1d5e0);
            color: #333;
            margin: 0;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        button, input[type="text"] {
            width: 100%;
            padding: 0.9rem 1.2rem;
            margin: 0.6rem 0;
            font-size: 1rem;
            border: 2px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        input[type="text"] {
            border: 2px solid #ddd;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .primary-btn {
            background: #3498db;
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .primary-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 2rem;
            }
            .container {
                padding: 1.5rem;
                margin-top: 0;
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <h1>🧠 Student Registration</h1>
    <div class="container" id="quiz-app">
        <div id="registrationScreen">
            <p>Register your Student ID and Name:</p>
            <input type="text" id="regIdInput" placeholder="Student ID" />
            <input type="text" id="regLastNameInput" placeholder="Last Name" />
            <input type="text" id="regFirstNameInput" placeholder="First Name" />
            <button id="registerBtn" class="primary-btn">Register</button>
            <div id="regResult"></div>
        </div>
        
    </div>
    <script>
        const registrationScreen = document.getElementById('registrationScreen');
        const regIdInput = document.getElementById('regIdInput');
        const regLastNameInput = document.getElementById('regLastNameInput');
        const regFirstNameInput = document.getElementById('regFirstNameInput');
        const registerBtn = document.getElementById('registerBtn');
        const regResult = document.getElementById('regResult');
        const welcomeScreen = document.getElementById('welcomeScreen');
        const idInput = document.getElementById('idInput');
        const nameInput = document.getElementById('nameInput');

        registerBtn.addEventListener('click', async () => {
            const id = regIdInput.value.trim();
            const lastName = regLastNameInput.value.trim();
            const firstName = regFirstNameInput.value.trim();
            if (!id || !lastName || !firstName) {
                regResult.textContent = "Please fill in all fields.";
                return;
            }
            const res = await fetch('?action=registerStudent', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, lastName, firstName })
            });
            const data = await res.json();
            if (data.status === 'success') {
                regResult.textContent = "Registration successful!";
                registrationScreen.style.display = 'none';
                welcomeScreen.style.display = 'block';
                idInput.value = id;
                nameInput.value = `${firstName} ${lastName}`;
                nameInput.style.display = 'block';
            } else {
                regResult.textContent = data.message || "Registration failed.";
            }
        });

        idInput.addEventListener('input', async () => {
            const id = idInput.value.trim();
            if (id !== "") {
                const res = await fetch(`?action=getStudentName&id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (data.name) {
                    nameInput.value = data.name;
                    nameInput.style.display = 'block';
                } else {
                    nameInput.value = "ID not found!";
                    nameInput.style.display = 'block';
                }
            } else {
                nameInput.style.display = 'none';
            }
        });

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                fetch('?action=logTabLeave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: idInput.value.trim(),
                        name: nameInput.value.trim()
                    })
                });
            }
        });
    </script>
</body>
</html>
