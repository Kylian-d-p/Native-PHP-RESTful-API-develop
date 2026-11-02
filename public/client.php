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

    <p id="message"></p>

    <!-- Utilisateur non authentifié -->
    <section id="guest-section" style="display: none">
        <article>
            <h2>Authentification</h2>
            <form id="login-form">
                <label>Email <input type="email" name="email" required></label>
                <label>Mot de passe <input type="password" name="password" required></label>
                <button type="submit">Se connecter</button>
            </form>
        </article>

        <article>
            <h2>Création d'un compte</h2>
            <form id="create-form">
                <label>Prénom <input type="text" name="first_name" required></label>
                <label>Nom <input type="text" name="last_name" required></label>
                <label>Pseudo <input type="text" name="pseudo" required></label>
                <label>Date de naissance <input type="date" name="birth_date" required></label>
                <label>Genre
                    <select name="gender">
                        <option value="m">Homme</option>
                        <option value="f">Femme</option>
                        <option value="o">Autre</option>
                    </select>
                </label>
                <label>Avatar <input type="text" name="avatar"></label>
                <label>Email <input type="email" name="email" required></label>
                <label>Mot de passe <input type="password" name="password" required></label>
                <button type="submit">Créer le compte</button>
            </form>
        </article>
    </section>

    <!-- Utilisateur authentifié -->
    <section id="user-section" style="display: none">
        <article>
            <p>Authentifié en tant que <strong id="user-pseudo"></strong></p>
            <form id="logout-form">
                <button type="submit">Se déconnecter</button>
            </form>
        </article>

        <article>
            <h2>Modification du compte</h2>
            <form id="update-form">
                <label>Pseudo <input type="text" name="pseudo" required></label>
                <label>Genre
                    <select name="gender">
                        <option value="m">Homme</option>
                        <option value="f">Femme</option>
                        <option value="o">Autre</option>
                    </select>
                </label>
                <label>Avatar <input type="text" name="avatar"></label>
                <button type="submit">Enregistrer</button>
            </form>
        </article>

        <article>
            <h2>Suppression du compte</h2>
            <form id="delete-form">
                <button type="submit" class="secondary">Supprimer mon compte</button>
            </form>
        </article>
    </section>
</main>
<script>
    function showMessage(message, isError) {
        $('#message').text(message).css('color', isError ? 'red' : 'green');
    }

    // Message d'erreur renvoyé par l'API (réponse JSON), ou erreur réseau
    function errorMessage(jqXHR) {
        return jqXHR.responseJSON ? jqXHR.responseJSON.message : 'API injoignable';
    }

    // Identifiant de l'utilisateur authentifié, lu dans le payload du token d'accès
    // (JWT = header.payload.signature, chaque partie étant encodée en base64url)
    function getUserId() {
        const payload = localStorage.getItem('accessToken').split('.')[1];
        return JSON.parse(atob(payload.replace(/-/g, '+').replace(/_/g, '/'))).user_id;
    }

    // Utilisateur authentifié : formulaires d'authentification et de création cachés, compte affiché
    function showUser(user) {
        $('#user-pseudo').text(user.pseudo);
        $('#update-form [name=pseudo]').val(user.pseudo);
        $('#update-form [name=gender]').val(user.gender);
        $('#update-form [name=avatar]').val(user.avatar);
        $('#guest-section').hide();
        $('#user-section').show();
    }

    function showGuest() {
        $('#user-section').hide();
        $('#guest-section').show();
    }

    // Erreur d'une requête authentifiée : un code 401 signifie que le token d'accès a expiré,
    // il est alors rafraîchi automatiquement puis la requête est rejouée (retry)
    function handleError(jqXHR, retry) {
        if (jqXHR.status === 401) {
            reauthenticate(retry);
        } else {
            showMessage('Erreur : ' + errorMessage(jqXHR), true);
        }
    }

    function authenticate(email, password) {
        $.ajax({
            url: 'http://localhost:8000/api/login',
            method: 'POST',
            data: {email: email, password: password},
            success: function (response) {
                const accessToken = response.data.accessToken;
                localStorage.setItem('accessToken', accessToken);
                $('#login-form')[0].reset();
                showMessage('Authentification réussie');
                endpointGet(getUserId());
            },
            error: function (jqXHR) {
                showMessage('Échec de l\'authentification : ' + errorMessage(jqXHR), true);
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
                showGuest();
                showMessage('Désauthentification réussie');
            },
            error: function (jqXHR) {
                handleError(jqXHR, deauthenticate);
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
                if (callback) callback(response.data.accessToken);
            },
            error: function () {
                // Refresh token absent, expiré ou révoqué : l'utilisateur doit se réauthentifier manuellement
                localStorage.removeItem('accessToken');
                showGuest();
                showMessage('Session expirée, veuillez vous réauthentifier', true);
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
                showUser(response.data[0]);
            },
            error: function (jqXHR) {
                handleError(jqXHR, function () {
                    endpointGet(id);
                });
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
                $('#create-form')[0].reset();
                showMessage('Compte créé, vous pouvez vous authentifier');
            },
            error: function (jqXHR) {
                showMessage('Échec de la création du compte : ' + errorMessage(jqXHR), true);
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
                showUser(response.data[0]);
                showMessage('Compte modifié');
            },
            error: function (jqXHR) {
                handleError(jqXHR, function () {
                    endpointUpdate(id, data);
                });
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
                localStorage.removeItem('accessToken');
                showGuest();
                showMessage('Compte supprimé');
            },
            error: function (jqXHR) {
                handleError(jqXHR, function () {
                    endpointDelete(id);
                });
            }
        });
    }

    $('#login-form').on('submit', function (event) {
        event.preventDefault();
        authenticate($(this).find('[name=email]').val(), $(this).find('[name=password]').val());
    });

    $('#logout-form').on('submit', function (event) {
        event.preventDefault();
        deauthenticate();
    });

    $('#create-form').on('submit', function (event) {
        event.preventDefault();
        endpointCreate($(this).serialize());
    });

    // Seuls les champs pseudo, gender et avatar sont modifiables
    $('#update-form').on('submit', function (event) {
        event.preventDefault();
        endpointUpdate(getUserId(), {
            pseudo: $(this).find('[name=pseudo]').val(),
            gender: $(this).find('[name=gender]').val(),
            avatar: $(this).find('[name=avatar]').val()
        });
    });

    $('#delete-form').on('submit', function (event) {
        event.preventDefault();
        if (confirm('Supprimer définitivement votre compte ?')) {
            endpointDelete(getUserId());
        }
    });

    // Au chargement de la page, un utilisateur déjà authentifié retrouve son compte
    // (si son token d'accès a expiré entre-temps, il est rafraîchi automatiquement)
    if (localStorage.getItem('accessToken')) {
        endpointGet(getUserId());
    } else {
        showGuest();
    }
</script>
</body>
</html>
