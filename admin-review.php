<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thaakireen - Verify Users</title>
    <style>
        body {
            margin: 0;
            padding-bottom: 60px;
            font-family: 'Times New Roman', Times, serif, sans-serif;
        }

        header {
            background-color:  #387a5f;
            ;
            color: #fff;
            padding: 5px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        footer {
            background-color: #387a5f;
            color: #fff;
            padding: 10px;
            text-align: center;
            width: 100%;
            position: fixed;
            bottom: 0;
            z-index: 1000;
        }

        #logo img {
            max-width: 30%;
            height: auto;
            padding-left: 10px;
        }

        #menu-container {
            padding-top: 30px;
            padding-right: 20px;
        }

        #menu-container ul {
            list-style: none;
            display: flex;
            justify-content: flex-end;
            margin: 0;
            padding: 0;
        }

        #menu-container li {
            margin-left: 20px;
        }

        #menu-container a {
            text-decoration: none;
            color: #fff;
            font-weight: bold;
            font-size: 16px;
        }

        .profile-container {
            margin-top: 60px;
            padding: 20px;
        }

        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            grid-gap: 20px;
        }

        .user {
            background-color: rgba(223, 223, 223, 0.9);
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
        }

        .user p {
            margin: 5px 0;
        }

        .verify-button {
            background-color: #2f654e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            padding: 8px 12px;
        }

        .verify-button:hover {
            background-color: #265140;
        }

        h1 {
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <div id="logo">
        <img src="uploads/Logo.png" alt="Thaakireen Logo">
    </div>
    <div id="menu-container">
        <ul>
            <li><a href="adminHomepage.html">Home</a></li>
            <li><a href="adminProfile.html">My Profile</a></li>
            <li><a href="verifyUsers.html">Verify Users</a></li>
            <li><a href="users.html">User Management</a></li>
            <li><a href="adminLogin.html">Logout</a></li>
        </ul>
    </div>
</header>

<div class="profile-container">
    <h1>Pending Users</h1>
    <div id="pendingUsers" class="user-grid"></div>

    <h1>Active Users</h1>
    <div id="activeUsers" class="user-grid"></div>
</div>

<footer>
    <p>&copy; 2024 Thaakireen. All rights reserved.</p>
</footer>

<script>
    function fetchPendingUsers() {
        fetch('getPendingUsers.php')
            .then(response => response.json())
            .then(data => displayPendingUsers(data))
            .catch(error => console.error('Error fetching pending users:', error));
    }

    function displayPendingUsers(users) {
        const container = document.getElementById('pendingUsers');
        container.innerHTML = '';
        users.forEach(user => {
            const userDiv = document.createElement('div');
            userDiv.classList.add('user');
            userDiv.innerHTML = `
                <p>Name: ${user.first_name} ${user.last_name}</p>
                <p>Email: ${user.email}</p>
                <p>Role: ${user.userType}</p>
                <p>Status: <span class="status">${user.approved}</span></p>
                <button class="verify-button" data-id="${user.id}" data-lastname="${user.last_name}" data-email="${user.email}">Approve</button>
            `;
            container.appendChild(userDiv);
        });
    }

    function fetchActiveUsers() {
        fetch('getActiveUsers.php')
            .then(response => response.json())
            .then(data => displayActiveUsers(data))
            .catch(error => console.error('Error fetching active users:', error));
    }

    function displayActiveUsers(users) {
        const container = document.getElementById('activeUsers');
        container.innerHTML = '';
        users.forEach(user => {
            const userDiv = document.createElement('div');
            userDiv.classList.add('user');
            userDiv.innerHTML = `
                <p>Name: ${user.first_name} ${user.last_name}</p>
                <p>Email: ${user.email}</p>
                <p>Role: ${user.userType}</p>
                <p>Status: <span class="status">${user.approved}</span></p>
            `;
            container.appendChild(userDiv);
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('verify-button')) {
            const userId = event.target.getAttribute('data-id');
            const email = event.target.getAttribute('data-email');
            const lastName = event.target.getAttribute('data-lastname');
            verifyUser(userId, email, lastName);
        }
    });

    function verifyUser(userId, email, lastName) {
        const formData = new FormData();
        formData.append('userId', userId);
        formData.append('email', email);
        formData.append('lastName', lastName);

        fetch('verifyUser.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            fetchPendingUsers();
            fetchActiveUsers();
        })
        .catch(error => console.error('Error verifying user:', error));
    }

    window.onload = function() {
        fetchPendingUsers();
        fetchActiveUsers();
    };
</script>

</body>
</html>
