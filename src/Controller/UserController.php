<?php

require_once __DIR__ . '/../Model/User.php';

class UserController
{
    private User $user;
    private JwtService $jwtService;

    public function __construct()
    {
        $this->user = new User();
        $this->jwtService = new JwtService();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function list(): void
    {
        try {
            $result = $this->user->findAll();

            sendResponseCustom('Successfully get all user data from database', $result);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function get($id): void
    {
        try {
            $result = $this->user->findById($id);

            $httpResponseCode = !$result ? 404 : 200;
            $status = !$result ? 'Error' : 'Success';
            $message = !$result ? 'User not found' : 'Successfully retrieved user data';

            sendResponseCustom($message, $result, $status, $httpResponseCode);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function create(): void
    {
        try {
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $pseudo = $_POST['pseudo'];
            $birthDate = $_POST['birth_date'];
            $gender = $_POST['gender'];
            $avatar = $_POST['avatar'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $this->user->create($firstName, $lastName, $pseudo, $birthDate, $gender, $avatar, $email, $password);

            $result = $this->user->findByPseudoOrEmail($pseudo, $email);

            $httpResponseCode = !$result ? 400 : 200;
            $status = !$result ? 'Error' : 'Success';
            $message = !$result ? 'New user creation failed' : 'Successfully create new user';

            sendResponseCustom($message, $result, $status, $httpResponseCode);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function update($id): void
    {
        try {
            $refreshToken = $_COOKIE['refresh_token'] ?? null;

            if (!$refreshToken) {
                sendResponseCustom('No refresh token found', null, 'Error', 400);
                exit(1);
            }

            if (!$this->user->findById($id)) {
                sendResponseCustom('User not found', null, 'User not found', 404);
                exit(1);
            }

            $inputData = json_decode(file_get_contents('php://input'), true);

            if (!$inputData) {
                sendResponseCustom('Input data is empty', null, 'Error', 400);
                exit(1);
            }

            $acceptedFields = [
                'pseudo',
                'gender',
                'avatar',
            ];

            $toBeUpdated = [];
            foreach ($acceptedFields as $acceptedField) {
                if (isset($inputData[$acceptedField]) && $inputData[$acceptedField] !== '') {
                    $toBeUpdated[$acceptedField] = $inputData[$acceptedField];
                }
            }

            if (!$toBeUpdated) {
                sendResponseCustom('Nothing to update', null, 'Error', 400);
                exit(1);
            }

            $this->user->setFields($id, $toBeUpdated);
            $result = $this->user->findById($id);

            sendResponseCustom('Successfully update data', $result);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function replace($id): void
    {
        // TODO Nothing to do
    }

    public function delete($id): void
    {
        try {
            if (!$this->user->findById($id)) {
                sendResponse404();
                exit(1);
            }

            $this->user->deleteById($id);

            sendResponseCustom('Successfully delete user');
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function authenticate(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            $user = $this->user->findByEmail($email);

            $badResult = !$user || !password_verify($password, $user['password']);

            $accessToken = null;
            if (!$badResult) {
                $userId = $user['id'];
                $accessToken = $this->jwtService->createToken([
                    'user_id' => $userId,
                    'email' => $email,
                ]);

                $refreshToken = bin2hex(random_bytes(64));
                $expiresIn = $_ENV['JWT_REFRESH_TTL'] ?? 604800; // or 7 days (7*24*60*60)

                $this->user->storeRefreshToken($userId, $refreshToken, $expiresIn);

                setcookie('refresh_token', $refreshToken, [
                    'expires' => time() + $expiresIn,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => false, // true in production (HTTPS)
                    'samesite' => 'Lax',
                ]);
            }

            $httpResponseCode = $badResult ? 401 : 200;
            $status = $badResult ? 'Error' : 'Success';
            $message = $badResult ? 'Invalid credentials' : 'Successfully authenticate user';

            sendResponseCustom($message, ['accessToken' => $accessToken], $status, $httpResponseCode);
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
            sendResponse500();
        }
    }

    public function deauthenticate($id): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if (!$refreshToken) {
            sendResponseCustom('No refresh token found', null, 'Error', 400);
            exit(1);
        }

        $this->user->revokeRefreshToken($id, $refreshToken);
        sendResponseCustom('Refresh token revoked');

        setcookie('refresh_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => false, // true in production (HTTPS)
            'samesite' => 'Lax',
        ]);
    }

    public function reauthenticate(): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? null;

        if (!$refreshToken) {
            sendResponseCustom('No refresh token found', null, 'Error', 400);
            exit(1);
        }

        $user = $this->user->findByToken($refreshToken);
        if (!$user) {
            sendResponseCustom('Invalid or revoked refresh token', null, 'Error', 401);
            exit(1);
        }

        try {
            $payload = ['user_id' => $user['id'], 'email' => $user['email']];
            $accessToken = $this->jwtService->createToken($payload);
        } catch (Exception $e) {
            logWithDate('Unknown error', $e->getMessage());
            sendResponse500();
            exit(1);
        }

        sendResponseCustom('Successfully reauthenticate user', ['accessToken' => $accessToken]);
        exit(0);
    }
}
