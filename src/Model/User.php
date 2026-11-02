<?php

class User
{
    private ?PDO $conn = null;

    public function __construct()
    {
        try {
            $this->conn = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            logWithDate('DB connection failed', $e->getMessage());
        }
    }

    public function findAll(): ?array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT * FROM user';

            $stmt = $this->conn->query($query);

            return $stmt->fetchAll();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    public function findById($id): ?array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT * FROM user WHERE id=:id';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    public function findByEmail($email): mixed
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT id, password FROM user WHERE email=:email';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetch();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    public function findByPseudoOrEmail($pseudo, $email): ?array
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT * FROM user WHERE pseudo=:pseudo OR email=:email';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pseudo', $pseudo);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
            return null;
        }
    }

    public function findByToken(string $refreshToken): mixed
    {
        try {
            throwDbNullConnection($this->conn);

            // FIXME To be optimized (eg. 6 characters token prefix)
            $query = 'SELECT user.email, user.id, token ';
            $query .= 'FROM user ';
            $query .= 'JOIN refresh_token ';
            $query .= 'ON user.id = refresh_token.user_id ';
            $query .= 'WHERE revoked = 0';
            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            $users = $stmt->fetchAll();
            foreach ($users as $user) {
                if (password_verify($refreshToken, $user['token'])) {
                    return $user;
                }
            }
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }

        return null;
    }

    public function create($firstName, $lastName, $pseudo, $birthDate, $gender, $avatar, $email, $password): void
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'INSERT INTO user ';
            $query .= '(first_name, last_name, pseudo, birth_date, gender, avatar, email, password, created_at) ';
            $query .= 'VALUES (:first_name, :last_name, :pseudo, :birth_date, :gender, :avatar, :email, :password, NOW())';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':pseudo', $pseudo);
            $stmt->bindParam(':birth_date', $birthDate);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':avatar', $avatar);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }

    public function setAll($id, $fields): void
    {
        try {
            throwDbNullConnection($this->conn);

            $subQuery = array_map(fn($fieldName, $fieldValue) => "$fieldName=:$fieldName", array_keys($fields), array_values($fields));
            $subQuery = implode(', ', $subQuery);

            $query = "UPDATE user SET $subQuery WHERE id=:id";

            $stmt = $this->conn->prepare($query);
            foreach ($fields as $fieldName => &$fieldValue) {
                $stmt->bindParam(":$fieldName", $fieldValue);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }

    public function setFields($id, $fields): void
    {
        try {
            throwDbNullConnection($this->conn);

            $subQuery = array_map(fn($fieldName, $fieldValue) => "$fieldName=:$fieldName", array_keys($fields), array_values($fields));
            $subQuery = implode(', ', $subQuery);

            $query = "UPDATE user SET $subQuery WHERE id=:id";

            $stmt = $this->conn->prepare($query);
            foreach ($fields as $fieldName => &$fieldValue) {
                $stmt->bindParam(":$fieldName", $fieldValue);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }

    public function deleteById($id): void
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'DELETE FROM user WHERE id=:id';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }

    public function storeRefreshToken(int $userId, string $refreshToken, int $expiresIn): void
    {
        $hashedToken = password_hash($refreshToken, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        try {
            throwDbNullConnection($this->conn);

            $query = 'INSERT INTO refresh_token (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $hashedToken);
            $stmt->bindParam(':expires_at', $expiresAt);
            $stmt->execute();
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }

    public function revokeRefreshToken(int $userId, string $currentRefreshToken): void
    {
        try {
            throwDbNullConnection($this->conn);

            $query = 'SELECT id, token FROM refresh_token WHERE user_id = :user_id AND revoked = 0';

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $refreshTokens = $stmt->fetchAll();
            foreach ($refreshTokens as $refreshToken) {
                if (password_verify($currentRefreshToken, $refreshToken['token'])) {
                    $query = 'UPDATE refresh_token SET revoked = 1 WHERE id = :id AND user_id = :user_id';

                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id', $refreshToken['id'], PDO::PARAM_INT);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        } catch (PDOException|Exception $e) {
            logWithDate('Query failed', $e->getMessage());
        }
    }
}
