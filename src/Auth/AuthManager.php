<?php

declare(strict_types=1);

namespace Magmi\Gui\Auth;

use Magmi\Core\Config\ProfileManager;
use Magmi\Core\Database\DbHelper;
use Magmi\Gui\App\BasePath;
use Magmi\Gui\Database\DbConfigFactory;

class AuthManager
{
    private string $configDir;
    private string $dataDir;

    public function __construct()
    {
        $basePath = BasePath::get();
        $this->configDir = $basePath . '/data/config';
        $this->dataDir = $basePath . '/data';
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['magmi_authenticated']) && $_SESSION['magmi_authenticated'] === true;
    }

    public function authenticate(string $username, string $password): bool
    {
        if ($this->isDbAvailable()) {
            return $this->authenticateDbAdmin($username, $password);
        }

        return $this->authenticateEmergency($username, $password);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function isDbAvailable(): bool
    {
        $global = $this->getGlobalConfig();
        $dbConfig = DbConfigFactory::fromGlobalConfig($global);
        if ($dbConfig === null) {
            return false;
        }

        try {
            $dbHelper = new DbHelper($dbConfig);
            $dbHelper->connect();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAuthMode(): string
    {
        return $this->isDbAvailable() ? 'db' : 'emergency';
    }

    public function getEmergencyCredentials(): array
    {
        $file = $this->getEmergencyFilePath();
        if (!file_exists($file)) {
            $this->createEmergencyPasswordFile();
        }

        $contents = file_get_contents($file);
        $username = '';
        $password = '';

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'username:')) {
                $username = trim(substr($line, strlen('username:')));
            } elseif (str_starts_with($line, 'password:')) {
                $password = trim(substr($line, strlen('password:')));
            }
        }

        if ($username === '' || $password === '' || strlen($password) < 16) {
            $this->createEmergencyPasswordFile();
            return $this->getEmergencyCredentials();
        }

        return ['username' => $username, 'password' => $password];
    }

    public function createEmergencyPasswordFile(): void
    {
        $file = $this->getEmergencyFilePath();
        $username = 'admin' . $this->randomString(5, '0123456789');
        $password = $this->randomString(16, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789+-.!');

        $contents = "username: {$username}\npassword: {$password}\n";
        file_put_contents($file, $contents);
        chmod($file, 0600);
    }

    private function authenticateEmergency(string $username, string $password): bool
    {
        $credentials = $this->getEmergencyCredentials();
        if ($credentials['username'] !== $username) {
            return false;
        }
        return hash_equals($credentials['password'], $password);
    }

    private function authenticateDbAdmin(string $username, string $password): bool
    {
        $global = $this->getGlobalConfig();
        $dbConfig = DbConfigFactory::fromGlobalConfig($global);
        if ($dbConfig === null) {
            return false;
        }

        try {
            $dbHelper = new DbHelper($dbConfig);
            $dbHelper->connect();

            $table = $dbHelper->tableName('admin_user');
            $passwordColumn = $this->getAdminPasswordColumn($dbHelper, $table);
            $stmt = $dbHelper->select(
                "SELECT {$passwordColumn} AS pwd_value FROM {$table} WHERE username = :username AND is_active = 1",
                [':username' => $username]
            );
            $row = $stmt->fetch();
            $stmt->closeCursor();

            if (!$row || empty($row['pwd_value'])) {
                return false;
            }

            return $this->verifyMagentoPassword($password, $row['pwd_value']);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getAdminPasswordColumn(DbHelper $dbHelper, string $table): string
    {
        try {
            $sql = "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column";
            $count = (int) $dbHelper->selectOne($sql, [':table' => $table, ':column' => 'password_hash'], 'cnt');
            if ($count > 0) {
                return 'password_hash';
            }
        } catch (\Exception $e) {
            error_log('Magmi Auth: failed to detect password_hash column - ' . $e->getMessage());
        }

        return 'password';
    }

    private function verifyMagentoPassword(string $password, string $hash): bool
    {
        // Modern bcrypt hash (password_hash)
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
            return password_verify($password, $hash);
        }

        $magento2Result = $this->verifyMagento2Password($password, $hash);
        if ($magento2Result !== null) {
            return $magento2Result;
        }

        // Magento legacy format: version:hash:salt (old Magmi style)
        if (str_contains($hash, ':')) {
            $parts = explode(':', $hash, 3);
            if (count($parts) === 3 && is_numeric($parts[0])) {
                $algo = $parts[0];
                $storedHash = $parts[1];
                $salt = $parts[2];
                if ($algo === '0' || $algo === 'latest') {
                    return password_verify($password, $storedHash);
                }
                if ($algo === '1') {
                    return hash_equals($storedHash, md5($salt . $password));
                }
                if ($algo === '2') {
                    return hash_equals($storedHash, hash('sha256', $salt . $password));
                }
                if ($algo === '3') {
                    return hash_equals($storedHash, hash('sha512', $salt . $password));
                }
                return hash_equals($storedHash, hash('sha256', $salt . $password));
            }

            // Simple hash:salt without version
            if (count($parts) === 2) {
                [$storedHash, $salt] = $parts;
                return hash_equals($storedHash, hash('sha256', $salt . $password));
            }
        }

        return false;
    }

    private function verifyMagento2Password(string $password, string $hash): ?bool
    {
        if (!str_contains($hash, ':')) {
            return null;
        }

        $parts = explode(':', $hash, 3);
        if (count($parts) !== 3) {
            return null;
        }

        [$storedHash, $salt, $versionString] = $parts;
        if ($storedHash === '' || $salt === '' || $versionString === '') {
            return null;
        }

        $versions = array_filter(explode(':', $versionString), static fn ($v) => $v !== '');
        if (empty($versions)) {
            return null;
        }

        $recreated = $password;
        try {
            foreach ($versions as $version) {
                if (preg_match('/^3_(\d+)_(\d+)_(\d+)$/', $version, $matches)) {
                    $recreated = $this->argonHash(
                        $recreated,
                        (int) $matches[1],
                        (int) $matches[2],
                        (int) $matches[3],
                        $salt
                    );
                    continue;
                }

                $versionInt = (int) $version;
                if ($versionInt === 2) {
                    $recreated = $this->argonHash(
                        $recreated,
                        defined('SODIUM_CRYPTO_SIGN_SEEDBYTES') ? SODIUM_CRYPTO_SIGN_SEEDBYTES : 32,
                        defined('SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE') ? SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE : 2,
                        defined('SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE') ? SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE : 67108864,
                        $salt
                    );
                    continue;
                }

                $recreated = $this->generateSimpleHash($salt . $recreated, $versionInt);
            }
        } catch (\Throwable $e) {
            return null;
        }

        return hash_equals($recreated, $storedHash);
    }

    private function generateSimpleHash(string $data, int $version): string
    {
        return match ($version) {
            0 => md5($data),
            1 => hash('sha256', $data),
            3 => hash('sha512', $data),
            default => hash('sha256', $data),
        };
    }

    private function argonHash(string $data, int $seedBytes, int $opsLimit, int $memLimit, string $salt): string
    {
        if (!function_exists('sodium_crypto_pwhash')) {
            throw new \RuntimeException('libsodium extension is required for Argon hashing.');
        }

        $saltBytes = $salt;
        $saltLength = defined('SODIUM_CRYPTO_PWHASH_SALTBYTES') ? SODIUM_CRYPTO_PWHASH_SALTBYTES : 16;
        if (strlen($saltBytes) < $saltLength) {
            $saltBytes = str_pad($saltBytes, $saltLength, $saltBytes);
        } elseif (strlen($saltBytes) > $saltLength) {
            $saltBytes = substr($saltBytes, 0, $saltLength);
        }

        return bin2hex(
            sodium_crypto_pwhash(
                $seedBytes,
                $data,
                $saltBytes,
                $opsLimit,
                $memLimit,
                defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13') ? SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13 : 2
            )
        );
    }

    private function getEmergencyFilePath(): string
    {
        return $this->dataDir . '/emergency_password.txt';
    }

    private function getGlobalConfig(): array
    {
        $file = $this->configDir . '/global.json';
        if (!file_exists($file)) {
            return [];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }

    private function randomString(int $length, string $charset): string
    {
        $max = strlen($charset) - 1;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $charset[random_int(0, $max)];
        }
        return $result;
    }
}
