<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client jQuery</title>
    <link rel="stylesheet" href="/assets/css/pico.min.css">
    <script src="/assets/js/jquery-3.7.1.min.js"></script>
</head>
<body>
<main class="container">
    <h1>Client jQuery</h1>
</main>
<script>
    let dataSample = {
        first_name: "Adrien",
        last_name: "Girard",
        pseudo: "CodeRanger",
        birth_date: "2004-12-03",
        gender: "m",
        avatar: "coderanger.jpg",
        email: "adrien.girard@example.com",
        password: "password"
    };

    let partialDataSample = {
        pseudo: "Avalonee",
        avatar: "avalonee.jpg"
    };

    function authenticate(email, password) {
        $.ajax({
            url: 'http://localhost:8000/api/login',
            method: 'POST',
            data: {email: email, password: password},
            success: function (response) {
                const accessToken = response.data.accessToken;
                localStorage.setItem('accessToken', accessToken);
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function deauthenticate() {
        $.ajax({
            url: 'http://localhost:8000/api/logout',
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                localStorage.removeItem('accessToken');
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function reauthenticate(callback) {
        $.ajax({
            url: 'http://localhost:8000/api/token/refresh',
            method: 'POST',
            success: function (response) {
                const accessToken = response.data.accessToken;
                localStorage.setItem('accessToken', accessToken);
                // TODO Do something if needed
                if (callback) callback(response.data.accessToken);
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function endpointList() {
        $.ajax({
            url: 'http://localhost:8000/api/user',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function endpointGet(id) {
        $.ajax({
            url: 'http://localhost:8000/api/user' + '/' + id,
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function endpointCreate(data) {
        $.ajax({
            url: 'http://localhost:8000/api/user',
            method: 'POST',
            data: data,
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function endpointUpdate(id, data) {
        $.ajax({
            url: 'http://localhost:8000/api/user' + '/' + id,
            method: 'PATCH',
            data: JSON.stringify(data),
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    function endpointReplace(id) {
        // TODO Nothing to do
    }

    function endpointDelete(id) {
        $.ajax({
            url: 'http://localhost:8000/api/user' + '/' + id,
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('accessToken'),
            },
            success: function (response) {
                // TODO Do something if needed
            },
            error: function () {
                // TODO Do something if needed
            }
        });
    }

    /*
     * Example of a sequence of actions:
     *   - 4
     *   - 1 (with valid credentials)
     *   - 4
     *   - 1 (with invalid credentials)
     *   - 4
     *   - 1 (with valid credentials)
     *   - 4
     *   - 2
     *   - 4
     *   - 1 (with valid credentials, then wait for 5 minutes or change JWT_TTL value)
     *   - 3
     *   - 2
     *   - 3
     */
    const action = 9;
    switch (action) {
        case 1:
            authenticate("lucas.morel@example.com", "password");
            break;
        case 2:
            deauthenticate();
            break;
        case 3:
            reauthenticate();
            break;
        case 4:
            endpointList();
            break;
        case 5:
            endpointGet(1);
            break;
        case 6:
            endpointCreate(dataSample);
            break;
        case 7:
            endpointUpdate(1, partialDataSample);
            break;
        case 8:
            // endpointReplace(1);
            // TODO Nothing to do
            break;
        case 9:
            endpointDelete(6);
            break;
        default:
            console.log('Unknown action number: ' + action);
            break;
    }
</script>
</body>
</html>
